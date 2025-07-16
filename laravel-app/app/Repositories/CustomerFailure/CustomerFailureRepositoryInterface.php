<?php
namespace App\Repositories\CustomerFailure;

use App\Repositories\RepositoryInterface;

interface CustomerFailureRepositoryInterface extends RepositoryInterface
{
    public function insertBatch(array $failures): void;
}
