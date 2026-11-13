@extends('layouts.app')

@section('title', 'Thống kê doanh thu - Pet Clinic')
@section('page_title', 'Thống kê doanh thu')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="form-row align-items-end">
            <div class="col-md-4 mb-2">
                <label for="tu_ngay">Từ ngày</label>
                <input type="date" id="tu_ngay" class="form-control">
            </div>
            <div class="col-md-4 mb-2">
                <label for="den_ngay">Đến ngày</label>
                <input type="date" id="den_ngay" class="form-control">
            </div>
            <div class="col-md-4 mb-2">
                <button type="button" id="btnThongKe" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Xem thống kê
                </button>
                <button type="button" id="btnThangNay" class="btn btn-outline-secondary">
                    Tháng này
                </button>
            </div>
        </div>
        <small id="thongKeError" class="text-danger"></small>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-left border-success p-3 h-100">
            <small class="text-muted font-weight-bold">TỔNG DOANH THU</small>
            <h3 id="tongDoanhThu" class="text-success font-weight-bold mt-2">0 đ</h3>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-left border-primary p-3 h-100">
            <small class="text-muted font-weight-bold">SỐ HÓA ĐƠN ĐÃ THANH TOÁN</small>
            <h3 id="soHoaDon" class="text-primary font-weight-bold mt-2">0</h3>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-left border-info p-3 h-100">
            <small class="text-muted font-weight-bold">TRUNG BÌNH / HÓA ĐƠN</small>
            <h3 id="doanhThuTrungBinh" class="text-info font-weight-bold mt-2">0 đ</h3>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold">Doanh thu theo ngày</div>
    <div class="card-body">
        <canvas id="chartDoanhThu" height="100"></canvas>
        <div id="khongCoDoanhThuNgay" class="text-muted text-center d-none">Không có dữ liệu trong khoảng thời gian này.</div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white font-weight-bold">Doanh thu theo loại chi phí</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr><th>Loại chi phí</th><th class="text-right">Số hóa đơn</th><th class="text-right">Doanh thu</th></tr>
                    </thead>
                    <tbody id="bangTheoLoai">
                        <tr><td colspan="3" class="text-center text-muted">Đang tải...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white font-weight-bold">Chi tiết doanh thu từng ngày</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr><th>Ngày</th><th class="text-right">Hóa đơn</th><th class="text-right">Doanh thu</th></tr>
                    </thead>
                    <tbody id="bangTheoNgay">
                        <tr><td colspan="3" class="text-center text-muted">Đang tải...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/admin-statistics.js') }}"></script>
@endpush
