<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CageWaitingListService
{
    /**
     * Tự động tìm lồng phù hợp; nếu không có thì đưa thú cưng vào danh sách chờ.
     * Hàm này được gọi bên trong DB::transaction().
     */
    public function assignOrQueue(int $petId, float $weight, string $treatment, bool $isolation): array
    {
        $pet = DB::table('pets')->where('pet_id', $petId)->lockForUpdate()->first();

        if (!$pet) {
            throw new \RuntimeException('Không tìm thấy thú cưng.');
        }

        // Nếu thú đã ở lồng phù hợp thì giữ nguyên, không tạo lượt lưu trú trùng.
        if ($pet->cage_id) {
            $current = DB::table('cages')->where('cage_id', $pet->cage_id)->first();
            if ($current && (($current->loailong === 'CACH_LY') === $isolation)) {
                $this->closeWaitingRows($petId, 'ASSIGNED', (int) $current->cage_id);
                return ['cage' => $current, 'waiting' => false];
            }

            // Không để thú cần cách ly tiếp tục ở chung lồng thường.
            $oldCageId = (int) $pet->cage_id;
            DB::table('pet_boardings')
                ->where('pet_id', $petId)
                ->where('cage_id', $oldCageId)
                ->where('status', 'checked_in')
                ->whereNull('check_out')
                ->update([
                    'check_out' => now(),
                    'status' => 'checked_out',
                    'updated_at' => now(),
                ]);

            DB::table('pets')->where('pet_id', $petId)->update(['cage_id' => null]);
            app(CageOccupancyService::class)->sync($oldCageId);
            $pet->cage_id = null;
        }

        $cage = $this->findSuitableCage($pet, $weight, $treatment, $isolation);

        if (!$cage) {
            $this->putOnWaitingList($petId, $weight, $treatment, $isolation);

            return ['cage' => null, 'waiting' => true];
        }

        $this->assignToCage($pet, $cage, $isolation);
        $this->closeWaitingRows($petId, 'ASSIGNED', (int) $cage->cage_id);

        return [
            'cage' => app(CageOccupancyService::class)->sync((int) $cage->cage_id),
            'waiting' => false,
        ];
    }

    /**
     * Khi có chỗ trống (ví dụ thú xuất viện), thử xếp lần lượt các trường hợp đang chờ.
     */
    public function dispatchWaiting(): int
    {
        if (!Schema::hasTable('pet_cage_waitlist')) {
            return 0;
        }

        $assigned = 0;
        $waitingRows = DB::table('pet_cage_waitlist')
            ->where('status', 'WAITING')
            ->orderBy('created_at')
            ->orderBy('waitlist_id')
            ->lockForUpdate()
            ->get();

        foreach ($waitingRows as $row) {
            $pet = DB::table('pets')->where('pet_id', $row->pet_id)->lockForUpdate()->first();

            if (!$pet || $pet->cage_id) {
                if (!$pet) {
                    DB::table('pet_cage_waitlist')->where('waitlist_id', $row->waitlist_id)
                        ->update(['status' => 'CANCELLED', 'updated_at' => now()]);
                }
                continue;
            }

            $cage = $this->findSuitableCage(
                $pet,
                (float) $row->weight,
                $row->treatment_type,
                (bool) $row->isolation_required
            );

            if (!$cage) {
                continue;
            }

            $this->assignToCage($pet, $cage, (bool) $row->isolation_required);
            DB::table('pet_cage_waitlist')->where('waitlist_id', $row->waitlist_id)->update([
                'status' => 'ASSIGNED',
                'assigned_cage_id' => $cage->cage_id,
                'assigned_at' => now(),
                'updated_at' => now(),
            ]);
            app(CageOccupancyService::class)->sync((int) $cage->cage_id);
            $assigned++;
        }

        return $assigned;
    }

    private function findSuitableCage(object $pet, float $weight, string $treatment, bool $isolation): ?object
    {
        $hasMaxWeight = Schema::hasColumn('cages', 'tai_trong_toi_da');
        $hasSpecies = Schema::hasColumn('cages', 'loai_phu_hop');

        $cages = DB::table('cages')
            ->whereNotIn('trangthai', ['DANG_VE_SINH', 'BAO_TRI'])
            ->when($isolation, fn ($q) => $q->where('loailong', 'CACH_LY'))
            ->when(!$isolation, fn ($q) => $q->where('loailong', '<>', 'CACH_LY'))
            ->when($hasMaxWeight, function ($q) use ($weight) {
                $q->where(function ($sub) use ($weight) {
                    $sub->whereNull('tai_trong_toi_da')->orWhere('tai_trong_toi_da', '>=', $weight);
                });
            })
            ->orderByRaw("CASE WHEN loailong = ? THEN 0 ELSE 1 END", [
                $treatment === 'SURGERY' ? 'CAP_CUU_ICU' : 'THUONG',
            ])
            ->orderBy('cage_id')
            ->lockForUpdate()
            ->get();

        foreach ($cages as $cage) {
            if ($hasSpecies && !empty($cage->loai_phu_hop) && $cage->loai_phu_hop !== 'TAT_CA'
                && $this->normalizeSpecies((string) $cage->loai_phu_hop) !== $this->normalizeSpecies((string) $pet->loai)) {
                continue;
            }

            $occupants = DB::table('pets')->where('cage_id', $cage->cage_id)->get();
            $count = $occupants->count();
            $capacity = $cage->loailong === 'CACH_LY' ? 1 : (int) $cage->succhua;

            if ($count >= $capacity) {
                continue;
            }

            // Lồng cách ly chỉ nhận đúng một thú; lồng thường/ICU không nhận thú cách ly.
            if ($cage->loailong === 'CACH_LY' && $count > 0) {
                continue;
            }

            $compatible = true;
            foreach ($occupants as $other) {
                if ($this->normalizeSpecies((string) $other->loai) !== $this->normalizeSpecies((string) $pet->loai)) {
                    $compatible = false;
                    break;
                }
                if ($pet->gioitinh !== 'CHUA_XAC_DINH' && $other->gioitinh !== 'CHUA_XAC_DINH'
                    && $pet->gioitinh !== $other->gioitinh) {
                    $compatible = false;
                    break;
                }

                if (Schema::hasColumn('pet_boardings', 'isolation_required')) {
                    $otherIsolation = DB::table('pet_boardings')
                        ->where('pet_id', $other->pet_id)
                        ->where('cage_id', $cage->cage_id)
                        ->where('status', 'checked_in')
                        ->whereNull('check_out')
                        ->value('isolation_required');
                    if ((bool) $otherIsolation) {
                        $compatible = false;
                        break;
                    }
                }
            }

            if ($compatible) {
                return $cage;
            }
        }

        return null;
    }

    private function assignToCage(object $pet, object $cage, bool $isolation): void
    {
        $boarding = [
            'pet_id' => $pet->pet_id,
            'cage_id' => $cage->cage_id,
            'check_in' => now(),
            'status' => 'checked_in',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('pet_boardings', 'isolation_required')) {
            $boarding['isolation_required'] = $isolation;
        }

        DB::table('pet_boardings')->insert($boarding);
        DB::table('pets')->where('pet_id', $pet->pet_id)->update([
            'cage_id' => $cage->cage_id,
            'trangthai' => 'NOI_TRU',
        ]);
    }

    private function putOnWaitingList(int $petId, float $weight, string $treatment, bool $isolation): void
    {
        $existing = DB::table('pet_cage_waitlist')
            ->where('pet_id', $petId)
            ->where('status', 'WAITING')
            ->lockForUpdate()
            ->first();

        $data = [
            'weight' => $weight,
            'treatment_type' => $treatment,
            'isolation_required' => $isolation,
            'note' => 'Chưa có lồng phù hợp tại thời điểm tiếp nhận.',
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('pet_cage_waitlist')->where('waitlist_id', $existing->waitlist_id)->update($data);
            return;
        }

        DB::table('pet_cage_waitlist')->insert($data + [
            'pet_id' => $petId,
            'status' => 'WAITING',
            'created_at' => now(),
        ]);
    }

    private function closeWaitingRows(int $petId, string $status, ?int $cageId = null): void
    {
        if (!Schema::hasTable('pet_cage_waitlist')) {
            return;
        }

        DB::table('pet_cage_waitlist')->where('pet_id', $petId)->where('status', 'WAITING')->update([
            'status' => $status,
            'assigned_cage_id' => $cageId,
            'assigned_at' => $cageId ? now() : null,
            'updated_at' => now(),
        ]);
    }

    private function normalizeSpecies(string $species): string
    {
        return Str::lower(Str::ascii(trim($species)));
    }
}
