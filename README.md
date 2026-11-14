# PET CLINIC

Hệ thống quản lý phòng khám thú cưng.

## Công nghệ sử dụng

- **PHP 8.2 trở lên**
- **Laravel 12**: xử lý nghiệp vụ, định tuyến, xác thực và API.
- **MySQL**: lưu trữ dữ liệu.
- **Bootstrap 4.6.2**: xây dựng giao diện.
- **CSS thuần**: tùy chỉnh giao diện trong `public/css/style.css`.
- **JavaScript thuần**: xử lý tương tác và gọi API bằng Fetch API.
- **jQuery**: hỗ trợ các modal của Bootstrap 4.
- **Font Awesome 5.15.4**: biểu tượng giao diện.
- **Chart.js 4.4.1**: biểu đồ thống kê doanh thu.

Giao diện sử dụng Bootstrap 4 qua CDN. Các trang nghiệp vụ tải CSS và JavaScript trực tiếp từ thư mục `public`, vì vậy dự án không cần Node.js, npm, Vite, Tailwind CSS hoặc Axios.

## Cài đặt và chạy dự án

### 1. Yêu cầu

- PHP 8.2 trở lên và các extension Laravel yêu cầu.
- Composer.
- MySQL.

### 2. Cài đặt thư viện PHP

```bash
composer install
```

### 3. Tạo file môi trường

Trên Windows (Command Prompt):

```bat
copy .env.example .env
```

Nếu đã có file `.env`, không cần tạo lại.

### 4. Cấu hình cơ sở dữ liệu

Mở file `.env` và chỉnh thông tin MySQL cho phù hợp:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pet_clinic
DB_USERNAME=root
DB_PASSWORD=
```

Tên cơ sở dữ liệu và tài khoản cần khớp với MySQL trên máy của bạn.

### 5. Tạo khóa ứng dụng và chạy migration

```bash
php artisan key:generate
php artisan migrate
```

Chỉ chạy migration trên cơ sở dữ liệu đã được chuẩn bị đúng. Không dùng `migrate:fresh` trên cơ sở dữ liệu đang có dữ liệu cần giữ.

### 6. Khởi động hệ thống

```bash
php artisan serve
```

Mở địa chỉ được Laravel hiển thị trong terminal, thường là `http://127.0.0.1:8000`.

Có thể dùng lệnh rút gọn sau để chạy máy chủ:

```bash
composer dev
```

## Cấu trúc giao diện

- `resources/views/`: các trang Blade.
- `public/css/style.css`: CSS tùy chỉnh.
- `public/js/`: JavaScript của từng chức năng.
- `public/js/config.js`: cấu hình và hàm gọi API dùng chung.

## Kiểm thử

```bash
php artisan test
```

## Ghi chú

- Không cần chạy `npm install`, `npm run dev` hoặc `npm run build`.
- Không chỉnh sửa trực tiếp dữ liệu thật bằng các lệnh xóa toàn bộ bảng.
- Các chức năng nghiệp vụ và API được giữ trong Laravel; Bootstrap chỉ phụ trách phần giao diện.
