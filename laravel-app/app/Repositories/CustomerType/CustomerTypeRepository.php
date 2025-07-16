<?php
namespace App\Repositories\CustomerType;

use App\Repositories\BaseRepository;
use App\Models\CustomerType;

class CustomerTypeRepository extends BaseRepository implements CustomerTypeRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\CustomerType::class;
    }

    public function findByName(string $name): ?CustomerType
    {
        return $this->model->where('name', $name)->first();
    }
}
