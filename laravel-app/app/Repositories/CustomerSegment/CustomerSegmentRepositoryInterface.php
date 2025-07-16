<?php
namespace App\Repositories\CustomerSegment;

use App\Repositories\RepositoryInterface;
use App\Models\CustomerSegment;

interface CustomerSegmentRepositoryInterface extends RepositoryInterface
{
    public function findByName(string $name): ?CustomerSegment;
}
