<?php

namespace App\Repositories\Interfaces;

interface ITableRepository
{
    public function getTables(?int $userId = null) : array;
}
