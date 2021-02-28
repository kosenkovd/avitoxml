<?php


namespace App\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;
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
            $row["name"]
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
    `id`, `roleId`, `dateCreated`, `phoneNumber`, `socialNetworkUrl`, `isBlocked`, `apiKey`, `notes`, `name`
FROM ".$this->config->getUsersTableName());
    
        if(!$res || !$res->data_seek(0))
        {
            return null;
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
     * @throws Exception
     */
    public function updateUser(int $userId, User $user): bool
    {
        $mysqli = $this->connect();
        $res = $mysqli->query("
            UPDATE ".$this->config->getUsersTableName()."
            SET
                `roleId` = ".$user->getRoleId().",
                `dateCreated` = ".$user->getDateCreated().",
                `phoneNumber` = ".$user->getPhoneNumber().",
                `socialNetworkUrl` = ".$user->getSocialNetworkUrl().",
                `isBlocked` = ".$user->isBlocked().",
                `apiKey` = ".$user->getApiKey().",
                `notes` = ".$user->getNotes().",
                `name` = ".$user->getName()."
            FROM ".$this->config->getUsersTableName()."
            WHERE id=".$user->getUserId());

        if(!$res || !$res->data_seek(0))
        {
            throw new Exception('User does not exists');
        }
        $row = $res->fetch_assoc();
        $mysqli->close();

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
}
