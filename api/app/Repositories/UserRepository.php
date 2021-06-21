<?php


namespace App\Repositories;

use Exception;
use App\Models\User;
use App\Repositories\Interfaces\IUserRepository;

class UserRepository extends RepositoryBase implements IUserRepository
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function getUserByApiKey(string $apiKey): ?User
    {
        $mysqli = $this->connect();
        $cleanApiKey = $mysqli->real_escape_string($apiKey);
        $res = $mysqli->query("
SELECT
    `id`, `roleId`, `dateCreated`, `phoneNumber`, `socialNetworkUrl`, `isBlocked`, `apiKey`, `notes`, `name`
FROM ".$this->config->getUsersTableName()."
WHERE apiKey='$cleanApiKey'");

        if(!$res || !$res->data_seek(0))
        {
            return null;
        }
        $row = $res->fetch_assoc();
        $mysqli->close();

        return new User(
            $row["id"],
            $row["roleId"],
            $row["dateCreated"],
            $row["phoneNumber"],
            $row["socialNetworkUrl"],
            $row["isBlocked"],
            $row["apiKey"],
            $row["notes"],
            $row["name"]
        );
    }

    /**
     * @inheritDoc
     */
    public function getUserById(int $userId): ?User
    {
        $mysqli = $this->connect();
        $res = $mysqli->query("
SELECT
    `id`, `roleId`, `dateCreated`, `phoneNumber`, `socialNetworkUrl`, `isBlocked`, `apiKey`, `notes`, `name`
FROM ".$this->config->getUsersTableName()."
WHERE `id` = '$userId'");

        if(!$res || !$res->data_seek(0))
        {
            return null;
        }
        $row = $res->fetch_assoc();
        $mysqli->close();

        return new User(
            $row["id"],
            $row["roleId"],
            $row["dateCreated"],
            $row["phoneNumber"],
            $row["socialNetworkUrl"],
            $row["isBlocked"],
            $row["apiKey"],
            $row["notes"],
            $row["name"]
        );
    }
    
    /**
     * @inheritDoc
     */
    public function get(): array
    {
        $mysqli = $this->connect();
        $res = $mysqli->query("
SELECT
    `id`, `roleId`, `dateCreated`, `phoneNumber`, `socialNetworkUrl`, `isBlocked`, `apiKey`, `notes`, `name`
FROM ".$this->config->getUsersTableName());
    
        if(!$res || !$res->data_seek(0))
        {
            return [];
        }
        
        $users = [];
        while($row = $res->fetch_assoc())
        {
            $users[] = new User(
                $row["id"],
                $row["roleId"],
                $row["dateCreated"],
                $row["phoneNumber"],
                $row["socialNetworkUrl"],
                $row["isBlocked"],
                $row["apiKey"],
                $row["notes"],
                $row["name"]
            );
        }
        
        $mysqli->close();
        
        return array_values($users);
    }

	/**
	 * @inheritDoc
	 */
	public function insert(User $user): bool
	{
		$mysqli = $this->connect();

		$query = "
            INSERT INTO `".$this->config->getUsersTableName()."`
            (
            	`roleId`,
            	`dateCreated`,
            	`apiKey`
            )
            VALUES (
                ?,
                ?,
            	?
            )";

		$statement = $mysqli->prepare($query);

		$roleId = $user->getRoleId();
		$dateCreated = $user->getDateCreated();
		$apiKey = $user->getApiKey();

		$statement->bind_param(
		    'iis',
			$roleId,
			$dateCreated,
			$apiKey,
		);

		$result = $statement->execute();

		$mysqli->close();

		return !!$result;
	}
    
    /**
     * @inheritDoc
     */
    public function update(User $user): bool
    {
        $mysqli = $this->connect();
        
        $query = "
            UPDATE ".$this->config->getUsersTableName()."
            SET `roleId` = ?,
                `phoneNumber` = ?,
                `socialNetworkUrl` = ?,
                `isBlocked` = ?,
                `apiKey` = ?,
                `notes` = ?,
                `name` = ?
            WHERE id=".$user->getUserId();

        $statement = $mysqli->prepare($query);
        
        $roleId = $user->getRoleId();
        $phoneNumber = $user->getPhoneNumber();
        $socialNetworkUrl = $user->getSocialNetworkUrl();
        $apiKey = $user->getApiKey();
        $isBlocked = (int)$user->isBlocked();
        $notes = $user->getNotes();
        $name = $user->getName();
        
        $statement->bind_param(
            'ississs',
            $roleId,
            $phoneNumber,
            $socialNetworkUrl,
            $isBlocked,
            $apiKey,
            $notes,
            $name
        );
       
        $result = $statement->execute();
        
        $mysqli->close();

        return !!$result; //TODO delete this bool
    }
}
