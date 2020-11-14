<?

namespace App\Repositories;

use App\Repositories\Interfaces\ITableRepository;

class TableRepository extends RepositoryBase implements ITableRepository
{
    function __construct()
    {
        parent::__construct();
    }
}
