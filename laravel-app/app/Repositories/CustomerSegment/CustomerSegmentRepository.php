<?php
namespace App\Repositories\CustomerSegment;

use App\Models\CustomerSegment;
use App\Repositories\BaseRepository;

class CustomerSegmentRepository extends BaseRepository implements CustomerSegmentRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\CustomerSegment::class;
    }

    public function findByName(string $name): ?CustomerSegment
    {
        return $this->model->where('name', $name)->first();
    }
}
