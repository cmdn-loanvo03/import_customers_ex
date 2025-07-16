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
use App\Models\{CustomerFailure, CustomerImportLog};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CustomerImportService
{   
    public function __construct(protected CustomerRepositoryInterface $customerRepo,
                                protected CustomerAddressRepositoryInterface $addressRepo,
                                protected CustomerSegmentRepositoryInterface $segmentRepo,
                                protected CustomerTypeRepositoryInterface $typeRepo,
                                protected GenderRepositoryInterface $genderRepo,
                                protected CustomerImportLogRepositoryInterface $importLogRepo,
                                protected CustomerFailureRepositoryInterface $failureRepo)
    {}

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
        $stream = Storage::disk('s3_minio')->readStream($file);
        if (!$stream) {
            return ['status' => 'failed', 'message' => "Unable to open file: $file"];
        }

        $tmpPath = storage_path('app/tmp_valid_rows.csv');
        $tmpHandle = fopen($tmpPath, 'w');
        if (!$tmpHandle) {
            return ['status' => 'failed', 'message' => "Cannot create temp file"];
        }

        $header = null;
        $failures = [];
        $rowNum = 1;

        while (($row = fgetcsv($stream)) !== false) {
            if (!$header) {
                $header = array_map('trim', $row);
                fputcsv($tmpHandle, $header); 
                continue;
            }
            if ($rowNum % 10000 === 0) {
                \Log::info("✅ Processed $rowNum rows from $file");
            }
            $rowNum++;
            $data = array_combine($header, $row);

            if (!$data || count($data) !== count($header)) {
                $failures[] = $this->fail($rowNum, $data, 'Invalid format or column mismatch');
                continue;
            }

            $validated = CustomerImportRowRequest::validate($data);
            if (!$validated['status']) {
                $failures[] = $this->fail($rowNum, $data, implode('; ', $validated['errors']));
                continue;
            }

            if (
                Str::contains($data['customer_type_name'], 'internal') &&
                preg_match('/@(gmail|yahoo|outlook)\.com$/', $data['email'])
            ) {
                $failures[] = $this->fail($rowNum, $data, 'Internal user must not use public email domain.');
                continue;
            }

            fputcsv($tmpHandle, $row);
        }

        fclose($stream);
        fclose($tmpHandle);

        $log = $this->importLogRepo->create([
            'filename' => $file,
            'status' => empty($failures) ? 'success' : 'failed',
            'total_rows' => $rowNum - 1,
            'failed_rows' => count($failures),
            'message' => empty($failures) ? null : 'Validation failed',
        ]);

        if (!empty($failures)) {
            collect($failures)->chunk(1000)->each(function ($chunk) use ($log) {
                $batch = [];

                foreach ($chunk as $fail) {
                    $batch[] = array_merge(['import_log_id' => $log->id], $fail);
                }

                $this->failureRepo->insertBatch($batch);
            });

            unlink($tmpPath);
            return ['status' => 'failed', 'message' => "Import failed: some rows are invalid"];
        }

        $tmpHandle = fopen($tmpPath, 'r');
        $header = fgetcsv($tmpHandle);
        $batch = [];
        $inserted = 0;
        \Log::info("🚀 All rows valid. Begin inserting into database...");

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($tmpHandle)) !== false) {
                $data = array_combine($header, $row);

                $age = Carbon::parse($data['date_of_birth'])->age;
                
                $segmentName = $data['total_purchase'] > 100_000_000 ? 'high_value'
                    : (($data['total_purchase'] < 100_000 && $age > 3) ? 'at_risk' : 'normal');

                $segment = $this->segmentRepo->findByName($segmentName);
                if (!$segment) {
                    $segment = $this->segmentRepo->create(['name' => $segmentName]);
                }

                $gender = $this->genderRepo->findByName($data['gender_name']);
                if (!$gender) {
                    throw new \Exception("Gender '{$data['gender_name']}' not found");
                }

                $customerType = $this->typeRepo->findByName($data['customer_type_name']);
                if (!$customerType) {
                    throw new \Exception("CustomerType '{$data['customer_type_name']}' not found");
                }

                $customer = $this->customerRepo->create([
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'date_of_birth' => $data['date_of_birth'],
                    'gender_id' => $gender->id,
                    'customer_type_id' => $customerType->id,
                    'segment_id' => $segment->id,
                    'national_id' => $data['national_id'],
                ]);

                $this->addressRepo->create([
                    'customer_id' => $customer->id,
                    'address_line' => $data['address_line'],
                    'province' => $data['province'],
                    'district' => $data['district'],
                    'ward' => $data['ward'],
                ]);

                $batch[] = $customer;

                if (count($batch) >= 1000) {
                    DB::commit();
                    DB::beginTransaction();
                    \Log::info("Committed batch of 1000 customers. Total inserted so far: " . ($inserted + count($batch)));
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            DB::commit();
            $inserted += count($batch);
            \Log::info("Committed final batch. Total customers inserted: $inserted");

            fclose($tmpHandle);
            unlink($tmpPath);

            return ['status' => 'success', 'message' => "$inserted rows imported from $file"];
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($tmpHandle);
            unlink($tmpPath);
            $log->update(['status' => 'failed', 'message' => $e->getMessage()]);
            return ['status' => 'failed', 'message' => 'DB error: ' . $e->getMessage()];
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
}
