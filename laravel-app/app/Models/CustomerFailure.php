<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerFailure extends Model
{
    protected $fillable = [
        'import_log_id',
        'row_number',
        'raw_data',
        'failed_reason',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function importLog()
    {
        return $this->belongsTo(CustomerImportLog::class, 'import_log_id');
    }
}

