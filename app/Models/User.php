<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false; // CSDL dùng cột ngaytao

    protected $fillable = [
        'username',
        'mat_khau',
        'ho_ten',
        'email',
        'phone',
        'vaitro',
        'trangthai',
        'phai_doimatkhau',
        'ngaytao',
    ];

    protected $hidden = [
        'mat_khau',
    ];

    protected $casts = [
        'phai_doimatkhau' => 'boolean',
    ];

    public function getAuthPasswordName()
    {
        return 'mat_khau';
    }
}