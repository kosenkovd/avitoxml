<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface IUserRepository
{
    public function getUserByApiKey(string $apiKey) : ?User;
}
