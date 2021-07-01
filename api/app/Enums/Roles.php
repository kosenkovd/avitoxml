<?php

namespace App\Enums;

/**
 * Class Roles
 *
 * @property int $Admin
 * @property int $Customer
 */
class Roles
{
    private static int $Admin = 1;
    private static int $Customer = 2;
    
    public function __get($name)
    {
        return self::$$name;
    }
}
