<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cage extends Model
{
    protected $table = 'cages';
    protected $primaryKey = 'cage_id';
    public $timestamps = false;

    protected $fillable = [
        'malong',
        'ten_long',
        'loailong',
        'succhua',
        'soluonghientai',
        'trangthai',
        'loai_phu_hop',
        'tai_trong_toi_da',
    ];

    /**
     * Quan hệ: Một lồng có thể chứa nhiều thú cưng (tối đa theo succhua)
     */
    public function pets()
    {
        return $this->hasMany(Pet::class, 'cage_id', 'cage_id');
    }
}