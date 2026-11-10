<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cage extends Model
{
    protected $fillable = ['code', 'size_type', 'max_weight', 'status'];
}
