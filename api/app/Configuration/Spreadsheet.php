<?php


namespace App\Configuration;


class Spreadsheet
{
    public function __construct()
    {
        $configurationJson =  json_decode(file_get_contents(__dir__. '/appsettings.json'), true);
        foreach ($configurationJson["spreadsheet"] as $key => $value)
        {
            if(property_exists(Spreadsheet::class, $key))
            {
                $this->$key = strval($value);
            }
        }
    }
}
