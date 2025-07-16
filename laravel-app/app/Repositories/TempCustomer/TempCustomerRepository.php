<?php

namespace App\Repositories\TempCustomer;

use App\Models\TempCustomer;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class TempCustomerRepository extends BaseRepository implements TempCustomerRepositoryInterface
{
    public function getModel()
    {
        return TempCustomer::class;
    }

    public function insertBatch(array $data): void
    {
        $this->model->insert($data);
    }

    public function deleteByLogId(int $logId): void
    {
        $this->model->where('import_log_id', $logId)->delete();
    }
    
    public function transferToCustomerTableByLogId(int $logId): void
    {
        DB::statement("
            INSERT INTO customers 
            (full_name,email,phone,date_of_birth,gender_id,customer_type_id,segment_id,national_id,created_at,updated_at)
            SELECT full_name,email,phone,date_of_birth,gender_id,customer_type_id,segment_id,national_id,created_at,updated_at
            FROM temp_customers
            WHERE import_log_id = ?
        ", [$logId]);
    }

}
