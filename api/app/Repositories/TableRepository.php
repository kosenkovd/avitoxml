<?

namespace App\Repositories;

use App\Configuration\Config;
use App\Models\Table;
use App\Repositories\Interfaces\ITableRepository;

class TableRepository extends RepositoryBase implements ITableRepository
{
    function __construct()
    {
        parent::__construct();
        $this->config = new Config();
    }

    public function getTables(?int $userId = null) : array
    {
        $mysqli = $this->connect();
        $mysqli->set_charset("utf8");
        $statement = "
SELECT
    `id`, `userId`, `googleSheetId`, `googleDriveId`, `dateExpired`, `isDeleted`, `dateDeleted`, `notes`, `tableGuid`
FROM `".$this->config::getTablesTableName()."`
WHERE 1";
        if(!is_null($userId))
        {
            $statement .= " AND `userId`=".$mysqli->real_escape_string($userId);
        }
        $res = $mysqli->query($statement);

        $tables = [];
        while($row = $res->fetch_assoc())
        {
            $tables[] = new Table(
                $row["id"],
                $row["userId"],
                $row["googleSheetId"],
                $row["googleDriveId"],
                $row["dateExpired"],
                $row["isDeleted"],
                $row["dateDeleted"],
                $row["notes"],
                $row["tableGuid"]);
        }

        return $tables;
    }
}
