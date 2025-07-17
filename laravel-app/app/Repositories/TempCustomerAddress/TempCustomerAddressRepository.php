<?php

namespace App\Repositories\TempCustomerAddress;

use App\Models\TempCustomerAddress;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class TempCustomerAddressRepository extends BaseRepository implements TempCustomerAddressRepositoryInterface
{
    public function getModel()
    {
        return TempCustomerAddress::class;
    }

    public function insertBatch(array $data): void
    {
        $this->model->insert($data);
    }

    public function deleteByLogId(int $logId): void
    {
        $this->model->where('import_log_id', $logId)->delete();
    }

    public function transferToCustomerAddressTableByLogId(int $logId): void
    {
        DB::insert("
            INSERT INTO customer_addresses (customer_id, address_line, province, district, ward, created_at, updated_at)
            SELECT c.id, tca.address_line, tca.province, tca.district, tca.ward, NOW(), NOW()
            FROM temp_customer_addresses tca
            JOIN customers c ON c.email = tca.customer_email
            WHERE tca.import_log_id = ?
        ", [$logId]);
    }
}
