@extends('layouts.app')

@section('title', 'Thanh toán POS & Xuất viện - Pet Clinic')
@section('page_title', 'Thanh toán Viện phí POS & Xuất viện')

@section('content')
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
                </tr>
            </thead>
            <tbody id="invoicesTableBody">
                <tr><td colspan="7" class="text-center text-muted">Đang tải danh sách hóa đơn...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/invoices.js') }}"></script>
@endpush
