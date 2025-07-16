<?php
namespace App\Repositories\Gender;

use App\Repositories\BaseRepository;
use App\Models\Gender;

class GenderRepository extends BaseRepository implements GenderRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\Gender::class;
    }

    public function findByName(string $name): ?Gender
    {
        return $this->model->where('name', $name)->first();
    }
}
