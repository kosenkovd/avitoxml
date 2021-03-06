<?php


namespace App\Repositories;

use Exception;
use App\Models\Generator;
use App\Repositories\Interfaces\IGeneratorRepository;

class GeneratorRepository extends RepositoryBase implements IGeneratorRepository
{
    private static int $MaxDataLength = 2000000;

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
INSERT INTO `".$this->config->getGeneratorsTableName()."`(
    `tableId`,
    `generatorGuid`,
    `dateLastGenerated`,
    `targetPlatform`)
VALUES (
    ".$generator->getTableId().",
    '".$generator->getGeneratorGuid()."',
    ".$generator->getLastGenerated().",
    '".$generator->getTargetPlatform()."')";

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
        $query = "
SELECT
       `lastGeneration`
FROM `".$this->config->getGeneratorsTableName()."`
WHERE `id`=?";

        $mysqli = $this->connect();
        $statement = $mysqli->prepare($query);
        $statement->bind_param('i', $generatorId);

        $statement->execute();

        $generatedXML = null;
        $statement->store_result();
        $statement->bind_result($generatedXML);
        $statement->data_seek(0);
        if(!$statement->fetch())
        {
            return null;
        }
        $statement->free_result();

        $mysqli->close();

        return $generatedXML;
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
        $query = "
UPDATE `".$this->config->getGeneratorsTableName()."`
SET `lastGeneration`=?
WHERE `id`=?";
        $statement = $mysqli->prepare($query);

        // Big files should be uploaded partially
        $null = null;
        $statement->bind_param('bi', $null, $generatorId);

        $stringNotCompletelyLoaded = true;
        $offset = 0;
        while($stringNotCompletelyLoaded)
        {
            $statement->send_long_data(0, substr($content, $offset, self::$MaxDataLength));
            $offset += self::$MaxDataLength;

            if($offset > strlen($content))
            {
                $stringNotCompletelyLoaded = false;
            }
        }

        $statement->execute();
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
UPDATE `".$this->config->getGeneratorsTableName()."`
SET `id`=".$generator->getGeneratorId().",
    `tableId`=".$generator->getTableId().",
    `generatorGuid`='".$generator->getGeneratorGuid()."',
    `dateLastGenerated`=".$generator->getLastGenerated().",
    `targetPlatform`='".$generator->getTargetPlatform()."'
WHERE `id`=".$generator->getGeneratorId();

        $mysqli = $this->connect();
        $mysqli->query($statement);
        $mysqli->close();
    }
}
