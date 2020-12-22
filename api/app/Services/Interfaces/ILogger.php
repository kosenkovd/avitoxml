<?php


namespace App\Services\Interfaces;


interface ILogger
{
    public function log(string $message) : void;
}
