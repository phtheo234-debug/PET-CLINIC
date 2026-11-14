@extends('layouts.app')

@section('title', 'Thống kê doanh thu - Pet Clinic')
@section('page_title', 'Thống kê doanh thu')

@section('content')
<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-left border-success p-3 h-100">
            <small class="text-muted font-weight-bold">TỔNG DOANH THU</small>
            <h3 id="tongDoanhThu" class="text-success font-weight-bold mt-2 mb-0">0 đ</h3>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-left border-primary p-3 h-100">
            <small class="text-muted font-weight-bold">SỐ HÓA ĐƠN ĐÃ THANH TOÁN</small>
            <h3 id="soHoaDon" class="text-primary font-weight-bold mt-2 mb-0">0</h3>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-left border-info p-3 h-100">
            <small class="text-muted font-weight-bold">SỐ CA ĐÃ KHÁM</small>
            <h3 id="soCaKham" class="text-info font-weight-bold mt-2 mb-0">0 CA</h3>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
        <strong>Doanh thu theo ngày</strong>
        <div class="d-flex align-items-center mt-2 mt-md-0">
            <label for="chonNgay" class="mb-0 mr-2">Chọn ngày</label>
            <input type="date" id="chonNgay" class="form-control form-control-sm mr-2" style="width:auto">
            <button type="button" id="btnNgay" class="btn btn-sm btn-primary">Xem</button>
        </div>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-lg-5">
                <canvas id="chartDoanhThuNgay" height="220"></canvas>
                <div id="khongCoDoanhThuNgay" class="text-muted text-center d-none">Ngày này chưa có doanh thu.</div>
            </div>
            <div class="col-lg-7">
                <div class="d-flex justify-content-between border-bottom py-2"><span>Tổng doanh thu ngày</span><strong id="tongNgay">0 đ</strong></div>
                <div class="d-flex justify-content-between border-bottom py-2"><span>Hóa đơn đã thanh toán</span><strong id="hoaDonNgay">0</strong></div>
                <div class="d-flex justify-content-between py-2"><span>Ca đã khám</span><strong id="caKhamNgay">0 CA</strong></div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light"><tr><th>Loại phí</th><th class="text-right">Doanh thu</th></tr></thead>
                        <tbody id="bangTheoLoaiNgay"><tr><td colspan="2" class="text-center text-muted">Đang tải...</td></tr></tbody>
                    </table>
                </div>
                <div class="text-right mt-3">
                    <button type="button" id="xuatNgay" class="btn btn-sm btn-outline-success">Xuất phiếu</button>
                    <button type="button" id="inNgay" class="btn btn-sm btn-outline-dark">In phiếu</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
        <strong>Doanh thu theo tuần</strong>
        <div class="d-flex align-items-center mt-2 mt-md-0">
            <label for="chonTuan" class="mb-0 mr-2">Chọn một ngày trong tuần</label>
            <input type="date" id="chonTuan" class="form-control form-control-sm mr-2" style="width:auto">
            <button type="button" id="btnTuan" class="btn btn-sm btn-primary">Xem</button>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3"><span id="nhanTuan" class="text-muted"></span><strong>Tổng tuần: <span id="tongTuan">0 đ</span></strong></div>
        <canvas id="chartDoanhThuTuan" height="100"></canvas>
        <div class="text-right mt-3">
            <button type="button" id="xuatTuan" class="btn btn-sm btn-outline-success">Xuất phiếu</button>
            <button type="button" id="inTuan" class="btn btn-sm btn-outline-dark">In phiếu</button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
                <strong>Doanh thu theo tháng</strong>
                <div class="d-flex align-items-center mt-2 mt-md-0">
                    <input type="month" id="chonThang" value="{{ now()->format('Y-m') }}" class="form-control form-control-sm mr-2" style="width:auto">
                    <button type="button" id="btnThang" class="btn btn-sm btn-primary">Xem</button>
                </div>
            </div>
            <div class="card-body">
                <h4 class="text-success mb-3" id="tongThang">0 đ</h4>
                <div class="table-responsive"><table class="table table-sm table-hover">
                    <thead class="thead-light"><tr><th>Loại chi phí</th><th class="text-right">Tổng tiền</th></tr></thead>
                    <tbody id="bangTheoThang"><tr><td colspan="2" class="text-center text-muted">Đang tải...</td></tr></tbody>
                </table></div>
                <div class="text-right">
                    <button type="button" id="xuatThang" class="btn btn-sm btn-outline-success">Xuất phiếu</button>
                    <button type="button" id="inThang" class="btn btn-sm btn-outline-dark">In phiếu</button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
                <strong>Doanh thu theo năm</strong>
                <div class="d-flex align-items-center mt-2 mt-md-0">
                    <input type="number" id="chonNam" value="{{ now()->format('Y') }}" min="2000" max="2100" class="form-control form-control-sm mr-2" style="width:100px">
                    <button type="button" id="btnNam" class="btn btn-sm btn-primary">Xem</button>
                </div>
            </div>
            <div class="card-body">
                <h4 class="text-success mb-3" id="tongNam">0 đ</h4>
                <div class="table-responsive"><table class="table table-sm table-hover">
                    <thead class="thead-light"><tr><th>Loại chi phí</th><th class="text-right">Tổng tiền</th></tr></thead>
                    <tbody id="bangTheoNam"><tr><td colspan="2" class="text-center text-muted">Đang tải...</td></tr></tbody>
                </table></div>
                <div class="text-right">
                    <button type="button" id="xuatNam" class="btn btn-sm btn-outline-success">Xuất phiếu</button>
                    <button type="button" id="inNam" class="btn btn-sm btn-outline-dark">In phiếu</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold">
        <i class="fas fa-exclamation-triangle text-warning mr-1"></i> Các kho sắp hết hàng
        <small class="text-muted font-weight-normal ml-2">Mặt hàng có số lượng dưới 5</small>
    </div>
    <div class="card-body">
        <div id="danhSachKhoSapHet" class="row">
            <div class="col-12 text-muted">Đang tải danh sách kho...</div>
        </div>
        <div id="chiTietKhoSapHet" class="mt-3 d-none">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong id="tenKhoSapHet"></strong>
                <div>
                    <button type="button" id="xuatKhoSapHet" class="btn btn-sm btn-outline-success">Xuất phiếu</button>
                    <button type="button" id="inKhoSapHet" class="btn btn-sm btn-outline-dark">In phiếu</button>
                </div>
            </div>
            <div class="table-responsive"><table class="table table-sm table-bordered mb-0">
                <thead class="thead-light"><tr><th>Mã</th><th>Tên</th><th class="text-right">Số lượng</th></tr></thead>
                <tbody id="bangChiTietKhoSapHet"></tbody>
            </table></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-warning text-dark font-weight-bold">
                <i class="fas fa-clock mr-1"></i> Hàng sắp hết hạn (trong 6 tháng)
                <span class="badge badge-dark ml-2" id="countSapHetHan">0</span>
            </div>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                <thead class="thead-light"><tr><th>Sản phẩm</th><th>Lô</th><th>Tồn</th><th>Hạn dùng</th></tr></thead>
                <tbody id="tableSapHetHan"><tr><td colspan="4" class="text-center text-muted">Đang tải...</td></tr></tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-danger text-white font-weight-bold">
                <i class="fas fa-exclamation-triangle mr-1"></i> Hàng đã hết hạn
                <span class="badge badge-light ml-2" id="countDaHetHan">0</span>
            </div>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0">
                <thead class="thead-light"><tr><th>Sản phẩm</th><th>Lô</th><th>Tồn</th><th>Hết hạn</th><th></th></tr></thead>
                <tbody id="tableDaHetHan"><tr><td colspan="5" class="text-center text-muted">Đang tải...</td></tr></tbody>
            </table></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/admin-statistics.js') }}?v={{ filemtime(public_path('js/admin-statistics.js')) }}"></script>
@endpush
