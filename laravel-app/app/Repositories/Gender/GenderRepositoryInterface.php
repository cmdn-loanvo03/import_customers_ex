<?php
namespace App\Repositories\Gender;

use App\Repositories\RepositoryInterface;
use App\Models\Gender;

interface GenderRepositoryInterface extends RepositoryInterface
{
    public function findByName(string $name): ?Gender;
}
