@extends('layouts.app')

@section('title', 'Lồng lưu trú - Pet Clinic')
@section('page_title', 'Quản lý Lồng Lưu trú & Nội trú')

@section('content')
<div class="d-flex justify-content-end mb-3 d-none" id="adminCageToolbar">
    <button type="button" class="btn btn-primary" onclick="openCageForm()">
        <i class="fas fa-plus mr-1"></i> Thêm lồng lưu trú
    </button>
</div>

<div class="row" id="cagesGrid">
    <div class="col-12 text-center text-muted py-4">Đang tải danh sách lồng lưu trú...</div>
</div>

<!-- Modal Thêm / sửa lồng (chỉ quản trị viên) -->
<div class="modal fade" id="cageFormModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="cageForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="cageFormTitle">Thêm lồng lưu trú</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cage_form_id">
                <div class="form-group">
                    <label>Mã lồng <span class="text-danger">*</span></label>
                    <input type="text" id="cage_malong" class="form-control" maxlength="20" required>
                </div>
                <div class="form-group">
                    <label>Tên lồng <span class="text-danger">*</span></label>
                    <input type="text" id="cage_ten_long" class="form-control" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label>Loại lồng <span class="text-danger">*</span></label>
                    <select id="cage_loailong" class="form-control" required>
                        <option value="THUONG">Lồng thường</option>
                        <option value="CAP_CUU_ICU">Hậu phẫu / ICU</option>
                        <option value="CACH_LY">Cách ly truyền nhiễm</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Sức chứa tối đa <span class="text-danger">*</span></label>
                    <input type="number" id="cage_succhua" class="form-control" min="1" max="100" value="1" required>
                </div>
                <div class="form-group" id="cageStatusGroup">
                    <label>Trạng thái</label>
                    <select id="cage_trangthai" class="form-control">
                        <option value="CON_TRONG">Còn trống</option>
                        <option value="DA_CO_THU">Đang có thú (tự động)</option>
                        <option value="DANG_VE_SINH">Đang vệ sinh</option>
                        <option value="BAO_TRI">Bảo trì</option>
                    </select>
                    <small class="form-text text-muted">Trạng thái đang có thú được hệ thống tự xác định theo số thú thực tế.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary" id="btnSaveCage">Lưu lồng</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Xếp thú cưng vào lồng -->
<div class="modal fade" id="assignCageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="assignCageForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">
                    Xếp Thú cưng vào Lồng <span id="modalCageCode" class="text-primary"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="selected_cage_id">
                <div class="form-group">
                    <label>Chọn Thú cưng cần lưu trú <span class="text-danger">*</span></label>
                    <select id="assign_pet_id" class="form-control" required>
                        <option value="">-- Đang tải danh sách thú cưng --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lý do lưu trú / Ghi chú</label>
                    <input type="text" id="assign_note" class="form-control" placeholder="VD: Lưu viện theo dõi hậu phẫu...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" id="btnConfirmAssign" class="btn btn-primary">
                    <i class="fas fa-check mr-1"></i> Xác nhận xếp lồng
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/cages.js') }}"></script>
@endpush