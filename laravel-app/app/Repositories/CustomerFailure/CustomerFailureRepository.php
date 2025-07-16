<?php
namespace App\Repositories\CustomerFailure;

use App\Repositories\BaseRepository;

class CustomerFailureRepository extends BaseRepository implements CustomerFailureRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\CustomerFailure::class;
    }

    public function insertBatch(array $failures): void
    {
        $this->model->insert($failures);
    }
}
