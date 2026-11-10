<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetBoarding extends Model
{
    protected $fillable = ['pet_id', 'cage_id', 'check_in', 'check_out', 'status'];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function cage()
    {
        return $this->belongsTo(Cage::class);
    }
}
