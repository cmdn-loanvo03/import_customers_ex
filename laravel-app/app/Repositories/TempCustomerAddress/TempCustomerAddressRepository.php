<?php

namespace App\Repositories\TempCustomerAddress;

use App\Models\TempCustomerAddress;
use App\Repositories\BaseRepository;

class TempCustomerAddressRepository extends BaseRepository implements TempCustomerAddressRepositoryInterface
{
    public function getModel()
    {
        return TempCustomerAddress::class;
    }

    public function insertBatch(array $data): void
    {
        TempCustomerAddress::insert($data);
    }

    public function deleteByLogId(int $logId): void
    {
        TempCustomerAddress::where('import_log_id', $logId)->delete();
    }

    public function transferToCustomerAddressTableByLogId(int $logId): void
    {
        \DB::statement("
            INSERT INTO customer_addresses 
            (customer_id, address_line1, address_line2, city, province, postal_code, created_at, updated_at)
            SELECT c.id, t.address_line1, t.address_line2, t.city, t.province, t.postal_code, t.created_at, t.updated_at
            FROM temp_customer_addresses t
            JOIN customers c ON c.email = t.customer_email
            WHERE t.import_log_id = ?
        ", [$logId]);
    }
}
