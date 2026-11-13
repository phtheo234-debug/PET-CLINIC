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
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'mat_khau' => static::$password ??= Hash::make('123456'),
            'ho_ten' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('09########'),
            'vaitro' => 'NHAN_VIEN',
            'trangthai' => 'HOAT_DONG',
            'phai_doimatkhau' => true,
            'ngaytao' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'vaitro' => 'QUAN_TRI',
        ]);
    }

    public function doctor(): static
    {
        return $this->state(fn (array $attributes) => [
            'vaitro' => 'BAC_SI',
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'vaitro' => 'NHAN_VIEN',
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'trangthai' => 'KHOA',
        ]);
    }

    public function passwordConfirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'phai_doimatkhau' => false,
        ]);
    }
}