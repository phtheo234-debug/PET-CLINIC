<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    protected $table = 'owners';
    protected $primaryKey = 'owner_id';
    public $timestamps = false;

    protected $fillable = [
        'ho_ten',
        'phone',
        'diachi',
        'email',
        'ngaytao',
    ];

    /**
     * Quan hệ 1-N: Một chủ nuôi sở hữu nhiều thú cưng
     */
    public function pets()
    {
        return $this->hasMany(Pet::class, 'owner_id', 'owner_id');
    }
}