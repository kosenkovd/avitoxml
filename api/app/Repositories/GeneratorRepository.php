<?php


namespace App\Repositories;

use Exception;
use App\Models\Generator;
use App\Repositories\Interfaces\IGeneratorRepository;

class GeneratorRepository extends RepositoryBase implements IGeneratorRepository
{
    /**
     * GeneratorRepository constructor.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Persist new generator in database.
     *
     * @param Generator $generator generator data to insert.
     * @return int new table id.
     * @throws Exception in case of DB connection failure.
     */
    public function insert(Generator $generator) : int
    {
        $statement = "
INSERT INTO `".$this->config::getGeneratorsTableName()."`(
    `tableId`,
    `generatorGuid`,
    `dateLastGenerated`)
VALUES (
    ".$generator->getTableId().",
    '".$generator->getGeneratorGuid()."',
    ".$generator->getLastGenerated().")";

        $mysqli = $this->connect();
        $mysqli->query($statement);
        $newGeneratorId = $mysqli->insert_id;
        $mysqli->close();

        return $newGeneratorId;
    }

    /**
     * Retrieve last saved generated XML.
     *
     * @param int $generatorId generator id.
     * @return string generated XML, if it exists.
     * @throws Exception in case of DB connection failure.
     */
    public function getLastGeneration(int $generatorId) : ?string
    {
        $statement = "
SELECT
       `lastGeneration`
FROM `".$this->config::getGeneratorsTableName()."`
WHERE `id`=".$generatorId;

        $mysqli = $this->connect();
        $res = $mysqli->query($statement);

        if(!$res || !$res->data_seek(0))
        {
            return null;
        }

        $row = $res->fetch_assoc();
        $mysqli->close();

        return $row["lastGeneration"];
    }

    /**
     * Save new XML generation.
     *
     * @param int $generatorId generator id.
     * @param string $content new generation content.
     * @throws Exception in case of DB connection failure.
     */
    public function setLastGeneration(int $generatorId, string $content) : void
    {
        $mysqli = $this->connect();
        $content = $mysqli->real_escape_string($content);
        $statement = "
UPDATE `".$this->config::getGeneratorsTableName()."`
SET `lastGeneration`='".$content."'
WHERE `id`=".$generatorId;
        $mysqli->query($statement);
        $mysqli->close();
    }

    /**
     * Update model in database.
     *
     * @param Generator $generator generator resource to update.
     * @throws Exception in case of DB connection failure.
     */
    public function update(Generator $generator) : void
    {
        if(is_null($generator->getGeneratorId()))
        {
            return;
        }

        $statement = "
UPDATE `".$this->config::getGeneratorsTableName()."`
SET `id`=".$generator->getGeneratorId().",
    `tableId`=".$generator->getTableId().",
    `generatorGuid`='".$generator->getGeneratorGuid()."',
    `dateLastGenerated`=".$generator->getLastGenerated()."
WHERE `id`=".$generator->getGeneratorId();

        $mysqli = $this->connect();
        $mysqli->query($statement);
        $mysqli->close();
    }
}
