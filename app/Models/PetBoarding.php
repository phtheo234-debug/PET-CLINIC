<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetBoarding extends Model
{
    protected $table = 'pet_boardings';
    protected $primaryKey = 'id'; // Hoặc 'boarding_id' tùy theo file Migration của dự án
    public $timestamps = true;

    protected $fillable = [
        'pet_id',
        'cage_id',
        'check_in',
        'check_out',
    ];

    protected $casts = [
        'check_in'  => 'datetime',
        'check_out' => 'datetime',
    ];

    /**
     * Quan hệ N-1: Đợt lưu trú thuộc về một Thú cưng
     */
    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id', 'pet_id');
    }

    /**
     * Quan hệ N-1: Đợt lưu trú thuộc về một Lồng lưu trú
     */
    public function cage()
    {
        return $this->belongsTo(Cage::class, 'cage_id', 'cage_id');
    }
}