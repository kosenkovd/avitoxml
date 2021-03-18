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
     * @return User[]
     * @throws Exception in case of DB connection failure.
     */
    public function get() : array;

	/**
     * Persist new user in database
     *
	 * @param User $user
	 * @return bool
	 * @throws Exception in case of DB connection failure.
	 */
    public function insert(User $user) : bool;
    
    /**
     * Update user
     *
     * @param User $user
     * @return bool
	 * @throws Exception in case of DB connection failure.
     */
    public function update(User $user) : bool;
    
    /**
     * Update user ApiKey
     *
     * @param int $userId
     * @param string $newApiKey
     * @return bool
	 * @throws Exception in case of DB connection failure.
     */
    public function updateApiKey(int $userId, string $newApiKey) : bool;
}
