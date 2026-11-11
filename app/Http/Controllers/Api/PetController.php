<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetController extends Controller
{
    private function query()
    {
        return DB::table('pets')
            ->leftJoin('owners', 'owners.owner_id', '=', 'pets.owner_id')
            ->select('pets.*', 'owners.ho_ten as owner_name', 'owners.phone as owner_phone');
    }

    private function findOrFail($id)
    {
        $pet = $this->query()->where('pets.pet_id', $id)->first();
        abort_if(!$pet, 404, 'Không tìm thấy thú cưng');
        return $pet;
    }

    private function format($p): array
    {
        return [
            'id' => $p->pet_id,
            'mathucung' => $p->mathucung,
            'name' => $p->ten_thucung,
            'species' => $p->loai,
            'breed' => $p->giong,
            'weight' => $p->cannang,
            'owner_id' => $p->owner_id,
            'cage_id' => $p->cage_id,
            'trangthai' => $p->trangthai,
            'owner' => ['id' => $p->owner_id, 'name' => $p->owner_name, 'phone' => $p->owner_phone],
        ];
    }

    public function index()
    {
        $pets = $this->query()->orderByDesc('pets.pet_id')->get();
        return response()->json($pets->map(fn ($p) => $this->format($p))->values(), 200);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'owner_id' => 'required|exists:owners,owner_id',
            'name' => 'required|string|max:50',
            'species' => 'required|string|max:30',
            'breed' => 'nullable|string|max:50',
            'weight' => 'required|numeric|min:0.1|max:999',
        ]);

        $id = DB::table('pets')->insertGetId([
            'mathucung' => 'TMP' . uniqid(),
            'owner_id' => $v['owner_id'],
            'ten_thucung' => $v['name'],
            'loai' => $v['species'],
            'giong' => $v['breed'] ?? null,
            'cannang' => $v['weight'],
        ]);
        DB::table('pets')->where('pet_id', $id)->update(['mathucung' => sprintf('TC%04d', $id)]);

        return response()->json([
            'message' => 'Thêm hồ sơ thú cưng thành công',
            'data' => $this->format($this->findOrFail($id)),
        ], 201);
    }

    public function show($id)
    {
        return response()->json($this->format($this->findOrFail($id)), 200);
    }

    public function update(Request $request, $id)
    {
        $this->findOrFail($id);

        $v = $request->validate([
            'owner_id' => 'sometimes|exists:owners,owner_id',
            'name' => 'sometimes|string|max:50',
            'species' => 'sometimes|string|max:30',
            'breed' => 'sometimes|nullable|string|max:50',
            'weight' => 'sometimes|numeric|min:0.1|max:999',
        ]);

        $map = ['owner_id' => 'owner_id', 'name' => 'ten_thucung', 'species' => 'loai', 'breed' => 'giong', 'weight' => 'cannang'];
        $data = [];
        foreach ($map as $in => $col) {
            if (array_key_exists($in, $v)) {
                $data[$col] = $v[$in];
            }
        }
        if ($data) {
            DB::table('pets')->where('pet_id', $id)->update($data);
        }

        return response()->json(['message' => 'Cập nhật thành công', 'data' => $this->format($this->findOrFail($id))], 200);
    }

    public function destroy($id)
    {
        $pet = $this->findOrFail($id);

        if ($pet->cage_id) {
            return response()->json(['message' => 'Thú cưng đang nội trú. Hãy làm thủ tục thanh toán và xuất viện trước.'], 409);
        }

        try {
            DB::table('pets')->where('pet_id', $id)->delete();
        } catch (QueryException $e) {
            return response()->json(['message' => 'Không thể xóa: thú cưng đã có hồ sơ khám hoặc hóa đơn.'], 409);
        }

        return response()->json(['message' => 'Đã xóa hồ sơ thú cưng'], 200);
    }
}
