<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CustomerImportService;
use Illuminate\Support\Facades\Log;

class ImportCustomersFromMinio extends Command
{
    protected $signature = 'customers:import-from-minio';
    protected $description = 'Import customers from CSV stored in MinIO';

    public function handle(CustomerImportService $importService)
    {
        Log::info('Schedule triggered at: ' . now());

        $result = $importService->importAllFromMinio();

        if ($result['status'] === 'failed') {
            $this->error($result['message']);
            return self::FAILURE;
        }

        $this->info($result['message']);
        return self::SUCCESS;
    }
}
