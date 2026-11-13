@extends('layouts.app')

@section('title', 'Lồng lưu trú - Pet Clinic')
@section('page_title', 'Quản lý Lồng Lưu trú & Nội trú')

@section('content')
<div class="d-flex justify-content-end mb-3 d-none" id="adminCageToolbar">
    <button type="button" class="btn btn-primary" onclick="openCageForm()">
        <i class="fas fa-plus mr-1"></i> Thêm lồng lưu trú
    </button>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-md-6 mb-0">
                <label for="cageSearch">Tìm kiếm lồng / thú cưng</label>
                <input type="search" id="cageSearch" class="form-control"
                    placeholder="Nhập mã lồng, tên lồng, mã hoặc tên thú cưng...">
            </div>
            <div class="form-group col-md-3 mb-0">
                <label for="cageStatusFilter">Trạng thái</label>
                <select id="cageStatusFilter" class="form-control">
                    <option value="">Tất cả trạng thái</option>
                    <option value="CON_TRONG">Còn trống</option>
                    <option value="DA_CO_THU">Đang có thú</option>
                    <option value="DANG_VE_SINH">Đang vệ sinh</option>
                    <option value="BAO_TRI">Bảo trì</option>
                </select>
            </div>
            <div class="form-group col-md-3 mb-0">
                <label for="cageTypeFilter">Loại lồng</label>
                <select id="cageTypeFilter" class="form-control">
                    <option value="">Tất cả loại lồng</option>
                    <option value="THUONG">Lồng thường</option>
                    <option value="CAP_CUU_ICU">Hậu phẫu / ICU</option>
                    <option value="CACH_LY">Cách ly</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="row" id="cagesGrid">
    <div class="col-12 text-center text-muted py-4">Đang tải danh sách lồng lưu trú...</div>
</div>

<div class="card shadow-sm mt-4" id="cageWaitingCard">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong class="text-warning"><i class="fas fa-clock mr-1"></i> Danh sách chờ xếp lồng</strong>
        <span class="badge badge-warning" id="waitingCount">0</span>
    </div>
    <div class="card-body table-responsive">
        <div id="waitingAdminNotice" class="alert alert-warning d-none">
            Có thú cưng đang chờ lồng phù hợp. Vui lòng kiểm tra nhu cầu và bổ sung lồng nếu cần.
        </div>
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Mã thú cưng</th>
                    <th>Tên</th>
                    <th>Loài</th>
                    <th>Cân nặng</th>
                    <th>Yêu cầu</th>
                    <th>Thời gian chờ</th>
                </tr>
            </thead>
            <tbody id="cageWaitingBody">
                <tr><td colspan="6" class="text-center text-muted">Đang tải danh sách chờ...</td></tr>
            </tbody>
        </table>
    </div>
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
                    <label>Mã lồng</label>
                    <input type="text" id="cage_malong" class="form-control" maxlength="20" readonly>
                    <small class="form-text text-muted">Mã được hệ thống tự động tạo khi lưu lồng mới; mã đã cấp không thay đổi.</small>
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
                    <label>Loài phù hợp</label>
                    <select id="cage_loai_phu_hop" class="form-control">
                        <option value="TAT_CA">Tất cả loài</option>
                        <option value="CHO">Chó</option>
                        <option value="MEO">Mèo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tải trọng tối đa (kg)</label>
                    <input type="number" id="cage_tai_trong_toi_da" class="form-control" min="0.1" max="9999" step="0.1" placeholder="Để trống nếu không giới hạn">
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