<?php


namespace App\Repositories;


use App\Configuration\Config;
use App\Models\TableUpdateLock;
use App\Repositories\Interfaces\ITableUpdateLockRepository;

class TableUpdateLockRepository extends RepositoryBase implements ITableUpdateLockRepository
{
    function __construct()
    {
        parent::__construct();
        $this->config = new Config();
    }

    /**
     * @inheritDoc
     */
    public function insert(int $tableId): TableUpdateLock
    {
        $mysqli = $this->connect();
        $query = "
INSERT INTO `".$this->config->getTableUpdateLockTableName()."`(
    `tableId`,
    `fillImagesLock`,
    `randomizeTextLock`)
VALUES (?, 0, 0)";
        $statement = $mysqli->prepare($query);

        $statement->bind_param('i', $tableId);
        $statement->execute();

        $newLock = new TableUpdateLock(
            $mysqli->insert_id,
            $tableId,
            0,
            0
        );
        $mysqli->close();

        return $newLock;
    }

    /**
     * @inheritDoc
     */
    public function update(TableUpdateLock $lock): void
    {
        if(is_null($lock->getTableUpdateLockId()))
        {
            return;
        }

        $mysqli = $this->connect();
        $query = "
UPDATE `".$this->config->getTableUpdateLockTableName()."`
SET `id`=?,
    `tableId`=?,
    `fillImagesLock`=?,
    `randomizeTextLock`=?
WHERE `id`=?";

        $statement = $mysqli->prepare($query);

        $lockId = $lock->getTableUpdateLockId();
        $tableId = $lock->getTableId();
        $fillImagesLock = $lock->getFillImagesLock();
        $randomizeTextLock = $lock->getRandomizeTextLock();
        $statement->bind_param(
            'iiiii', $lockId, $tableId, $fillImagesLock, $randomizeTextLock, $lockId);
        $statement->execute();
        $mysqli->close();
    }

    /**
     * @inheritDoc
     */
    public function getByTableId(int $tableId) : ?TableUpdateLock
    {
        $mysqli = $this->connect();

        $query = "
SELECT
    `id`,
    `tableId`,
    `fillImagesLock`,
    `randomizeTextLock`
FROM ".$this->config->getTableUpdateLockTableName()."
WHERE `tableId`=?";
        $statement = $mysqli->prepare($query);
        $statement->bind_param('i', $tableId);
        $statement->execute();

        $result = $statement->get_result();
        if(!$result->data_seek(0))
        {
            return null;
        }

        $row = $result->fetch_assoc();

        $mysqli->close();

        return new TableUpdateLock(
            $row["id"],
            $row["tableId"],
            $row["fillImagesLock"],
            $row["randomizeTextLock"]
        );
    }
}
