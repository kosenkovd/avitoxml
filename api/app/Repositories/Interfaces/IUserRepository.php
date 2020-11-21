<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface IUserRepository
{
    /**
     * Find user by api key.
     *
     * @param string $apiKey user api key
     * @return User|null user if found, otherwise null.
     * @throws \Exception in case of DB connection failure.
     */
    public function getUserByApiKey(string $apiKey) : ?User;
}
