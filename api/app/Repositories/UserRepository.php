<?php


namespace App\Repositories;


use App\Repositories\Interfaces\IUserRepository;

class UserRepository extends RepositoryBase implements IUserRepository
{
    function __construct()
    {
        parent::__construct();
    }
}
