<?php

namespace App\Services;

use App\Http\Requests\CustomerImportRowRequest;
use App\Repositories\Customer\CustomerRepositoryInterface;
use App\Repositories\CustomerAddress\CustomerAddressRepositoryInterface;
use App\Repositories\CustomerSegment\CustomerSegmentRepositoryInterface;
use App\Repositories\CustomerType\CustomerTypeRepositoryInterface;
use App\Repositories\Gender\GenderRepositoryInterface;
use App\Repositories\CustomerImportLog\CustomerImportLogRepositoryInterface;
use App\Repositories\CustomerFailure\CustomerFailureRepositoryInterface;
use App\Repositories\TempCustomer\TempCustomerRepository;
use App\Models\{CustomerFailure, CustomerImportLog};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CustomerImportService
{   
    public function __construct(
        protected CustomerRepositoryInterface $customerRepo,
        protected CustomerAddressRepositoryInterface $addressRepo,
        protected CustomerSegmentRepositoryInterface $segmentRepo,
        protected CustomerTypeRepositoryInterface $typeRepo,
        protected GenderRepositoryInterface $genderRepo,
        protected CustomerImportLogRepositoryInterface $importLogRepo,
        protected CustomerFailureRepositoryInterface $failureRepo)
    {}

    public const GENDER_MAP = [
        'male' => 1,
        'female' => 2,
        'other' => 3,
    ];

    public const CUSTOMER_TYPE_MAP = [
        'internal' => 1,
        'external' => 2,
    ];
    
    public const SEGMENT_MAP = [
        'high_value' => 1,
        'at_risk' => 2,
        'normal' => 3,
    ];

    public function importAllFromMinio(): array
    {
        $disk = Storage::disk('s3_minio');
        $files = $disk->files('');

        if (empty($files)) {
            return ['status' => 'failed', 'message' => 'No CSV files found in MinIO bucket.'];
        }

        foreach ($files as $file) {
            $result = $this->importFile($file);
            if ($result['status'] === 'failed') return $result;
        }

        return ['status' => 'success', 'message' => 'All files imported.'];
    }

    public function importFile(string $file): array
    {
        \Log::info("🔄 Start importing file: $file");
        
        $log = $this->importLogRepo->create([
            'filename' => $file,
            'status' => 'success',
            'total_rows' => 0,
            'failed_rows' => 0,
            'message' => null,
        ]);

        $suffix = Str::random(8); 
        $tempRepo = new TempCustomerRepository($suffix);
        $tempRepo->createTable();

        $stream = Storage::disk('s3_minio')->readStream($file);
        if (!$stream) {
            $tempRepo->dropTable();
            return ['status' => 'failed', 'message' => "Unable to open file: $file"];
        }

        $header = null;
        $failures = [];
        $rowNum = 1;
        $validRows = [];

        while (($row = fgetcsv($stream)) !== false) {
            if (!$header) {
                $header = array_map('trim', $row);
                continue;
            }

            if ($rowNum % 10000 === 0) {
                \Log::info("✅ Processed $rowNum rows from $file");
            }

            $rowNum++;
            $data = array_combine($header, $row);

            if (!$data || count($data) !== count($header)) {
                $failures[] = $this->fail($rowNum, $data, 'Invalid format or column mismatch');
                $this->flushFailuresIfNeeded($failures, $log);
                continue;
            }

            $validated = CustomerImportRowRequest::validate($data);
            if (!$validated['status']) {
                $failures[] = $this->fail($rowNum, $data, implode('; ', $validated['errors']));
                $this->flushFailuresIfNeeded($failures, $log);
                continue;
            }

            if (
                Str::contains($data['customer_type_name'], 'internal') &&
                preg_match('/@(gmail|yahoo|outlook)\.com$/', $data['email'])
            ) {
                $failures[] = $this->fail($rowNum, $data, 'Internal user must not use public email domain.');
                $this->flushFailuresIfNeeded($failures, $log);
                continue;
            }

            try {
                $genderName = strtolower($data['gender_name']);
                $typeName = strtolower($data['customer_type_name']);

                if (!isset(self::GENDER_MAP[$genderName]) || !isset(self::CUSTOMER_TYPE_MAP[$typeName])) {
                    $failures[] = $this->fail($rowNum, $data, 'Invalid gender or customer type');
                    $this->flushFailuresIfNeeded($failures, $log);
                    continue;
                }

                $genderId = self::GENDER_MAP[$genderName];
                $typeId = self::CUSTOMER_TYPE_MAP[$typeName];

                $age = Carbon::parse($data['date_of_birth'])->age;

                $segmentName = $data['total_purchase'] > 100_000_000
                ? 'high_value'
                : (($data['total_purchase'] < 100_000 && $age > 3) ? 'at_risk' : 'normal');

                if (!isset(self::SEGMENT_MAP[$segmentName])) {
                    $failures[] = $this->fail($rowNum, $data, 'Invalid segment');
                    $this->flushFailuresIfNeeded($failures, $log);
                    continue;
                }

                $segmentId = self::SEGMENT_MAP[$segmentName];

                $validRows[] = [
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'date_of_birth' => $data['date_of_birth'],
                    'gender_id' => $genderId,
                    'customer_type_id' => $typeId,
                    'segment_id' => $segmentId,
                    'national_id' => $data['national_id'],
                    'address_line' => $data['address_line'],
                    'province' => $data['province'],
                    'district' => $data['district'],
                    'ward' => $data['ward'],
                ];

                if (count($validRows) >= 1000) {
                    try {
                        \Log::info("⏳ Inserting batch of 1000 valid rows...");
                        $tempRepo->insertBatch($validRows);
                        $validRows = []; 
                        \Log::info("Successfully inserted 1000 rows into temp_customers");
                    } catch (\Throwable $e) {
                        \Log::error(" Failed to insert batch: " . $e->getMessage());
                    }
                }
            } catch (\Throwable $e) {
                $failures[] = $this->fail($rowNum, $data, $e->getMessage());
                $this->flushFailuresIfNeeded($failures, $log);
            }
        }

        $this->flushFailuresIfNeeded($failures, $log);
        fclose($stream);

        if (!empty($validRows)) {
            $tempRepo->insertBatch($validRows);
        }

        $this->importLogRepo->update($log->id,[
            'status' => empty($failures) ? 'success' : 'failed',
            'total_rows' => $rowNum - 1,
            'failed_rows' => count($failures),
            'message' => empty($failures) ? null : 'Validation failed',
        ]);

        if (!empty($failures)) {
            $batch = array_map(fn($fail) => array_merge(['import_log_id' => $log->id], $fail), $failures);
            $this->failureRepo->insertBatch($batch);
            $tempRepo->dropTable();
        }

        try {
            \Log::info("Starting to transfer data from temp_customers table to customers table for import_log_id: {$log->id}");

            DB::transaction(function () use ($tempRepo, $log) {
                $tempRepo->transfer();

                \Log::info("Successfully inserted data from temp table to customers.");

                $log->update([
                    'status' => 'success',
                    'message' => 'Import completed successfully.'
                ]);

            });

            \Log::info("🎉 Import for file '{$log->filename}' completed successfully.");

            $tempRepo->dropTable();

            return ['status' => 'success', 'message' => "Import from {$log->filename} completed successfully."];
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'message' => $e->getMessage()]);

            return ['status' => 'failed', 'message' => 'DB error: ' . $e->getMessage()];
        } finally {
            $tempRepo->dropTable();
        }
    }

    private function fail($row, $data, $reason): array
    {
        return [
            'row_number' => $row,
            'raw_data' => json_encode($data),
            'failed_reason' => $reason,
        ];
    }

    private function flushFailuresIfNeeded(array &$failures, CustomerImportLog $log): void
    {
        if (count($failures) >= 1000) {
            $batch = array_map(fn($fail) => array_merge(['import_log_id' => $log->id], $fail), $failures);

            try {
                $this->failureRepo->insertBatch($batch);
            } catch (\Throwable $e) {
                \Log::error("Failed to insert failure batch: " . $e->getMessage());
            }

            $failures = []; 
        }
    }
}
