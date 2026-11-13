@extends('layouts.app')

@section('title', 'Khám bệnh & Kê đơn - Pet Clinic')
@section('page_title', 'Khám bệnh Lâm sàng, Kê đơn & Tiêm phòng')

@section('content')
@php
    $medicalCurrentRole = strtoupper(trim($medicalUser->vaitro ?? ''));
    $isMedicalDoctor = $medicalCurrentRole === 'BAC_SI';
@endphp
@if($isMedicalDoctor)
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold text-danger"><i class="fas fa-clipboard-list mr-1"></i> DANH SÁCH CHỜ KHÁM</div>
    <div class="card-body table-responsive">
        <p class="text-muted small">Ca có ghi chú ưu tiên sẽ được xếp lên đầu. Bác sĩ nhấn Chọn để tiếp nhận từng ca.</p>
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light"><tr><th>Thú cưng</th><th>Chủ nuôi</th><th>Triệu chứng</th><th>Ghi chú ưu tiên</th><th>Trạng thái</th><th>Thời gian chờ</th><th></th></tr></thead>
            <tbody id="medicalQueueBody"><tr><td colspan="7" class="text-center text-muted">Đang tải danh sách chờ...</td></tr></tbody>
        </table>
    </div>
</div>
@endif
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold text-primary">
        <i class="fas fa-stethoscope mr-1"></i> TẠO PHIẾU KHÁM BỆNH LÂM SÀNG
    </div>
    <div class="card-body">
        <form id="medicalForm">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Thú cưng thăm khám <span class="text-danger">*</span></label>
                    <select id="pet_select" class="form-control" required>
                        <option value="">-- Đang tải danh sách thú cưng --</option>
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label class="text-danger font-weight-bold">
                        Cân nặng lúc khám (kg) *
                    </label>

                    <input
                        type="number"
                        step="0.1"
                        min="0.1"
                        max="200"
                        id="cannangkhi_kham"
                        class="form-control border-danger"
                        placeholder="Chọn thú cưng để lấy cân nặng"
                        required
                    >

                    <small class="form-text text-muted">
                        Tự động lấy cân nặng hiện tại. Bác sĩ có thể điều chỉnh theo kết quả cân thực tế.
                    </small>

                    <small id="doseSuggestion" class="text-info">
                        Nhập cân nặng để tự động tính liều mg/kg
                    </small>
                </div>
                <div class="form-group col-md-4">
                    <label class="font-weight-bold">Hướng xử lý / Kết luận</label>
                    <select id="huong_xuly" class="form-control">
                        <option value="OUTPATIENT">1. Điều trị ngoại trú (Kê đơn về nhà)</option>
                        <option value="INPATIENT">2. Nhập viện nội trú (Tạo yêu cầu lưu trú)</option>
                        <option value="SURGERY">3. Chỉ định Phẫu thuật (Tạo yêu cầu lồng ICU)</option>
                        <option value="TRANSFER">4. Điều hướng Chuyển viện tuyến trên</option>
                    </select>
                </div>
            </div>
            <div class="form-row d-none" id="isolationOption">
                <div class="form-group col-md-12 mb-2">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="yeu_cau_cach_ly">
                        <label class="custom-control-label font-weight-bold text-danger" for="yeu_cau_cach_ly">
                            Yêu cầu lồng cách ly
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        Chọn khi thú cưng có nguy cơ lây nhiễm. Hệ thống chỉ đề xuất lồng cách ly còn trống.
                    </small>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Triệu chứng lâm sàng <span class="text-danger">*</span></label>
                    <textarea id="trieuchung" class="form-control" rows="2" placeholder="VD: Bỏ ăn, sốt nhẹ..." required></textarea>
                </div>
                <div class="form-group col-md-4">
                    <label>Chẩn đoán bệnh <span class="text-danger">*</span></label>
                    <textarea id="chandoan" class="form-control" rows="2" placeholder="VD: Viêm đường hô hấp trên..." required></textarea>
                </div>
                <div class="form-group col-md-12 d-none" id="medicalSurgerySection">
                    <label class="font-weight-bold text-danger">Loại phẫu thuật <span class="text-danger">*</span></label>
                    <select id="medicalSurgerySelect" class="form-control">
                        <option value="">-- Chọn loại phẫu thuật --</option>
                    </select>
                    <small class="form-text text-muted">Giá phẫu thuật được lấy từ danh mục phẫu thuật của phòng khám.</small>
                </div>
                <div class="form-group col-md-12 d-none" id="medicalInventorySection">
                    <label class="font-weight-bold">Thuốc / Vắc-xin sử dụng từ kho</label>
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-4 mb-2">
                            <label for="medicalInventorySearch">Mặt hàng trong kho</label>
                            <input type="text" id="medicalInventorySearch" class="form-control" list="medicalInventorySuggestions" placeholder="Nhập tên hoặc mã thuốc/vắc-xin..." autocomplete="off">
                            <datalist id="medicalInventorySuggestions"></datalist>
                            <select id="medicalInventorySelect" class="d-none" aria-hidden="true" tabindex="-1"><option value="">-- Chọn thuốc hoặc vắc-xin --</option></select>
                            <small class="form-text text-muted">Gõ tên hoặc mã để xem các mặt hàng liên quan còn trong kho.</small>
                        </div>
                        <div class="form-group col-md-2 mb-2"><label for="medicalInventoryQty">Số lượng</label><input type="number" id="medicalInventoryQty" class="form-control" min="1" step="1" value="1"></div>
                        <div class="form-group col-md-4 mb-2"><label for="medicalInventoryNote">Liều dùng / Hướng dẫn</label><input type="text" id="medicalInventoryNote" class="form-control" maxlength="1000" placeholder="VD: 1 viên/lần, ngày 2 lần..."></div>
                        <div class="form-group col-md-2 mb-2"><button type="button" id="btnAddMedicalItem" class="btn btn-outline-primary btn-block"><i class="fas fa-plus mr-1"></i>Thêm</button></div>
                    </div>
                    <div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr><th>Loại</th><th>Tên mặt hàng</th><th>Số lượng</th><th>Hướng dẫn</th><th></th></tr></thead><tbody id="medicalItemsBody"><tr><td colspan="5" class="text-center text-muted">Chưa chọn thuốc/vắc-xin</td></tr></tbody></table></div>
                    <small class="form-text text-muted">Chỉ chọn thuốc/vắc-xin đã có trong kho và còn tồn. Khi lưu phiếu, hệ thống tự xuất kho theo hạn sử dụng gần nhất (FEFO).</small>
                </div>
            </div>
            <button type="submit" id="btnSaveMedical" class="btn btn-primary mr-2"><i class="fas fa-save mr-1"></i> Lưu Phiếu Khám</button>
            <button type="button" id="btnPrintCurrentMedical" class="btn btn-outline-success" disabled><i class="fas fa-print mr-1"></i> In phiếu khám</button>
        </form>
    </div>
</div>

@php
    $medicalUser = request()->attributes->get('web_authenticated_user');
    $isMedicalAdmin = $medicalUser && strtoupper(trim($medicalUser->vaitro ?? '')) === 'QUAN_TRI';
@endphp
@if($isMedicalAdmin)
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-scalpel mr-1"></i> Danh mục phẫu thuật</span>
        <button type="button" class="btn btn-sm btn-primary" onclick="openSurgeryForm()"><i class="fas fa-plus mr-1"></i>Thêm loại phẫu thuật</button>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light"><tr><th>Mã</th><th>Tên phẫu thuật</th><th>Giá (VNĐ)</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody id="surgeryCatalogBody"><tr><td colspan="5" class="text-center text-muted">Đang tải danh mục...</td></tr></tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="surgeryFormModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="surgeryForm" class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="surgeryFormTitle">Thêm loại phẫu thuật</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <input type="hidden" id="surgery_form_id">
                <div class="form-group"><label>Mã phẫu thuật</label><input id="surgery_code" class="form-control" maxlength="30" required></div>
                <div class="form-group"><label>Tên phẫu thuật</label><input id="surgery_name" class="form-control" maxlength="100" required></div>
                <div class="form-group"><label>Giá (VNĐ)</label><input type="number" id="surgery_price" class="form-control" min="0" step="1000" required></div>
                <div class="form-group"><label>Trạng thái</label><select id="surgery_status" class="form-control"><option value="1">Đang áp dụng</option><option value="0">Ngừng áp dụng</option></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary" id="btnSaveSurgery">Lưu</button></div>
        </form>
    </div>
</div>
@endif

<div class="card shadow-sm">
    <div class="card-header bg-white font-weight-bold">Lịch sử Khám bệnh gần đây</div>
    <div class="card-body table-responsive">
        <table class="table table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Thú cưng</th>
                    <th>Cân nặng</th>
                    <th>Triệu chứng</th>
                    <th>Chẩn đoán</th>
                    <th>Hướng xử lý</th>
                    <th>Thời gian</th>
                    <th>Phiếu khám</th>
                </tr>
            </thead>
            <tbody id="medicalHistoryBody">
                <tr><td colspan="7" class="text-center text-muted">Đang tải lịch sử khám...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/medical.js') }}?v={{ filemtime(public_path('js/medical.js')) }}"></script>
@endpush