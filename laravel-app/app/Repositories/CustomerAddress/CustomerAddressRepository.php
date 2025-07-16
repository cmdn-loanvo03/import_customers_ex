<?php
namespace App\Repositories\CustomerAddress;

use App\Repositories\BaseRepository;

class CustomerAddressRepository extends BaseRepository implements CustomerAddressRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\CustomerAddress::class;
    }
}
