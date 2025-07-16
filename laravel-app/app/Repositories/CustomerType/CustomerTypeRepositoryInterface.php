<?php
namespace App\Repositories\CustomerType;

use App\Repositories\RepositoryInterface;
use App\Models\CustomerType;

interface CustomerTypeRepositoryInterface extends RepositoryInterface
{
    public function findByName(string $name): ?CustomerType;
}
