<?php

namespace App\Repositories\TempCustomer;

use App\Repositories\RepositoryInterface;

interface TempCustomerRepositoryInterface extends RepositoryInterface
{
    public function insertBatch(array $data): void;
    public function deleteByLogId(int $logId): void;
    public function transferToCustomerTableByLogId(int $logId): void;
}
