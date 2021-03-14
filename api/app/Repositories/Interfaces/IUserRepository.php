<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Exception;

interface IUserRepository
{
    /**
     * Find user by api key.
     *
     * @param string $apiKey user api key
     * @return User|null user if found, otherwise null.
     * @throws Exception in case of DB connection failure.
     */
    public function getUserByApiKey(string $apiKey) : ?User;
    
    /**
     * Get all users.
     *
     * @return User[]|null
     * @throws Exception in case of DB connection failure.
     */
    public function getUsers() : ?array;
    
    /**
     * @param int $userId
     * @param User $user
     * @return bool
     */
    public function updateUser(int $userId, User $user) : bool;
    
    /**
     * @param int $userId
     * @param string $newApiKey
     * @return bool
     */
    public function updateApiKey(int $userId, string $newApiKey) : bool;
}
