@extends('layouts.app')

@section('title', 'Kho thuốc & Dịch vụ Spa - Pet Clinic')
@section('page_title', 'Quản lý Kho Thuốc (FEFO) & Dịch vụ Spa')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold text-primary">
        Kho Thuốc, Vắc-xin & Thức ăn (Kiểm soát Lô & Hạn sử dụng FEFO)
    </div>
    <div class="card-body table-responsive">
        <table class="table table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th>Danh mục</th>
                    <th>Số lô</th>
                    <th>Tồn kho</th>
                    <th>Hạn sử dụng</th>
                    <th>Đơn giá</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody"></tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white font-weight-bold text-info">
        Bảng giá Dịch vụ Spa & Grooming
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Mã DV</th>
                    <th>Tên gói dịch vụ</th>
                    <th>Đơn giá</th>
                </tr>
            </thead>
            <tbody id="spaTableBody"></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/inventory.js') }}"></script>
@endpush