<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class PetQrController extends Controller
{
    public function show(string $qrCode)
    {
        $pet = DB::table('pets')
            ->leftJoin('owners', 'owners.owner_id', '=', 'pets.owner_id')
            ->leftJoin('weight_logs', function ($join) {
                $join->on('weight_logs.pet_id', '=', 'pets.pet_id')
                    ->whereRaw('weight_logs.id = (SELECT MAX(wl.id) FROM weight_logs wl WHERE wl.pet_id = pets.pet_id)');
            })
            ->where('pets.qr_code', $qrCode)
            ->select(
                'pets.mathucung', 'pets.ten_thucung', 'pets.loai', 'pets.giong',
                'pets.gioitinh', 'pets.ngaysinh', 'pets.trangthai',
                'owners.ho_ten as owner_name', 'owners.phone as owner_phone',
                'weight_logs.cannang as current_weight'
            )
            ->first();

        abort_if(!$pet, 404, 'Không tìm thấy hồ sơ thú cưng.');

        return view('pet-qr', compact('pet'));
    }
}
