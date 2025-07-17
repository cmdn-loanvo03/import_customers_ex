<?php

namespace App\Repositories\TempCustomerAddress;

use App\Repositories\RepositoryInterface;

interface TempCustomerAddressRepositoryInterface extends RepositoryInterface
{
    public function insertBatch(array $data): void;

    public function transferToCustomerAddressTableByLogId(int $logId): void;

    public function deleteByLogId(int $logId): void;
}
