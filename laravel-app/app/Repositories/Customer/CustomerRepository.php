<?php
namespace App\Repositories\Customer;

use App\Repositories\BaseRepository;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\Customer::class;
    }
}
