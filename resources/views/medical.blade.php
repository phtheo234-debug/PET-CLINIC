@extends('layouts.app')

@section('title', 'Khám bệnh & Kê đơn - Pet Clinic')
@section('page_title', 'Khám bệnh Lâm sàng, Kê đơn & Tiêm phòng')

@section('content')
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