<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempCustomer extends Model
{
    protected $table = 'temp_customers';
    protected $guarded = [];  
    public $timestamps = true;
}
