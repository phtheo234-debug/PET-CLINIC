@extends('layouts.app')

@section('title', 'Chủ nuôi & Thú cưng - Pet Clinic')
@section('page_title', 'Quản lý Chủ nuôi & Hồ sơ Thú cưng')

@section('content')
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <input type="text" id="searchPet" class="form-control w-50" placeholder="Tìm theo mã thú cưng, tên, SĐT chủ nuôi...">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPetModal">
            <i class="fas fa-plus mr-1"></i> Tiếp nhận Thú cưng
        </button>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Mã TC</th>
                    <th>Tên & Giống loài</th>
                    <th>Chủ nuôi</th>
                    <th>Mã Chip / QR</th>
                    <th>Cân nặng</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="petsTableBody">
                <tr>
                    <td colspan="7" class="text-center text-muted">Đang tải danh sách thú cưng...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tiếp nhận Chủ nuôi & Thú cưng Mới -->
<div class="modal fade" id="addPetModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form id="petForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Tiếp nhận Chủ nuôi & Thú cưng Mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
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
                        <select id="loai" class="form-control">
                            <option value="Chó">Chó</option>
                            <option value="Mèo">Mèo</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Giống</label>
                        <input type="text" id="giong" class="form-control" placeholder="VD: Poodle">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Giới tính</label>
                        <select id="gioitinh" class="form-control">
                            <option value="DUC">Đực</option>
                            <option value="CAI">Cái</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Cân nặng (kg) <span class="text-danger">*</span></label>
                        <input type="number" step="0.1" min="0.1" max="200" id="can_nang" class="form-control" placeholder="VD: 3.5" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Mã Microchip</label>
                        <input type="text" id="machip" class="form-control" placeholder="Để trống nếu chưa có">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" id="btnSavePet" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Lưu Hồ sơ
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Modal Cập nhật Cân nặng Thú cưng -->
<div class="modal fade" id="weightModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <form id="weightForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">
                    Cân thú cưng <span id="weightPetCode" class="text-primary"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="weight_pet_id">
                <div class="form-group mb-0">
                    <label>Cân nặng mới (kg) <span class="text-danger">*</span></label>
                    <input type="number" step="0.1" min="0.1" max="200" id="new_weight" class="form-control" placeholder="VD: 3.5" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Hủy</button>
                <button type="submit" id="btnSaveWeight" class="btn btn-info btn-sm">
                    <i class="fas fa-check mr-1"></i> Lưu cân nặng
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pets.js') }}"></script>
@endpush