<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempCustomerAddress extends Model
{
    protected $table = 'temp_customer_addresses';

    protected $fillable = [
        'import_log_id',
        'customer_email',
        'address_line1',
        'address_line2',
        'city',
        'province',
        'postal_code',
    ];
}
