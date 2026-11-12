<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Pet Clinic System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h4 class="mb-1"><i class="fas fa-clinic-medical mr-2"></i>PET CLINIC SYSTEM</h4>
                        <small>Đăng nhập hệ thống quản lý phòng khám thú y</small>
                    </div>
                    <div class="card-body p-4">
                        <div id="loginAlert" class="alert alert-danger d-none"></div>
                        <form id="loginForm">
                            <div class="form-group">
                                <label class="font-weight-bold" for="username">Tên đăng nhập</label>
                                <input type="text" id="username" class="form-control" placeholder="Nhập username..." required>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" for="mat_khau">Mật khẩu</label>
                                <div class="input-group">
                                    <input type="password" id="mat_khau" class="form-control" placeholder="Nhập mật khẩu..." autocomplete="current-password" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('mat_khau', this)" aria-label="Hiện mật khẩu" title="Hiện mật khẩu"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" id="btnSubmitLogin" class="btn btn-primary btn-block font-weight-bold">
                                <i class="fas fa-sign-in-alt mr-1"></i> Đăng nhập hệ thống
                            </button>
                        </form>
                        <div class="text-center mt-3"><a href="{{ route('forgot-password') }}">Quên mật khẩu?</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/config.js') }}"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
