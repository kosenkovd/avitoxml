<?php

namespace App\Enums;

class Roles
{
    private static int $Admin = 1;
    private static int $Customer = 2;

    public function __get($name) {
        return self::$$name;
    }
}
