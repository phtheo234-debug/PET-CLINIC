<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Hệ thống Quản lý Phòng khám Thú cưng')</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet"
          href="{{ asset('css/style.css') }}">
</head>

<body>

    @php
        $currentUser = request()->attributes->get('web_authenticated_user');
        $role = strtoupper(trim($currentUser->vaitro ?? ''));
    @endphp

    <div class="wrapper">

        {{-- SIDEBAR --}}
        <nav id="sidebar">

            <div class="sidebar-header">

                <h5 class="mb-0 font-weight-bold text-info">
                    <i class="fas fa-clinic-medical mr-2"></i>
                    PET CLINIC
                </h5>

                <small class="text-muted">
                    Hệ thống Quản lý Thú y
                </small>

            </div>

            <ul class="list-unstyled components">

                {{-- Thông tin tài khoản --}}
                <li class="{{ request()->routeIs('profile') ? 'active' : '' }}">
                    <a href="{{ route('profile') }}">
                        <i class="fas fa-user-cog"></i>
                        Thông tin tài khoản
                    </a>
                </li>


                {{-- Chỉ quản trị viên --}}
                @if($role === 'QUAN_TRI')

                    <li class="{{ request()->routeIs('admin.users') ? 'active' : '' }}">
                        <a href="{{ route('admin.users') }}">
                            <i class="fas fa-users-cog"></i>
                            Quản lý tài khoản
                        </a>
                    </li>

                    <li class="{{ request()->routeIs('admin.statistics') ? 'active' : '' }}">
                        <a href="{{ route('admin.statistics') }}">
                            <i class="fas fa-chart-pie"></i>
                            Thống kê doanh thu
                        </a>
                    </li>

                @endif


                {{-- Tổng quan --}}
                <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-chart-line"></i>
                        Tổng quan (Dashboard)
                    </a>
                </li>


                {{-- Chủ nuôi và thú cưng --}}
                <li class="{{ request()->routeIs('pets') ? 'active' : '' }}">
                    <a href="{{ route('pets') }}">
                        <i class="fas fa-paw"></i>
                        Chủ nuôi & Thú cưng
                    </a>
                </li>


                {{-- Lồng lưu trú: tất cả vai trò được xem --}}
                <li class="{{ request()->routeIs('cages') ? 'active' : '' }}">
                    <a href="{{ route('cages') }}">
                        <i class="fas fa-home"></i>
                        Lồng lưu trú nội trú
                    </a>
                </li>


                {{-- Bác sĩ và quản trị viên --}}
                @if(in_array($role, ['QUAN_TRI', 'BAC_SI'], true))

                    <li class="{{ request()->routeIs('medical') ? 'active' : '' }}">
                        <a href="{{ route('medical') }}">
                            <i class="fas fa-stethoscope"></i>
                            Khám bệnh & Kê đơn
                        </a>
                    </li>

                @endif


                {{-- Kho thuốc và Spa --}}
                <li class="{{ request()->routeIs('inventory') ? 'active' : '' }}">
                    <a href="{{ route('inventory') }}">
                        <i class="fas fa-pills"></i>
                        Kho thuốc & Spa
                    </a>
                </li>


                {{-- Nhân viên và quản trị viên --}}
                @if(in_array($role, ['QUAN_TRI', 'NHAN_VIEN'], true))

                    <li class="{{ request()->routeIs('invoices') ? 'active' : '' }}">
                        <a href="{{ route('invoices') }}">
                            <i class="fas fa-file-invoice-dollar"></i>
                            Thanh toán POS
                        </a>
                    </li>

                @endif

            </ul>

        </nav>


        {{-- NỘI DUNG CHÍNH --}}
        <div id="content">

            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">

                <span class="navbar-brand font-weight-bold text-dark mb-0">
                    @yield('page_title')
                </span>

                <div class="ml-auto d-flex align-items-center">

                    <span class="mr-3 text-secondary">

                        <i class="fas fa-user-circle mr-1 text-primary"></i>

                        <strong id="navUserName">
                            Đang tải...
                        </strong>

                        <span id="navUserRole"
                              class="badge badge-info ml-1">
                        </span>

                    </span>

                    <button onclick="logout()"
                            class="btn btn-outline-danger btn-sm">

                        <i class="fas fa-sign-out-alt"></i>
                        Đăng xuất

                    </button>

                </div>

            </nav>


            {{-- NỘI DUNG TỪ CÁC TRANG --}}
            <div class="container-fluid p-4">

                @yield('content')

            </div>

        </div>

    </div>


    {{-- JAVASCRIPT --}}
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="{{ asset('js/config.js') }}"></script>

    {{-- Script riêng của từng trang --}}
    @stack('scripts')

</body>
</html>
