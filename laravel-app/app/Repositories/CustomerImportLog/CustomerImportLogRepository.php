<?php
namespace App\Repositories\CustomerImportLog;

use App\Repositories\BaseRepository;

class CustomerImportLogRepository extends BaseRepository implements CustomerImportLogRepositoryInterface
{
    public function getModel()
    {
        return \App\Models\CustomerImportLog::class;
    }
}
