<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu lần đầu - Pet Clinic</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow border-0">
                    <div class="card-header bg-warning text-dark text-center py-4">
                        <h4 class="mb-1"><i class="fas fa-key mr-2"></i>ĐỔI MẬT KHẨU LẦN ĐẦU</h4>
                        <small>Vui lòng thay mật khẩu tạm thời để tiếp tục sử dụng hệ thống</small>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info">
                            Vì lý do bảo mật, bạn cần đổi mật khẩu được cấp trước khi truy cập các chức năng của Pet Clinic.
                        </div>
                        <div id="changePasswordAlert" class="alert alert-danger d-none" role="alert"></div>
                        <form id="changePasswordForm">
                            <div class="form-group">
                                <label class="font-weight-bold" for="mat_khau_cu">Mật khẩu hiện tại (mật khẩu tạm)</label>
                                <div class="input-group">
                                    <input type="password" id="mat_khau_cu" class="form-control" autocomplete="current-password" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('mat_khau_cu', this)" aria-label="Hiện mật khẩu" title="Hiện mật khẩu"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" for="mat_khau_moi">Mật khẩu mới</label>
                                <div class="input-group">
                                    <input type="password" id="mat_khau_moi" class="form-control" minlength="6" autocomplete="new-password" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('mat_khau_moi', this)" aria-label="Hiện mật khẩu" title="Hiện mật khẩu"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Mật khẩu phải có ít nhất 6 ký tự.</small>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" for="mat_khau_moi_confirmation">Nhập lại mật khẩu mới</label>
                                <div class="input-group">
                                    <input type="password" id="mat_khau_moi_confirmation" class="form-control" minlength="6" autocomplete="new-password" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('mat_khau_moi_confirmation', this)" aria-label="Hiện mật khẩu" title="Hiện mật khẩu"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" id="btnChangePassword" class="btn btn-primary btn-block font-weight-bold">
                                <i class="fas fa-save mr-1"></i> Xác nhận đổi mật khẩu
                            </button>
                            <button type="button" class="btn btn-link btn-block text-muted" onclick="logout()">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/config.js') }}"></script>
    <script src="{{ asset('js/change-password.js') }}"></script>
</body>
</html>
