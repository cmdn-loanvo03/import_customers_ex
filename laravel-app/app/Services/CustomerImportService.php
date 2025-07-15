<?php

namespace App\Services;

use App\Http\Requests\CustomerImportRowRequest;
use App\Repositories\CustomerRepository;
use App\Models\{CustomerFailure, CustomerImportLog};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CustomerImportService
{
    public function __construct(protected CustomerRepository $repo) {}

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

        $log = CustomerImportLog::create([
            'filename' => $file,
            'status' => empty($failures) ? 'success' : 'failed',
            'total_rows' => $rowNum - 1,
            'failed_rows' => count($failures),
            'message' => empty($failures) ? null : 'Validation failed',
        ]);

        if (!empty($failures)) {
            foreach ($failures as $fail) {
                CustomerFailure::create(array_merge(['import_log_id' => $log->id], $fail));
            }
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
                $segment = $data['total_purchase'] > 100_000_000 ? 'high_value'
                    : (($data['total_purchase'] < 100_000 && $age > 3) ? 'at_risk' : 'normal');

                $batch[] = ['data' => $data, 'segment' => $segment];

                if (count($batch) === 1000) {
                    foreach ($batch as $item) {
                        $this->repo->store($item['data'], $item['segment']);
                    }
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                foreach ($batch as $item) {
                    $this->repo->store($item['data'], $item['segment']);
                }
                $inserted += count($batch);
            }

            DB::commit();
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
