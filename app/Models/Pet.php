<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    protected $fillable = ['owner_id', 'name', 'species', 'breed', 'weight', 'age'];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
}
