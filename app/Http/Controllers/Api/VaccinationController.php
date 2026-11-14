<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VaccinationController extends Controller
{
    public function types()
    {
        return response()->json([
            'status' => 'success',
            'data' => DB::table('vaccination_types')
                ->where('is_active', true)
                ->whereNotNull('inventory_item_id')
                ->orderBy('vaccination_type_id')
                ->get(['vaccination_type_id as id', 'name', 'inventory_item_id']),
        ]);
    }

    public function petIndex($petId)
    {
        abort_unless(DB::table('pets')->where('pet_id', $petId)->exists(), 404, 'Không tìm thấy thú cưng.');

        $rows = DB::table('pet_vaccinations as pv')
            ->join('vaccination_types as vt', 'vt.vaccination_type_id', '=', 'pv.vaccination_type_id')
            ->leftJoin('users as u', 'u.user_id', '=', 'pv.administered_by')
            ->where('pv.pet_id', $petId)
            ->orderByDesc('pv.vaccinated_at')
            ->orderByDesc('pv.vaccination_id')
            ->get([
                'pv.vaccination_id as id',
                'pv.vaccination_type_id',
                'vt.name as vaccine_name',
                'pv.vaccinated_at',
                'pv.next_due_date',
                'pv.notes',
                'u.ho_ten as administered_by_name',
            ]);

        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    public function storeForPet(Request $request, $petId)
    {
        abort_unless(DB::table('pets')->where('pet_id', $petId)->exists(), 404, 'Không tìm thấy thú cưng.');

        $data = $request->validate([
            'vaccination_type_id' => 'required|integer|exists:vaccination_types,vaccination_type_id',
            'vaccinated_at' => 'required|date',
            'next_due_date' => 'nullable|date|after_or_equal:vaccinated_at',
            'notes' => 'nullable|string|max:2000',
        ]);

        $active = DB::table('vaccination_types')
            ->where('vaccination_type_id', $data['vaccination_type_id'])
            ->where('is_active', true)->exists();
        if (!$active) {
            return response()->json(['status' => 'error', 'message' => 'Loại vắc-xin này đã ngừng sử dụng.'], 422);
        }

        $id = DB::table('pet_vaccinations')->insertGetId([
            'pet_id' => $petId,
            'vaccination_type_id' => $data['vaccination_type_id'],
            'vaccinated_at' => $data['vaccinated_at'],
            'next_due_date' => $data['next_due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'administered_by' => $request->user()->user_id ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã lưu lịch sử tiêm chủng.',
            'data' => DB::table('pet_vaccinations')->where('vaccination_id', $id)->first(),
        ], 201);
    }

    public function destroyForPet($petId, $id)
    {
        $deleted = DB::table('pet_vaccinations')
            ->where('pet_id', $petId)
            ->where('vaccination_id', $id)
            ->delete();

        abort_if(!$deleted, 404, 'Không tìm thấy lần tiêm.');
        return response()->json(['status' => 'success', 'message' => 'Đã xóa lần ghi nhận tiêm chủng.']);
    }

    public function schedule()
    {
        $rows = DB::table('pet_vaccinations as pv')
            ->join('pets as p', 'p.pet_id', '=', 'pv.pet_id')
            ->join('vaccination_types as vt', 'vt.vaccination_type_id', '=', 'pv.vaccination_type_id')
            ->leftJoin('owners as o', 'o.owner_id', '=', 'p.owner_id')
            ->whereNotNull('pv.next_due_date')
            ->orderBy('pv.next_due_date')
            ->get([
                'pv.vaccination_id as id',
                'pv.pet_id',
                'p.mathucung',
                'p.ten_thucung',
                'o.ho_ten as owner_name',
                'o.phone as owner_phone',
                'vt.name as vaccine_name',
                'pv.vaccinated_at',
                'pv.next_due_date',
            ]);

        $today = now()->toDateString();
        $data = $rows->map(function ($row) use ($today) {
            $row->status = $row->next_due_date < $today ? 'QUA_HAN' : ($row->next_due_date <= now()->addDays(30)->toDateString() ? 'SAP_DEN_HAN' : 'CON_HAN');
            return $row;
        });

        return response()->json(['status' => 'success', 'data' => $data]);
    }
};
