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
     */
    public function getUserByApiKey(string $apiKey) : ?User;
    
    /**
     * Find user by userId.
     *
     * @param int $userId user id
     * @return User|null user if found, otherwise null.
     */
    public function getUserById(int $userId) : ?User;
    
    /**
     * Get all users.
     *
     * @return User[]
     */
    public function get() : array;

	/**
     * Persist new user in database
     *
	 * @param User $user
	 * @return bool
	 */
    public function insert(User $user) : bool;
    
    /**
     * Update user
     *
     * @param User $user
     * @return bool
     */
    public function update(User $user) : bool;
}
