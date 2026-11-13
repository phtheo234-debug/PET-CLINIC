<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ thú cưng - Pet Clinic</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f4f7fb; color: #273449; min-height: 100vh; }
        .qr-header { background: #fff; border-bottom: 1px solid #e8edf4; }
        .brand { color: #1685a8; font-weight: 700; letter-spacing: .3px; }
        .profile-card { border: 0; border-radius: 16px; overflow: hidden; }
        .profile-card .card-header { background: #fff; border-bottom: 1px solid #edf0f5; padding: 1.25rem 1.5rem; }
        .info-label { color: #7b8798; font-size: .85rem; display: block; margin-bottom: .25rem; }
        .info-value { font-weight: 600; overflow-wrap: anywhere; }
        .owner-panel { background: #f0f8fc; border: 1px solid #dceef5; border-radius: 12px; padding: 1.25rem; }
        .phone-link { color: #087da5; font-weight: 700; }
        .footer-note { color: #8792a2; font-size: .85rem; }
    </style>
</head>
<body>
    <header class="qr-header py-3 mb-4">
        <div class="container d-flex align-items-center">
            <span class="brand"><i class="fas fa-paw mr-2"></i>PET CLINIC</span>
            <span class="text-muted ml-auto small">Hồ sơ thú cưng qua QR</span>
        </div>
    </header>

    <main class="container pb-5" style="max-width: 820px;">
        <div class="card profile-card shadow-sm">
            <div class="card-header">
                <h4 class="mb-1"><i class="fas fa-paw text-info mr-2"></i>Thông tin thú cưng</h4>
                <p class="text-muted mb-0 small">Thông tin được chia sẻ qua mã QR của thú cưng.</p>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-sm-6 mb-4">
                        <span class="info-label">Mã thú cưng</span>
                        <span class="info-value">{{ $pet->mathucung ?: 'Chưa cập nhật' }}</span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <span class="info-label">Tên thú cưng</span>
                        <span class="info-value">{{ $pet->ten_thucung ?: 'Chưa cập nhật' }}</span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <span class="info-label">Loài / Giống</span>
                        <span class="info-value">{{ $pet->loai ?: 'Chưa cập nhật' }}{{ $pet->giong ? ' / '.$pet->giong : '' }}</span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <span class="info-label">Giới tính</span>
                        <span class="info-value">{{ ['DUC' => 'Đực', 'CAI' => 'Cái', 'CHUA_XAC_DINH' => 'Chưa xác định'][$pet->gioitinh] ?? ($pet->gioitinh ?: 'Chưa cập nhật') }}</span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <span class="info-label">Ngày sinh</span>
                        <span class="info-value">{{ $pet->ngaysinh ?: 'Chưa cập nhật' }}</span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <span class="info-label">Cân nặng hiện tại</span>
                        <span class="info-value">{{ $pet->current_weight !== null ? $pet->current_weight.' kg' : 'Chưa cập nhật' }}</span>
                    </div>
                    <div class="col-sm-6 mb-4">
                        <span class="info-label">Trạng thái</span>
                        <span class="info-value">{{ $pet->trangthai ?: 'Chưa cập nhật' }}</span>
                    </div>
                </div>

                <div class="owner-panel mt-2">
                    <h5 class="mb-3"><i class="fas fa-user mr-2 text-info"></i>Thông tin chủ nuôi</h5>
                    <div class="row align-items-center">
                        <div class="col-sm-6 mb-3 mb-sm-0">
                            <span class="info-label">Họ tên chủ nuôi</span>
                            <span class="info-value">{{ $pet->owner_name ?: 'Chưa cập nhật' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="info-label">Số điện thoại chủ nuôi</span>
                            @if($pet->owner_phone)
                                <a class="phone-link" href="tel:{{ $pet->owner_phone }}"><i class="fas fa-phone-alt mr-1"></i>{{ $pet->owner_phone }}</a>
                            @else
                                <span class="info-value">Chưa cập nhật</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4 mb-0 small">
                    <i class="fas fa-info-circle mr-1"></i>
                    Trang này có thể xem công khai khi quét đúng mã QR. Chủ nuôi không cần tạo tài khoản hệ thống.
                </div>
            </div>
        </div>
        <p class="footer-note text-center mt-3 mb-0">Pet Clinic · Hệ thống quản lý phòng khám thú cưng</p>
    </main>
</body>
</html>
