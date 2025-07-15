<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'address_line',
        'province',
        'district',
        'ward',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

