<?php

namespace App\Repositories;

use App\Configuration\Config;
use App\Models\Generator;
use App\Models\Table;
use App\Repositories\Interfaces\ITableRepository;
use App\Repositories\Interfaces\ITableUpdateLockRepository;
use Exception;

class TableRepository extends RepositoryBase implements ITableRepository
{
    function __construct()
    {
        parent::__construct();
        $this->config = new Config();
    }

    /**
     * Get all tables, or only ones that created by user, if $userId is specified.
     *
     * @param int|null $userId owner user id.
     * @return Table[] found tables.
     * @throws Exception in case of DB connection failure.
     */
    public function getTables(?int $userId = null) : array
    {
        $mysqli = $this->connect();
        $statement = "
SELECT `t`.`id` AS `tableId`,
       `t`.`userId` AS `userId`,
       `t`.`googleSheetId`,
       `t`.`googleDriveId`,
       `t`.`yandexToken`,
       `t`.`dateExpired`,
       `t`.`isDeleted`,
       `t`.`dateDeleted`,
       `t`.`notes`,
       `t`.`tableGuid`,
       `g`.`id` AS `generatorId`,
       `g`.`generatorGuid`,
       `g`.`targetPlatform`,
       `g`.`dateLastGenerated`
FROM `".$this->config->getTablesTableName()."` `t`
LEFT JOIN `".$this->config->getGeneratorsTableName()."` `g` ON `t`.`id`=`g`.`tableId`
WHERE 1";
        if(!is_null($userId))
        {
            $statement .= " AND `userId`=".$mysqli->real_escape_string($userId);
        }

        $statement .= " ORDER BY `tableId`";
        $res = $mysqli->query($statement);

        $tables = [];
        while($row = $res->fetch_assoc())
        {
            $tableId = $row["tableId"];
            $generator = new Generator(
                $row["generatorId"],
                $tableId,
                $row["generatorGuid"],
                $row["dateLastGenerated"],
                $row["targetPlatform"]
            );
            if(!isset($tables["table".$tableId]))
            {
                $tables["table".$tableId] = new Table(
                    $tableId,
                    $row["userId"],
                    $row["googleSheetId"],
                    $row["googleDriveId"],
                    $row["yandexToken"],
                    $row["dateExpired"],
                    $row["isDeleted"],
                    $row["dateDeleted"],
                    $row["notes"],
                    $row["tableGuid"],
                    [$generator]);
            }
            else
            {
                $tables["table".$tableId]->addGenerator($generator);
            }
        }

        $mysqli->close();

        return array_values($tables);
    }

    /**
     * @inheritDoc
     */
    public function getGeneratorlessTables(): array
    {
        $mysqli = $this->connect();
        $statement = "
SELECT `t`.`id` AS `tableId`,
       `t`.`userId` AS `userId`,
       `t`.`googleSheetId`,
       `t`.`googleDriveId`,
       `t`.`yandexToken`,
       `t`.`dateExpired`,
       `t`.`isDeleted`,
       `t`.`dateDeleted`,
       `t`.`notes`,
       `t`.`tableGuid`,
       `g`.`generatorGuid`
FROM `".$this->config->getTablesTableName()."` `t`
LEFT JOIN `".$this->config->getGeneratorsTableName()."` `g` ON `t`.`id`=`g`.`tableId`
WHERE generatorGuid IS NULL";

        $res = $mysqli->query($statement);

        $tables = [];
        while($row = $res->fetch_assoc())
        {
            $tableId = $row["tableId"];
            $tables["table".$tableId] = new Table(
                $tableId,
                $row["userId"],
                $row["googleSheetId"],
                $row["googleDriveId"],
                $row["yandexToken"],
                $row["dateExpired"],
                $row["isDeleted"],
                $row["dateDeleted"],
                $row["notes"],
                $row["tableGuid"],
                []);
        }

        $mysqli->close();

        return array_values($tables);
    }

    /**
     * Persist new table in database.
     *
     * @param Table $table table data to insert.
     * @return int new table id.
     * @throws Exception in case of DB connection failure.
     */
    public function insert(Table $table) : int
    {
        $dateExpired = is_null($table->getDateExpired()) ? "NULL" : $table->getDateExpired();

        $statement = "
INSERT INTO `".$this->config->getTablesTableName()."`(
    `userId`,
    `googleSheetId`,
    `dateExpired`,
    `tableGuid`)
VALUES (
    ".$table->getUserId().",
    '".$table->getGoogleSheetId()."',
    ".$dateExpired.",
    '".$table->getTableGuid()."')";

        $mysqli = $this->connect();
        $mysqli->query($statement);
        $tableId = $mysqli->insert_id;
        $mysqli->close();

        return $tableId;
    }

    /**
     * Get table by its guid.
     *
     * @param string $tableGuid table guid.
     * @return Table|null table, if found, otherwise null.
     * @throws Exception in case of DB connection failure.
     */
    public function get(string $tableGuid) : ?Table
    {
        $mysqli = $this->connect();
        $tableGuid = $mysqli->real_escape_string($tableGuid);
        $statement = "
SELECT `t`.`id` AS `tableId`,
       `t`.`userId` AS `userId`,
       `t`.`googleSheetId`,
       `t`.`googleDriveId`,
       `t`.`yandexToken`,
       `t`.`dateExpired`,
       `t`.`isDeleted`,
       `t`.`dateDeleted`,
       `t`.`notes`,
       `t`.`tableGuid`,
       `g`.`id` AS `generatorId`,
       `g`.`generatorGuid`,
       `g`.`targetPlatform`,
       `g`.`dateLastGenerated`
FROM `".$this->config->getTablesTableName()."` `t`
LEFT JOIN `".$this->config->getGeneratorsTableName()."` `g` ON `t`.`id`=`g`.`tableId`
WHERE `t`.`tableGuid`='".$tableGuid."'";
        $res = $mysqli->query($statement);

        if(!$res || !$res->data_seek(0))
        {
            return null;
        }

        $mysqli->close();

        $table = null;
        while($row = $res->fetch_assoc())
        {
            $tableId = $row["tableId"];
            $generator = new Generator(
                $row["generatorId"],
                $tableId,
                $row["generatorGuid"],
                $row["dateLastGenerated"],
                $row["targetPlatform"]
            );
            if(is_null($table))
            {
                $table = new Table(
                    $tableId,
                    $row["userId"],
                    $row["googleSheetId"],
                    $row["googleDriveId"],
                    $row["yandexToken"],
                    $row["dateExpired"],
                    $row["isDeleted"],
                    $row["dateDeleted"],
                    $row["notes"],
                    $row["tableGuid"],
                    [$generator]);
            }
            else
            {
                $table->addGenerator($generator);
            }
        }

        return $table;
    }

    /**
     * Update yandex token for table.
     *
     * @param int $tableId
     * @param string $yandexToken
     * @throws Exception
     */
    public function updateYandexToken(int $tableId, string $yandexToken) : void
    {
        $query = "
UPDATE `".$this->config->getTablesTableName()."`
SET `yandexToken`=?
WHERE `id`=?";

        $mysqli = $this->connect();
        $statement = $mysqli->prepare($query);
        $statement->bind_param('si', $yandexToken, $tableId);

        $statement->execute();
    }
}
