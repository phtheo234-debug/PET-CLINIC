<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    protected $table = 'pets';
    protected $primaryKey = 'pet_id';
    public $timestamps = false;

    protected $fillable = [
        'mathucung',
        'owner_id',
        'cage_id',
        'ten_thucung',
        'loai',
        'giong',
        'gioitinh',
        'ngaysinh',
        'machip',
        'trangthai',
        'ngaytao',
    ];

    /**
     * Quan hệ N-1: Thú cưng thuộc về một Chủ nuôi
     */
    public function owner()
    {
        return $this->belongsTo(Owner::class, 'owner_id', 'owner_id');
    }

    /**
     * Quan hệ N-1: Thú cưng lưu trú tại một Lồng (khi nằm viện)
     */
    public function cage()
    {
        return $this->belongsTo(Cage::class, 'cage_id', 'cage_id');
    }

    /**
     * Quan hệ 1-N: Lịch sử theo dõi cân nặng thể trạng (BR-04)
     */
    public function weightLogs()
    {
        return $this->hasMany(WeightLog::class, 'pet_id', 'pet_id');
    }
}