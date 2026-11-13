@extends('layouts.app')

@section('title', 'Hồ sơ thú cưng - Pet Clinic')
@section('page_title', 'Hồ sơ thú cưng từ QR Code')

@section('content')
<div class="card shadow-sm mx-auto" style="max-width: 760px;">
    <div class="card-header bg-white font-weight-bold">
        <i class="fas fa-paw text-primary mr-2"></i>Thông tin thú cưng
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3"><small class="text-muted d-block">Mã thú cưng</small><strong>{{ $pet->mathucung }}</strong></div>
            <div class="col-md-6 mb-3"><small class="text-muted d-block">Tên thú cưng</small><strong>{{ $pet->ten_thucung }}</strong></div>
            <div class="col-md-6 mb-3"><small class="text-muted d-block">Loài / Giống</small><strong>{{ $pet->loai }}{{ $pet->giong ? ' / '.$pet->giong : '' }}</strong></div>
            <div class="col-md-6 mb-3"><small class="text-muted d-block">Giới tính</small><strong>{{ ['DUC' => 'Đực', 'CAI' => 'Cái', 'CHUA_XAC_DINH' => 'Chưa xác định'][$pet->gioitinh] ?? $pet->gioitinh }}</strong></div>
            <div class="col-md-6 mb-3"><small class="text-muted d-block">Ngày sinh</small><strong>{{ $pet->ngaysinh ?: 'Chưa cập nhật' }}</strong></div>
            <div class="col-md-6 mb-3"><small class="text-muted d-block">Cân nặng hiện tại</small><strong>{{ $pet->current_weight !== null ? $pet->current_weight.' kg' : 'Chưa cập nhật' }}</strong></div>
            <div class="col-md-6 mb-3"><small class="text-muted d-block">Trạng thái</small><strong>{{ $pet->trangthai }}</strong></div>
        </div>
        <hr>
        <h6 class="font-weight-bold text-primary mb-3">Thông tin chủ nuôi</h6>
        <div class="row">
            <div class="col-md-6 mb-2"><small class="text-muted d-block">Họ tên chủ nuôi</small><strong>{{ $pet->owner_name ?: 'Chưa cập nhật' }}</strong></div>
            <div class="col-md-6 mb-2"><small class="text-muted d-block">Số điện thoại chủ nuôi</small><a href="{{ $pet->owner_phone ? 'tel:'.$pet->owner_phone : '#' }}"><strong>{{ $pet->owner_phone ?: 'Chưa cập nhật' }}</strong></a></div>
        </div>
        <div class="alert alert-info mt-4 mb-0 small">Thông tin chỉ hiển thị sau khi người dùng đăng nhập vào hệ thống Pet Clinic.</div>
    </div>
</div>
@endsection
