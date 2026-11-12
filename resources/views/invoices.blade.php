@extends('layouts.app')

@section('title', 'Thanh toán POS & Xuất viện - Pet Clinic')
@section('page_title', 'Thanh toán Viện phí POS & Xuất viện')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold text-success">
        <i class="fas fa-cash-register mr-1"></i> LẬP HÓA ĐƠN THANH TOÁN TỔNG HỢP (POS)
    </div>
    <div class="card-body">
        <form id="invoiceForm">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Thú cưng</label>
                    <input type="text" id="inv_pet" class="form-control" placeholder="PET-001" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Chủ nuôi</label>
                    <input type="text" id="inv_owner" class="form-control" placeholder="Nguyễn Văn An" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Phương thức thanh toán</label>
                    <select id="phuongthucthanhtoan" class="form-control">
                        <option value="TIEN_MAT">Tiền mặt</option>
                        <option value="CHUYEN_KHOAN">Chuyển khoản QR</option>
                        <option value="THE">Quẹt thẻ</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Tiền khám / Phẫu thuật (đ)</label>
                    <input type="number" id="fee_medical" class="form-control"
                        value="150000" min="0">
                </div>

                <div class="form-group col-md-3">
                    <label>Tiền thuốc / Vắc-xin (đ)</label>
                    <input type="number" id="fee_medicine" class="form-control"
                        value="0" min="0">
                </div>

                <div class="form-group col-md-3">
                    <label>Tiền lồng lưu trú (đ)</label>
                    <input type="number" id="fee_cage" class="form-control"
                        value="0" min="0">
                </div>

                <div class="form-group col-md-3">
                    <label>Dịch vụ Spa / Hạt mua kèm (đ)</label>
                    <input type="number" id="fee_spa" class="form-control"
                        value="0" min="0">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
                <h5 class="mb-0">Tổng thanh toán: <span id="tongtien_display" class="text-danger font-weight-bold">150.000 đ</span></h5>
                <button type="submit" class="btn btn-success font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> Thanh toán & Duyệt Xuất viện
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white font-weight-bold">Danh sách Hóa đơn đã xuất</div>
    <div class="card-body table-responsive">
        <table class="table table-hover">
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
            <tbody id="invoicesTableBody"></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/invoices.js') }}"></script>
@endpush