@extends('layouts.app')

@section('title', 'Thanh toán POS & Xuất viện - Pet Clinic')
@section('page_title', 'Thanh toán Viện phí POS & Xuất viện')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold text-info">
        <i class="fas fa-spa mr-1"></i> GHI NHẬN DỊCH VỤ SPA ĐÃ SỬ DỤNG
    </div>
    <div class="card-body">
        <p class="text-muted small">Chỉ ghi nhận cho thú cưng đang nội trú. Chi phí sẽ được cộng vào hóa đơn khi thanh toán và xuất viện.</p>
        <form id="spaUsageForm">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="spa_usage_pet">Thú cưng <span class="text-danger">*</span></label>
                    <select id="spa_usage_pet" class="form-control" required>
                        <option value="">-- Chọn thú cưng --</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="spa_usage_service">Dịch vụ Spa <span class="text-danger">*</span></label>
                    <select id="spa_usage_service" class="form-control" required>
                        <option value="">-- Chọn dịch vụ --</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="spa_usage_quantity">Số lần <span class="text-danger">*</span></label>
                    <input id="spa_usage_quantity" type="number" class="form-control" min="1" step="1" value="1" required>
                </div>
                <div class="form-group col-md-2">
                    <label>Đơn giá</label>
                    <input id="spa_usage_price" class="form-control bg-light" readonly placeholder="Tự động">
                </div>
            </div>
            <div class="form-group">
                <label for="spa_usage_note">Ghi chú</label>
                <input id="spa_usage_note" class="form-control" maxlength="255" placeholder="Ghi chú tình trạng hoặc nội dung đã thực hiện">
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" id="btnSaveSpaUsage" class="btn btn-info">
                    <i class="fas fa-save mr-1"></i> Ghi nhận dịch vụ đã sử dụng
                </button>
            </div>
        </form>
        <div id="spaUsageResult" class="mt-3" aria-live="polite"></div>
        <div class="table-responsive mt-3">
            <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light">
                    <tr><th>Thú cưng</th><th>Dịch vụ</th><th>Số lần</th><th>Thành tiền</th><th>Trạng thái hóa đơn</th><th>Nhân viên</th><th>Ngày ghi nhận</th></tr>
                </thead>
                <tbody id="spaUsageTableBody">
                    <tr><td colspan="7" class="text-center text-muted">Đang tải lịch sử dịch vụ...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold text-primary"><i class="fas fa-receipt mr-1"></i> THANH TOÁN LƯỢT KHÁM NGOẠI TRÚ</div>
    <div class="card-body">
        <p class="text-muted small">Mỗi phiếu khám đã hoàn thành được tính phí khám 50.000 đồng/lần, cộng thêm thuốc/vắc-xin đã xuất từ kho nếu có. Không thực hiện xuất viện tại mục này.</p>
        <form id="outpatientVisitForm">
            <div class="form-row align-items-end">
                <div class="form-group col-md-6"><label for="outpatient_record_select">Phiếu khám chưa thanh toán</label><select id="outpatient_record_select" class="form-control" required><option value="">-- Đang tải phiếu khám --</option></select></div>
                <div class="form-group col-md-3"><label for="outpatient_payment_method">Phương thức thanh toán</label><select id="outpatient_payment_method" class="form-control"><option value="TIEN_MAT">Tiền mặt</option><option value="CHUYEN_KHOAN">Chuyển khoản</option><option value="THE">Thẻ</option></select></div>
                <div class="form-group col-md-3"><button type="submit" id="btnPayOutpatient" class="btn btn-primary btn-block"><i class="fas fa-check mr-1"></i>Thanh toán lượt khám</button></div>
            </div>
        </form>
        <div id="outpatientPaymentResult" class="mt-2" aria-live="polite"></div>
    </div>
</div>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold text-success">
        <i class="fas fa-cash-register mr-1"></i> THANH TOÁN VÀ DUYỆT XUẤT VIỆN
    </div>
    <div class="card-body">
        <form id="invoiceForm">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="pet_select">Thú cưng nội trú <span class="text-danger">*</span></label>
                    <select id="pet_select" class="form-control" required>
                        <option value="">-- Đang tải danh sách thú cưng --</option>
                    </select>
                    <small class="form-text text-muted">
                        Chỉ chọn thú cưng đang nội trú. Backend sẽ kiểm tra hồ sơ khám và điều kiện xuất viện trước khi thanh toán.
                    </small>
                </div>
                <div class="form-group col-md-6">
                    <label for="inv_owner">Chủ nuôi</label>
                    <input type="text" id="inv_owner" class="form-control bg-light" placeholder="Tự động hiển thị theo thú cưng" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="phuongthucthanhtoan">Phương thức thanh toán <span class="text-danger">*</span></label>
                    <select id="phuongthucthanhtoan" class="form-control" required>
                        <option value="TIEN_MAT">Tiền mặt</option>
                        <option value="CHUYEN_KHOAN">Chuyển khoản</option>
                        <option value="THE">Thẻ</option>
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Chi phí thanh toán</label>
                    <div class="form-control bg-light text-muted" aria-live="polite">
                        Backend tự tính tiền thuốc chưa thanh toán và phí lưu trú theo số ngày thực tế.
                    </div>
                </div>
            </div>

            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle mr-1"></i>
                Không nhập tổng tiền thủ công. Hóa đơn và chi tiết từng khoản phí sẽ được Backend tính toán, lưu lại khi thanh toán thành công.
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" id="btnCheckout" class="btn btn-success font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> Thanh toán & Duyệt Xuất viện
                </button>
            </div>
        </form>
        <div id="checkoutResult" class="mt-3" aria-live="polite"></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white font-weight-bold">Danh sách Hóa đơn đã xuất</div>
    <div class="card-body table-responsive">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Mã HĐ</th>
                    <th>Thú cưng</th>
                    <th>Chủ nuôi</th>
                    <th>Tổng tiền</th>
                    <th>Thanh toán</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>In hóa đơn</th>
                </tr>
            </thead>
            <tbody id="invoicesTableBody">
                <tr><td colspan="8" class="text-center text-muted">Đang tải danh sách hóa đơn...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/invoices.js') }}"></script>
@endpush
