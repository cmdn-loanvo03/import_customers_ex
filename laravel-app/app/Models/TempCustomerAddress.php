<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempCustomerAddress extends Model
{
    protected $table = 'temp_customer_addresses';

    protected $fillable = [
        'import_log_id',
        'customer_email',
        'address_line',
        'province',
        'district',
        'ward',
    ];

}
