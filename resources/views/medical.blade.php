@extends('layouts.app')

@section('title', 'Khám bệnh & Kê đơn - Pet Clinic')
@section('page_title', 'Khám bệnh Lâm sàng, Kê đơn & Tiêm phòng')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold text-primary">
        <i class="fas fa-stethoscope mr-1"></i> TẠO PHIẾU KHÁM BỆNH LÂM SÀNG
    </div>
    <div class="card-body">
        <form id="medicalForm">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Thú cưng thăm khám <span class="text-danger">*</span></label>
                    <select id="pet_select" class="form-control" required>
                        <option value="">-- Đang tải danh sách thú cưng --</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label class="text-danger font-weight-bold">Cân nặng lúc khám (kg) *</label>
                    <input type="number" step="0.1" min="0.1" max="200" id="cannangkhi_kham" class="form-control border-danger" placeholder="VD: 4.5" required>
                    <small id="doseSuggestion" class="text-info">Nhập cân nặng để tự động tính liều mg/kg</small>
                </div>
                <div class="form-group col-md-4">
                    <label class="font-weight-bold">Hướng xử lý / Kết luận</label>
                    <select id="huong_xuly" class="form-control">
                        <option value="OUTPATIENT">1. Điều trị ngoại trú (Kê đơn về nhà)</option>
                        <option value="INPATIENT">2. Nhập viện nội trú (Tạo yêu cầu lưu trú)</option>
                        <option value="SURGERY">3. Chỉ định Phẫu thuật (Tạo yêu cầu lồng ICU)</option>
                        <option value="TRANSFER">4. Điều hướng Chuyển viện tuyến trên</option>
                    </select>
                </div>
            </div>
            <div class="form-row d-none" id="isolationOption">
                <div class="form-group col-md-12 mb-2">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="yeu_cau_cach_ly">
                        <label class="custom-control-label font-weight-bold text-danger" for="yeu_cau_cach_ly">
                            Yêu cầu lồng cách ly
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        Chọn khi thú cưng có nguy cơ lây nhiễm. Hệ thống chỉ đề xuất lồng cách ly còn trống.
                    </small>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Triệu chứng lâm sàng <span class="text-danger">*</span></label>
                    <textarea id="trieuchung" class="form-control" rows="2" placeholder="VD: Bỏ ăn, sốt nhẹ..." required></textarea>
                </div>
                <div class="form-group col-md-4">
                    <label>Chẩn đoán bệnh <span class="text-danger">*</span></label>
                    <textarea id="chandoan" class="form-control" rows="2" placeholder="VD: Viêm đường hô hấp trên..." required></textarea>
                </div>
                <div class="form-group col-md-4">
                    <label>Đơn thuốc / Vắc-xin chỉ định</label>
                    <textarea id="don_thuoc" class="form-control" rows="2" placeholder="Tên thuốc, liều lượng, số ngày..."></textarea>
                </div>
            </div>
            <button type="submit" id="btnSaveMedical" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Lưu Phiếu Khám
            </button>
        </form>
    </div>
</div>

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
                </tr>
            </thead>
            <tbody id="medicalHistoryBody">
                <tr><td colspan="6" class="text-center text-muted">Đang tải lịch sử khám...</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/medical.js') }}"></script>
@endpush