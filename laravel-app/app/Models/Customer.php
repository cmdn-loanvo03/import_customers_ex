<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'date_of_birth',
        'gender_id',
        'customer_type_id',
        'segment_id',
        'national_id',
    ];

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function type()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function segment()
    {
        return $this->belongsTo(CustomerSegment::class, 'segment_id');
    }

    public function address()
    {
        return $this->hasOne(CustomerAddress::class);
    }
}

