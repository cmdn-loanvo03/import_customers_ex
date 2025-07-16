<?php

namespace App\Repositories\TempCustomerAddress;

interface TempCustomerAddressRepositoryInterface
{
    public function insertBatch(array $data): void;

    public function transferToCustomerAddressTableByLogId(int $logId): void;

    public function deleteByLogId(int $importLogId): void;
}
