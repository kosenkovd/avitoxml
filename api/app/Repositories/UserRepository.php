<?php


namespace App\Repositories;

use Exception;
use mysqli;
use App\Models\User;
use App\Repositories\Interfaces\IUserRepository;

class UserRepository extends RepositoryBase implements IUserRepository
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Find user by api key.
     *
     * @param string $apiKey user api key
     * @return User|null user if found, otherwise null.
     * @throws Exception in case of DB connection failure.
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
            $row["name"],
            null
        );
    }
    
    /**
     * @return array|null
     * @throws Exception
     */
    public function getUsers(): ?array
    {
        $mysqli = $this->connect();
        $res = $mysqli->query("
SELECT
    `id`, `roleId`, `dateCreated`, `phoneNumber`, `socialNetworkUrl`, `isBlocked`, `apiKey`, `notes`, `name`, `apiKey`
FROM ".$this->config->getUsersTableName());
    
        if(!$res || !$res->data_seek(0))
        {
            return null;
        }
        
        $users = [];
        while($row = $res->fetch_assoc())
        {
            $token = $row["apiKey"];
            $users[] = new User(
                $row["id"],
                $row["roleId"],
                $row["dateCreated"],
                $row["phoneNumber"],
                $row["socialNetworkUrl"],
                $row["isBlocked"],
                $row["apiKey"],
                $row["notes"],
                $row["name"],
                $token
            );
        }
        
        $mysqli->close();
        
        return array_values($users);
    }
}
