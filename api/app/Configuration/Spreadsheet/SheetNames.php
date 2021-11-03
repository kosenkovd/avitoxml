<?php


namespace App\Configuration\Spreadsheet;


class SheetNames
{
    private string $avito;
    private string $youla;
    private string $yandex;
    private string $information;
    private string $yandexSettings;
    private string $ozon;

    public function __construct()
    {
        $configurationJson =  json_decode(file_get_contents(__dir__. '/../appsettings.json'), true);
        foreach ($configurationJson["spreadsheet"]["sheetNames"] as $key => $value)
        {
            if(property_exists(SheetNames::class, $key))
            {
                $this->$key = strval($value);
            }
        }
    }

    /**
     * @return string
     */
    public function getAvito(): string
    {
        return $this->avito;
    }

    /**
     * @return string
     */
    public function getYoula(): string
    {
        return $this->youla;
    }

    /**
     * @return string
     */
    public function getYandex(): string
    {
        return $this->yandex;
    }

    /**
     * @return string
     */
    public function getInformation(): string
    {
        return $this->information;
    }

    /**
     * @return string
     */
    public function getYandexSettings(): string
    {
        return $this->yandexSettings;
    }
    
    /**
     * @return string
     */
    public function getOzon(): string
    {
        return $this->ozon;
    }
}
