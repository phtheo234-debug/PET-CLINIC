<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetController extends Controller
{
    private function query()
    {
        // Subquery lấy chỉ số cân nặng mới nhất từ weight_logs (BR-04)
        $latestWeight = DB::table('weight_logs as wl1')
            ->select('wl1.pet_id', 'wl1.cannang')
            ->whereRaw('wl1.id = (SELECT MAX(wl2.id) FROM weight_logs wl2 WHERE wl2.pet_id = wl1.pet_id)');

        $latestExam = DB::table('medical_records as mr1')->select('mr1.pet_id', 'mr1.record_id', 'mr1.trangthai as exam_status', 'mr1.ngaykham as exam_date', 'mr1.invoice_id as exam_invoice_id')->whereRaw('mr1.record_id = (SELECT MAX(mr2.record_id) FROM medical_records mr2 WHERE mr2.pet_id = mr1.pet_id)');

        return DB::table('pets')
            ->leftJoinSub($latestExam, 'latest_exam', function ($join) { $join->on('pets.pet_id', '=', 'latest_exam.pet_id'); })
            ->leftJoin('owners', 'owners.owner_id', '=', 'pets.owner_id')
            ->leftJoinSub($latestWeight, 'latest_weight', function ($join) {
                $join->on('pets.pet_id', '=', 'latest_weight.pet_id');
            })
            ->select(
                'pets.*',
                'owners.ho_ten as owner_name',
                'owners.phone as owner_phone',
                'latest_weight.cannang as current_weight', 'latest_exam.record_id as record_id', 'latest_exam.exam_status as trangthai_kham', 'latest_exam.exam_date as ngaykham_gannhat', 'latest_exam.exam_invoice_id as mahoadon_kham'
            );
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
            'id'             => $p->pet_id,
            'mathucung'      => $p->mathucung,
            'name'           => $p->ten_thucung,
            'species'        => $p->loai,
            'breed'          => $p->giong,
            'gender'         => $p->gioitinh,
            'dob'            => $p->ngaysinh,
            'weight'         => $p->current_weight ? (float)$p->current_weight : null,
            'owner_id'       => $p->owner_id,
            'cage_id'        => $p->cage_id,
            'trangthai' => $p->trangthai,
            'record_id' => $p->record_id ?? null,
            'trangthai_kham' => $p->trangthai_kham ?? null,
            'ngaykham_gannhat' => $p->ngaykham_gannhat ?? null,
            'kham_da_thanh_toan' => !empty($p->mahoadon_kham),
            'owner'          => [
                'id'    => $p->owner_id,
                'name'  => $p->owner_name,
                'phone' => $p->owner_phone
            ],
        ];
    }

    // Lấy danh sách chủ nuôi đổ vào Dropdown chọn Chủ nuôi ở Frontend
    public function owners()
    {
        $owners = DB::table('owners')
            ->select('owner_id', 'ho_ten', 'phone')
            ->orderByDesc('owner_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $owners
        ], 200);
    }

    public function index()
    {
        $pets = $this->query()->orderByDesc('pets.pet_id')->get();
        return response()->json([
            'status' => 'success',
            'data'   => $pets->map(fn ($p) => $this->format($p))->values()
        ], 200);
    }

    public function store(Request $request)
    {
        $hasOwnerId = $request->filled('owner_id');

        $rules = [
            'name'           => 'required|string|max:50',
            'species'        => 'required|string|max:30',
            'breed'          => 'nullable|string|max:50',
            'gender'         => 'nullable|in:DUC,CAI,CHUA_XAC_DINH',
            'dob'            => 'nullable|date',
            'weight'         => 'required|numeric|min:0.1|max:999',
        ];

        if ($hasOwnerId) {
            $rules['owner_id'] = 'required|integer|min:1|exists:owners,owner_id';
        } else {
            $rules['ho_ten'] = 'required|string|max:100';
            $rules['phone']  = 'required|string|max:20';
            $rules['diachi'] = 'nullable|string|max:255';
            $rules['email']  = 'nullable|email|max:100';
        }

        $v = $request->validate($rules, [
            'owner_id.required'     => 'Vui lòng chọn chủ nuôi.',
            'owner_id.integer'      => 'ID chủ nuôi phải là số nguyên.',
            'owner_id.min'          => 'ID chủ nuôi phải lớn hơn 0.',
            'owner_id.exists'       => 'Chủ nuôi không tồn tại trong hệ thống.',
            'ho_ten.required'       => 'Vui lòng nhập họ tên chủ nuôi.',
            'phone.required'        => 'Vui lòng nhập số điện thoại chủ nuôi.',
            'name.required'         => 'Vui lòng nhập tên thú cưng.',
            'species.required'      => 'Vui lòng chọn loài thú cưng.',
            'weight.required'       => 'Vui lòng nhập cân nặng ban đầu.',
            'weight.min'            => 'Cân nặng phải tối thiểu 0.1 kg.',
        ]);

        try {
            return DB::transaction(function () use ($request, $v, $hasOwnerId) {
                // 1. Lấy hoặc tự động tạo mới Chủ nuôi nếu tiếp nhận khách mới
                if ($hasOwnerId) {
                    $ownerId = $v['owner_id'];
                } else {
                    $phone = trim($v['phone']);
                    $existingOwner = DB::table('owners')->where('phone', $phone)->first();

                    if ($existingOwner) {
                        $ownerId = $existingOwner->owner_id;
                    } else {
                        $ownerId = DB::table('owners')->insertGetId([
                            'ho_ten'  => trim($v['ho_ten']),
                            'phone'   => $phone,
                            'diachi'  => $v['diachi'] ?? null,
                            'email'   => $v['email'] ?? null,
                            'ngaytao' => now(),
                        ]);
                    }
                }

                // 2. Khởi tạo hồ sơ thú cưng
                $id = DB::table('pets')->insertGetId([
                    'mathucung'   => 'TMP' . uniqid(),
                    'owner_id'    => $ownerId,
                    'ten_thucung' => $v['name'],
                    'loai'        => $v['species'],
                    'giong'       => $v['breed'] ?? null,
                    'gioitinh'    => $v['gender'] ?? 'CHUA_XAC_DINH',
                    'ngaysinh'    => $v['dob'] ?? null,
                    'trangthai'   => 'NGOAI_TRU',
                ]);

                // 3. Tạo mã định danh duy nhất TC0001 (BR-01)
                DB::table('pets')->where('pet_id', $id)->update(['mathucung' => sprintf('TC%04d', $id)]);

                // 4. Ghi nhận cân nặng khởi tạo vào weight_logs (BR-04)
                DB::table('weight_logs')->insert([
                    'pet_id'  => $id,
                    'cannang' => $v['weight'],
                    'ngaydo'  => now(),
                    'nguoido' => $request->user()->user_id ?? 1,
                    'ghichu'  => 'Cân nặng khởi tạo hồ sơ',
                ]);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Thêm hồ sơ thú cưng thành công!',
                    'data'    => $this->format($this->findOrFail($id)),
                ], 201);
            });
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lỗi tạo hồ sơ thú cưng: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $pet = $this->findOrFail($id);

        $medicalHistory = DB::table('medical_records as mr')
            ->leftJoin('surgeries as s', 's.surgery_id', '=', 'mr.surgery_id')
            ->leftJoin('invoices as i', 'i.invoice_id', '=', 'mr.invoice_id')
            ->where('mr.pet_id', $id)
            ->select('mr.record_id', 'mr.ngaykham', 'mr.chandoan', 'mr.huong_xuly', 'mr.trangthai', 'mr.invoice_id', 'i.mahoadon', 's.ten_phauthuat')
            ->orderByDesc('mr.ngaykham')->orderByDesc('mr.record_id')->get();
        $latestExam = $medicalHistory->first();

        return response()->json([
            'status' => 'success',
            'data'   => array_merge($this->format($pet), [
                'medical_history' => $medicalHistory,
                'latest_exam' => $latestExam ? ['record_id' => $latestExam->record_id, 'trangthai' => $latestExam->trangthai, 'ngaykham' => $latestExam->ngaykham] : null,
            ]),
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $this->findOrFail($id);

        // Bác sĩ chỉ được cập nhật cân nặng phục vụ theo dõi lâm sàng.
        if (
            $request->user()
            && $request->user()->vaitro === 'BAC_SI'
            && array_diff(array_keys($request->all()), ['weight'])
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bác sĩ chỉ được cập nhật cân nặng của thú cưng tại chức năng này.',
            ], 403);
        }

        $v = $request->validate([
            'owner_id'       => 'sometimes|integer|min:1|exists:owners,owner_id',
            'name'           => 'sometimes|string|max:50',
            'species'        => 'sometimes|string|max:30',
            'breed'          => 'sometimes|nullable|string|max:50',
            'gender'         => 'sometimes|in:DUC,CAI,CHUA_XAC_DINH',
            'dob'            => 'sometimes|nullable|date',
            'weight'         => 'sometimes|numeric|min:0.1|max:999',
        ]);

        try {
            return DB::transaction(function () use ($request, $id, $v) {
                $map = [
                    'owner_id'       => 'owner_id',
                    'name'           => 'ten_thucung',
                    'species'        => 'loai',
                    'breed'          => 'giong',
                    'gender'         => 'gioitinh',
                    'dob'            => 'ngaysinh',
                ];

                $data = [];
                foreach ($map as $in => $col) {
                    if (array_key_exists($in, $v)) {
                        $data[$col] = $v[$in];
                    }
                }

                if ($data) {
                    DB::table('pets')->where('pet_id', $id)->update($data);
                }

                // Nếu có cập nhật cân nặng -> Thêm bản ghi mới vào weight_logs (BR-04)
                if (isset($v['weight'])) {
                    DB::table('weight_logs')->insert([
                        'pet_id'  => $id,
                        'cannang' => $v['weight'],
                        'ngaydo'  => now(),
                        'nguoido' => $request->user()->user_id ?? 1,
                        'ghichu'  => 'Cập nhật cân nặng mới',
                    ]);
                }

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Cập nhật hồ sơ thành công!',
                    'data'    => $this->format($this->findOrFail($id))
                ], 200);
            });
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Lỗi cập nhật: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $pet = $this->findOrFail($id);

        if ($pet->cage_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Thú cưng đang nội trú. Hãy làm thủ tục thanh toán và xuất viện trước.'
            ], 409);
        }

        try {
            DB::table('pets')->where('pet_id', $id)->delete();
        } catch (QueryException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Không thể xóa: thú cưng đã có hồ sơ khám hoặc hóa đơn.'
            ], 409);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Đã xóa hồ sơ thú cưng thành công!'
        ], 200);
    }
}