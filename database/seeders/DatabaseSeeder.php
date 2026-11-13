<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tạo tài khoản quản trị viên mẫu
        User::factory()->admin()->create([
            'username' => 'admin',
            'ho_ten' => 'Quản trị viên',
            'email' => 'admin@petclinic.com',
            'mat_khau' => bcrypt('123456'),
        ]);

        // Tạo tài khoản bác sĩ mẫu
        User::factory()->doctor()->create();

        // Tạo 3 tài khoản nhân viên mẫu
        User::factory(3)->staff()->create();
    }
}
