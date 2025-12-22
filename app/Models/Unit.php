<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'name',
        'note',
        'is_active',
    ];

    public function rentCycles()
    {
        return $this->hasMany(RentCycle::class);
    }

    public function activeRent()
    {
        return $this->hasOne(RentCycle::class)
            ->whereNull('end_date');
    }

}
