<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerImportLog extends Model
{
    protected $fillable = [
        'filename',
        'status',
        'total_rows',
        'failed_rows',
        'message',
        'imported_at',
    ];

    public $timestamps = false;

    protected $dates = ['imported_at'];

    public function failures()
    {
        return $this->hasMany(CustomerFailure::class, 'import_log_id');
    }
}

