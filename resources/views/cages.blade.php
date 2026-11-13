@extends('layouts.app')

@section('title', 'Lồng lưu trú - Pet Clinic')
@section('page_title', 'Quản lý Lồng Lưu trú & Nội trú')

@section('content')

{{-- =========================================
     1. YÊU CẦU LƯU TRÚ CHỜ XÁC NHẬN
========================================= --}}

<div class="card shadow-sm mb-4">

    <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">

        <span>
            <i class="fas fa-clipboard-list mr-1 text-primary"></i>
            YÊU CẦU LƯU TRÚ CHỜ XÁC NHẬN
        </span>

        <button type="button"
                class="btn btn-sm btn-outline-primary"
                onclick="loadBoardingRequests()">

            <i class="fas fa-sync-alt mr-1"></i>
            Làm mới

        </button>

    </div>

    <div class="card-body">

        <p class="text-muted small mb-3">
            Hệ thống tự tìm lồng theo chỉ định của bác sĩ.
            Nhân viên kiểm tra lồng được đề xuất và xác nhận;
            lồng chỉ được giữ sau khi xác nhận thành công.
        </p>

        <div class="table-responsive">

            <table class="table table-bordered table-hover mb-0">

                <thead class="thead-light">

                    <tr>
                        <th>Thú cưng</th>
                        <th>Chẩn đoán</th>
                        <th>Yêu cầu</th>
                        <th>Lồng hệ thống đề xuất</th>
                        <th>Thao tác</th>
                    </tr>

                </thead>

                <tbody id="boardingRequestsBody">

                    <tr>
                        <td colspan="5"
                            class="text-center text-muted">

                            Đang tải yêu cầu lưu trú...

                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

        <small class="form-text text-muted mt-2">

            Bác sĩ có thể xem đề xuất;
            chỉ quản trị viên và nhân viên được xác nhận xếp lồng.

        </small>

    </div>

</div>


{{-- =========================================
     2. NÚT THÊM LỒNG - CHỈ QUẢN TRỊ
========================================= --}}

@php
    $currentUser = request()->attributes->get('web_authenticated_user');

    $isAdminCage = $currentUser
        && strtoupper(trim($currentUser->vaitro ?? '')) === 'QUAN_TRI';
@endphp

@if($isAdminCage)

    <div class="d-flex justify-content-end mb-3"
         id="adminCageToolbar">

        <button type="button"
                class="btn btn-primary"
                onclick="openCageForm()">

            <i class="fas fa-plus mr-1"></i>
            Thêm lồng lưu trú

        </button>

    </div>

@else

    <div id="adminCageToolbar" class="d-none"></div>

@endif


{{-- =========================================
     3. DANH SÁCH LỒNG LƯU TRÚ
========================================= --}}

<div class="row" id="cagesGrid">

    <div class="col-12 text-center text-muted py-4">

        Đang tải danh sách lồng lưu trú...

    </div>

</div>


{{-- =========================================
     4. MODAL THÊM / SỬA LỒNG
========================================= --}}

@if($isAdminCage)

    <div class="modal fade"
         id="cageFormModal"
         tabindex="-1"
         role="dialog"
         aria-hidden="true">

        <div class="modal-dialog" role="document">

            <form id="cageForm" class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title font-weight-bold"
                        id="cageFormTitle">

                        Thêm lồng lưu trú

                    </h5>

                    <button type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Đóng">

                        <span>&times;</span>

                    </button>

                </div>


                <div class="modal-body">

                    <input type="hidden" id="cage_form_id">


                    <div class="form-group">

                        <label>Mã lồng</label>

                        <input type="text"
                               id="cage_malong"
                               class="form-control"
                               maxlength="20"
                               readonly>

                        <small class="form-text text-muted">

                            Mã được hệ thống tự động tạo khi lưu lồng mới.

                        </small>

                    </div>


                    <div class="form-group">

                        <label>
                            Tên lồng <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               id="cage_ten_long"
                               class="form-control"
                               maxlength="50"
                               required>

                    </div>


                    <div class="form-group">

                        <label>
                            Loại lồng <span class="text-danger">*</span>
                        </label>

                        <select id="cage_loailong"
                                class="form-control"
                                required>

                            <option value="THUONG">
                                Lồng thường
                            </option>

                            <option value="CAP_CUU_ICU">
                                Hậu phẫu / ICU
                            </option>

                            <option value="CACH_LY">
                                Cách ly truyền nhiễm
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Sức chứa tối đa
                            <span class="text-danger">*</span>
                        </label>

                        <input type="number"
                               id="cage_succhua"
                               class="form-control"
                               min="1"
                               max="100"
                               value="1"
                               required>

                    </div>


                    <div class="form-group">

                        <label>Trạng thái</label>

                        <select id="cage_trangthai"
                                class="form-control">

                            <option value="CON_TRONG">
                                Còn trống
                            </option>

                            <option value="DA_CO_THU">
                                Đang có thú (tự động)
                            </option>

                            <option value="DANG_VE_SINH">
                                Đang vệ sinh
                            </option>

                            <option value="BAO_TRI">
                                Bảo trì
                            </option>

                        </select>

                        <small class="form-text text-muted">

                            Trạng thái đang có thú được hệ thống
                            tự xác định theo số thú thực tế.

                        </small>

                    </div>

                </div>


                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">

                        Hủy

                    </button>

                    <button type="submit"
                            class="btn btn-primary"
                            id="btnSaveCage">

                        Lưu lồng

                    </button>

                </div>

            </form>

        </div>

    </div>

@endif

@endsection


{{-- =========================================
     5. NHÚNG JAVASCRIPT
========================================= --}}

@push('scripts')

<script src="{{ asset('js/cages.js') }}?v={{ filemtime(public_path('js/cages.js')) }}"></script>

@endpush
