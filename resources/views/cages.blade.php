@extends('layouts.app')

@section('title', 'Lồng lưu trú - Pet Clinic')
@section('page_title', 'Quản lý Lồng Lưu trú & Nội trú')

@section('content')
<div class="row" id="cagesGrid">
    <div class="col-12 text-center text-muted py-4">Đang tải danh sách lồng lưu trú...</div>
</div>

<!-- Modal Xếp thú cưng vào lồng -->
<div class="modal fade" id="assignCageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="assignCageForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">
                    Xếp Thú cưng vào Lồng <span id="modalCageCode" class="text-primary"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="selected_cage_id">
                <div class="form-group">
                    <label>Chọn Thú cưng cần lưu trú <span class="text-danger">*</span></label>
                    <select id="assign_pet_id" class="form-control" required>
                        <option value="">-- Đang tải danh sách thú cưng --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lý do lưu trú / Ghi chú</label>
                    <input type="text" id="assign_note" class="form-control" placeholder="VD: Lưu viện theo dõi hậu phẫu...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" id="btnConfirmAssign" class="btn btn-primary">
                    <i class="fas fa-check mr-1"></i> Xác nhận xếp lồng
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/cages.js') }}"></script>
@endpush