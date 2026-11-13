@extends('layouts.app')

@section('title', 'Dashboard - Pet Clinic')
@section('page_title', 'Tổng quan Hoạt động Phòng khám')

@section('content')
<div class="row">
    <div class="col-md-3 mb-3">
        <div class="card stat-card border-left border-primary p-3">
            <small class="text-muted font-weight-bold">SỐ CA KHÁM HÔM NAY</small>
            <h3 id="so_ca_kham" class="text-primary font-weight-bold mt-2">0</h3>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card border-left border-warning p-3">
            <small class="text-muted font-weight-bold">THÚ CƯNG NỘI TRÚ</small>
            <h3 id="so_thu_noi_tru" class="text-warning font-weight-bold mt-2">0</h3>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card border-left border-info p-3">
            <small class="text-muted font-weight-bold">LỒNG CÒN TRỐNG</small>
            <h3 id="so_long_trong" class="text-info font-weight-bold mt-2">0</h3>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card border-left border-success p-3">
            <small class="text-muted font-weight-bold">DOANH THU NGÀY</small>
            <h3 id="doanh_thu_ngay" class="text-success font-weight-bold mt-2">0 đ</h3>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard.js') }}"></script>
@endpush