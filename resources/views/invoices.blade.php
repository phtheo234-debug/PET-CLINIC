@extends('layouts.app')
@section('title', 'Thanh toán - Pet Clinic')
@section('page_title', 'Thanh toán & Lập hóa đơn')
@section('content')
<div class="card shadow-sm mb-3">
 <div class="card-header bg-white font-weight-bold text-primary">BẢNG 1. TÌM KIẾM HỒ SƠ THÚ CƯNG</div>
 <div class="card-body"><div class="form-row align-items-end">
  <div class="form-group col-md-8"><label for="paymentPetSearch">Tìm theo mã hoặc tên thú cưng</label><input id="paymentPetSearch" class="form-control" list="paymentPetSuggestions" placeholder="Nhập mã TC hoặc tên thú cưng..." autocomplete="off"><datalist id="paymentPetSuggestions"></datalist><small class="form-text text-muted">Chọn một gợi ý trong danh sách.</small></div>
  <div class="form-group col-md-4"><label for="paymentOwnerName">Tên chủ nuôi</label><input id="paymentOwnerName" class="form-control bg-light" readonly placeholder="Tự động hiển thị"></div>
 </div><div id="paymentPetInfo" class="text-muted small">Chưa chọn hồ sơ thú cưng.</div></div>
</div>
<div class="card shadow-sm mb-3 d-none" id="medicalChargesSection">
 <div class="card-header bg-white font-weight-bold text-info">BẢNG 2. CHI PHÍ KHÁM VÀ ĐIỀU TRỊ</div>
 <div class="card-body"><div class="table-responsive"><table class="table table-bordered table-sm"><thead class="thead-light"><tr><th>Khoản thu</th><th>Số lượng / số ngày</th><th>Đơn giá (VND)</th><th>Thành tiền (VND)</th></tr></thead><tbody id="medicalChargesBody"></tbody></table></div><div class="text-right font-weight-bold">Tổng chi phí khám và điều trị: <span id="medicalChargesTotal">0 ₫</span></div></div>
</div>
<div class="card shadow-sm mb-3 d-none" id="extraServicesSection">
 <div class="card-header bg-white font-weight-bold text-warning">BẢNG 3. DỊCH VỤ NGOÀI KHÁM</div>
 <div class="card-body"><div class="form-row align-items-end">
  <div class="form-group col-md-3"><label for="extraCategory">Danh mục</label><select id="extraCategory" class="form-control"><option value="DICH_VU_SPA">Dịch vụ</option><option value="THUC_AN">Thức ăn</option><option value="DO_DUNG">Đồ dùng</option></select></div>
  <div class="form-group col-md-4"><label for="extraItem">Sản phẩm / dịch vụ</label><select id="extraItem" class="form-control"><option value="">-- Chọn --</option></select></div>
  <div class="form-group col-md-2"><label for="extraQuantity">Số lượng</label><input type="number" id="extraQuantity" class="form-control" min="1" step="1" value="1"></div>
  <div class="form-group col-md-3"><label>Đơn giá (VND)</label><input id="extraUnitPrice" class="form-control bg-light" readonly value="0 ₫"></div>
 </div><div class="text-right mb-3"><button type="button" id="btnAddExtraItem" class="btn btn-outline-warning"><i class="fas fa-plus mr-1"></i>Thêm vào hóa đơn</button></div>
 <div class="table-responsive"><table class="table table-bordered table-sm"><thead class="thead-light"><tr><th>Danh mục</th><th>Tên sản phẩm / dịch vụ</th><th>Số lượng</th><th>Đơn giá (VND)</th><th>Thành tiền (VND)</th><th></th></tr></thead><tbody id="extraItemsBody"><tr><td colspan="6" class="text-center text-muted">Chưa chọn dịch vụ ngoài khám.</td></tr></tbody></table></div>
</div>
<div class="card shadow-sm mb-4 d-none" id="finalPaymentSection">
 <div class="card-header bg-white font-weight-bold text-success">BẢNG 4. TỔNG HỢP VÀ XÁC NHẬN THANH TOÁN</div>
 <div class="card-body"><div class="table-responsive"><table class="table table-bordered table-sm"><thead class="thead-light"><tr><th>Khoản phải trả</th><th>Số lượng</th><th>Đơn giá (VND)</th><th>Thành tiền (VND)</th></tr></thead><tbody id="finalChargesBody"></tbody><tfoot><tr><th colspan="3" class="text-right">TỔNG CỘNG</th><th class="text-success" id="finalGrandTotal">0 ₫</th></tr></tfoot></table></div>
 <div class="form-row align-items-end"><div class="form-group col-md-4"><label for="finalPaymentMethod">Phương thức thanh toán</label><select id="finalPaymentMethod" class="form-control"><option value="TIEN_MAT">Tiền mặt</option><option value="CHUYEN_KHOAN">Chuyển khoản</option><option value="THE">Quẹt thẻ</option></select></div><div class="form-group col-md-8 text-right"><button type="button" id="btnPaymentSuccess" class="btn btn-success btn-lg"><i class="fas fa-check-circle mr-1"></i>Thanh toán thành công</button></div></div>
 <small class="text-muted">Chỉ bấm nút sau khi xác nhận chủ nuôi đã trả tiền. Mã hóa đơn được tạo tại thời điểm xác nhận.</small><div id="unifiedPaymentResult" class="mt-3" aria-live="polite"></div><div id="invoicePrintAction" class="mt-3 d-none"><button type="button" id="btnPrintNewInvoice" class="btn btn-primary"><i class="fas fa-print mr-1"></i>In hóa đơn</button></div></div>
</div>
<div class="card shadow-sm"><div class="card-header bg-white font-weight-bold">Danh sách hóa đơn đã xuất</div><div class="card-body table-responsive"><table class="table table-hover mb-0"><thead class="thead-light"><tr><th>Mã HĐ</th><th>Thú cưng</th><th>Chủ nuôi</th><th>Tổng tiền</th><th>Thanh toán</th><th>Ngày tạo</th><th>In hóa đơn</th></tr></thead><tbody id="invoicesTableBody"><tr><td colspan="7" class="text-center text-muted">Đang tải danh sách hóa đơn...</td></tr></tbody></table></div></div>
@endsection
@push('scripts')
<script src="{{ asset('js/invoices.js') }}?v={{ filemtime(public_path('js/invoices.js')) }}"></script>
@endpush
