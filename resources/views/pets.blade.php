@extends('layouts.app')

@section('title', 'Chủ nuôi & Thú cưng - Pet Clinic')
@section('page_title', 'Quản lý Chủ nuôi & Hồ sơ Thú cưng')

@section('content')
@php
    $currentUser = request()->attributes->get('web_authenticated_user');
    $currentRole = strtoupper(trim($currentUser->vaitro ?? ''));
    $isDoctor = $currentRole === 'BAC_SI';
@endphp

<script>
    window.isDoctor = @json($isDoctor);
</script>
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div class="input-group w-50"><input type="text" id="searchPet" class="form-control" placeholder="Tìm theo mã thú cưng, tên, SĐT chủ nuôi..."><div class="input-group-append"><button type="button" id="btnSearchPet" class="btn btn-outline-primary"><i class="fas fa-search mr-1"></i>Tìm</button></div></div>
        <div class="d-flex align-items-center">
            <button type="button" id="btnOpenVaccinationSchedule" class="btn btn-outline-info mr-2" data-toggle="modal" data-target="#vaccinationScheduleModal">
                <i class="fas fa-syringe mr-1"></i> Lịch tiêm chủng
            </button>
            <button type="button" id="btnOpenAddPet" class="btn btn-primary d-none" data-toggle="modal" data-target="#addPetModal">
                <i class="fas fa-plus mr-1"></i> Tiếp nhận Thú cưng
            </button>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr><th>Mã TC</th><th>Tên & Giống loài</th><th>Chủ nuôi</th><th>Cân nặng</th><th>Trạng thái lưu trú</th><th>Trạng thái khám</th><th>Thao tác</th></tr>
            </thead>
            <tbody id="petsTableBody">
                <tr><td colspan="7" class="text-center text-muted">Đang tải danh sách thú cưng...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-white font-weight-bold">Danh sách chủ nuôi</div>
    <div class="card-body table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr><th>Họ tên</th><th>Số điện thoại</th><th>Email</th><th>Địa chỉ</th><th>Thao tác</th></tr></thead><tbody id="ownersTableBody"><tr><td colspan="5" class="text-center text-muted">Đang tải...</td></tr></tbody></table></div>
</div>
<div class="modal fade" id="editOwnerModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form id="editOwnerForm">
<div class="modal-header"><h5 class="modal-title">Cập nhật chủ nuôi</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><input type="hidden" id="editOwnerId"><div class="form-group"><label>Họ tên</label><input id="editOwnerName" class="form-control" maxlength="100" required></div>
<div class="form-group"><label>Số điện thoại</label><input id="editOwnerPhone" class="form-control" maxlength="20" required></div>
<div class="form-group"><label>Email</label><input id="editOwnerEmail" type="email" class="form-control" maxlength="100"></div>
<div class="form-group"><label>Địa chỉ</label><input id="editOwnerAddress" class="form-control" maxlength="255"></div></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu thay đổi</button></div>
</form></div></div></div>
<div class="modal fade" id="editPetModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form id="editPetForm">
<div class="modal-header"><h5 class="modal-title">Cập nhật hồ sơ thú cưng</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body"><input type="hidden" id="editPetId"><input type="hidden" id="editPetOwnerId">
<h6 class="font-weight-bold text-primary">Thông tin thú cưng</h6><div class="form-row">
<div class="form-group col-md-6"><label>Tên thú cưng</label><input id="editPetName" class="form-control" maxlength="50" required></div>
<div class="form-group col-md-6"><label>Loài</label><select id="editPetSpecies" class="form-control"><option>Chó</option><option>Mèo</option><option>Khác</option></select></div>
<div class="form-group col-md-6"><label>Giống</label><input id="editPetBreed" class="form-control" maxlength="50"></div>
<div class="form-group col-md-6"><label>Giới tính</label><select id="editPetGender" class="form-control"><option value="DUC">Đực</option><option value="CAI">Cái</option><option value="CHUA_XAC_DINH">Chưa xác định</option></select></div>
<div class="form-group col-md-6"><label>Ngày sinh</label><input type="date" id="editPetDob" class="form-control"></div></div>
<hr><h6 class="font-weight-bold text-primary">Thông tin chủ nuôi liên kết</h6>
<div class="form-row"><div class="form-group col-md-6"><label>Họ tên chủ nuôi</label><input id="editPetOwnerName" class="form-control" maxlength="100" required></div>
<div class="form-group col-md-6"><label>Số điện thoại</label><input id="editPetOwnerPhone" class="form-control" maxlength="20" required></div>
<div class="form-group col-md-6"><label>Email</label><input id="editPetOwnerEmail" type="email" class="form-control" maxlength="100"></div>
<div class="form-group col-md-6"><label>Địa chỉ</label><input id="editPetOwnerAddress" class="form-control" maxlength="255"></div></div>
<small class="text-muted">Thông tin chủ nuôi sẽ được cập nhật cho tất cả thú cưng cùng thuộc chủ nuôi này.</small></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu thay đổi</button></div>
</form></div></div></div>
<div class="modal fade" id="addPetModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="petForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Tiếp nhận Chủ nuôi & Thú cưng Mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Họ tên Chủ nuôi <span class="text-danger">*</span></label>
                        <input type="text" id="ho_ten_chu" class="form-control" placeholder="VD: Nguyễn Văn An" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" id="phone" class="form-control" pattern="[0-9]{10,11}" placeholder="VD: 0987654321" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Mã Thú cưng (Tự động) <span class="text-danger">*</span></label>
                        <input type="text" id="mathucung" class="form-control bg-light font-weight-bold text-primary" readonly required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Tên Thú cưng <span class="text-danger">*</span></label>
                        <input type="text" id="ten_thucung" class="form-control" placeholder="VD: Lu, Miu..." required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Loài <span class="text-danger">*</span></label>
                        <select id="loai" class="form-control"><option value="Chó">Chó</option><option value="Mèo">Mèo</option><option value="Khác">Khác</option></select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Giống</label>
                        <input type="text" id="giong" class="form-control" placeholder="VD: Poodle">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Giới tính</label>
                        <select id="gioitinh" class="form-control">
                            <option value="DUC">Đực</option>
                            <option value="CAI">Cái</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Ngày sinh</label>
                        <input type="date" id="ngaysinh" class="form-control">
                        <small class="form-text text-muted">Có thể để trống nếu không rõ ngày sinh.</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Cân nặng (kg) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" min="0.1" max="200" id="can_nang" class="form-control" placeholder="VD: 3.5" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" id="btnSavePet" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu Hồ sơ</button>
            </div>
        </form>
    </div>
</div>


<div class="modal fade" id="vaccinationScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title font-weight-bold">Lịch tiêm chủng toàn phòng khám</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="table-responsive"><table class="table table-sm table-bordered"><thead class="thead-light"><tr><th>Mã thú cưng</th><th>Thú cưng</th><th>Chủ nuôi</th><th>Vắc-xin</th><th>Ngày đã tiêm</th><th>Ngày hẹn tiếp</th><th>Trạng thái</th></tr></thead><tbody id="vaccinationScheduleTable"><tr><td colspan="7" class="text-center text-muted">Đang tải lịch...</td></tr></tbody></table></div>
                <small class="text-muted">Trạng thái sắp đến hạn được tính trong 30 ngày tiếp theo. Ngày hẹn do nhân viên/bác sĩ nhập, hệ thống không tự đặt chu kỳ tiêm.</small>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addMedicalQueueModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="addMedicalQueueForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-user-clock text-primary mr-2"></i>Đưa thú cưng vào chờ khám</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="queuePetId">
                <div class="alert alert-light border mb-3">
                    <div class="small text-muted">Thú cưng được chọn</div>
                    <strong id="queuePetName">—</strong>
                </div>
                <div class="form-group mb-0">
                    <label for="queuePetNote">Ghi chú khám <span class="text-muted font-weight-normal">(không bắt buộc)</span></label>
                    <textarea id="queuePetNote" class="form-control" rows="3" maxlength="1000" placeholder="Ví dụ: Ưu tiên khám gấp, bỏ ăn, khó thở..."></textarea>
                    <small class="form-text text-muted">Có thể để trống nếu không có lưu ý đặc biệt.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button type="submit" id="btnConfirmAddMedicalQueue" class="btn btn-danger"><i class="fas fa-check mr-1"></i>Xác nhận</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="petDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Chi tiết thú cưng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" id="petDetailContent">
                <div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải thông tin...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pets.js') }}?v={{ filemtime(public_path('js/pets.js')) }}"></script>
@endpush
