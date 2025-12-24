<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'phone',
        'note',
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
