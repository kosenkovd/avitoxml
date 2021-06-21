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
	 * @inheritDoc
	 */
    public function get(string $generatorGuid): ?Generator
    {
        $mysqli = $this->connect();
        $generatorGuid = $mysqli->real_escape_string($generatorGuid);
    
        $res = $mysqli->query("
        SELECT
               `id`,
               `tableId`,
               `generatorGuid`,
               `targetPlatform`,
               `dateLastGenerated`,
               `maxAds`
        FROM ".$this->config->getGeneratorsTableName()."
        WHERE `generatorGuid`= '".$generatorGuid."'");
        
        if(!$res || !$res->data_seek(0))
        {
            return null;
        }
        $row = $res->fetch_assoc();
        $mysqli->close();
    
        return new Generator(
            $row["id"],
            $row["tableId"],
            $row["generatorGuid"],
            $row["dateLastGenerated"],
            $row["targetPlatform"],
            $row["maxAds"]
        );
    }

	/**
	 * @inheritDoc
	 */
    public function insert(Generator $generator) : int
    {
        $statement = "
            INSERT INTO `".$this->config->getGeneratorsTableName()."` (
                `tableId`,
                `generatorGuid`,
                `dateLastGenerated`,
                `targetPlatform`,
                `maxAds`
            )
            VALUES (
                ".$generator->getTableId().",
                '".$generator->getGeneratorGuid()."',
                ".$generator->getLastGenerated().",
                '".$generator->getTargetPlatform()."',
                ".$generator->getMaxAds()."
                )";

        $mysqli = $this->connect();
        $mysqli->query($statement);
        $newGeneratorId = $mysqli->insert_id;
        $mysqli->close();

        return $newGeneratorId;
    }

	/**
	 * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function update(Generator $generator) : void
    {
        $mysqli = $this->connect();

        $query = "
            UPDATE `".$this->config->getGeneratorsTableName()."`
            SET
                `maxAds`= ?
            WHERE `id`= ?";
    
        $statement = $mysqli->prepare($query);
        
        $maxAds = $generator->getMaxAds();
        $generatorId = $generator->getGeneratorId();
        
        $statement->bind_param(
            'ii',
            $maxAds,
            $generatorId
        );
    
        $result = $statement->execute();
        
        $mysqli->close();
    }
}
