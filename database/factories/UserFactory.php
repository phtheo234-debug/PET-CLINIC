
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Mật khẩu mặc định dùng cho tài khoản được tạo bởi Factory.
     */
    protected static ?string $password = null;

    /**
     * Định nghĩa dữ liệu mẫu cho bảng users.
     */
    public function definition(): array
    {
        return [
            // Thông tin đăng nhập
            'username' => fake()->unique()->userName(),
            'mat_khau' => static::$password ??= Hash::make('123456'),

            // Thông tin nhân viên
            'ho_ten' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('09########'),

            // Vai trò mặc định
            'vaitro' => 'NHAN_VIEN',

            // Trạng thái tài khoản
            'trangthai' => 'HOAT_DONG',

            // Bắt buộc đổi mật khẩu khi đăng nhập lần đầu
            'phai_doimatkhau' => true,

            // Thời điểm tạo tài khoản
            'ngaytao' => now(),
        ];
    }

    /**
     * Tạo tài khoản quản trị viên.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'vaitro' => 'QUAN_TRI',
        ]);
    }

    /**
     * Tạo tài khoản bác sĩ.
     */
    public function doctor(): static
    {
        return $this->state(fn (array $attributes) => [
            'vaitro' => 'BAC_SI',
        ]);
    }

    /**
     * Tạo tài khoản nhân viên.
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'vaitro' => 'NHAN_VIEN',
        ]);
    }

    /**
     * Tạo tài khoản đang bị khóa.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'trangthai' => 'KHOA',
        ]);
    }

    /**
     * Tạo tài khoản không cần đổi mật khẩu lần đầu.
     */
    public function passwordConfirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'phai_doimatkhau' => false,
        ]);
    }
}
