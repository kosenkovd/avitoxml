<?php


namespace App\Configuration;


class XmlGeneration
{
    private ?string $avitoTabs = null;

    private ?string $youlaTabs = null;

    private ?string $yandexTabs = null;
    
    private ?string $ozonTabs = null;

    private ?string $multimarketTabs = null;

    public function __construct()
    {
        $configurationJson =  json_decode(file_get_contents(__dir__. '/appsettings.json'), true);
        foreach ($configurationJson["xmlGeneration"] as $key => $value)
        {
            if(property_exists(XmlGeneration::class, $key))
            {
                $this->$key = strval($value);
            }
        }
    }

    /**
     * @return string|null
     */
    public function getAvitoTabs(): ?string
    {
        return $this->avitoTabs;
    }

    /**
     * @return string|null
     */
    public function getYoulaTabs(): ?string
    {
        return $this->youlaTabs;
    }

    /**
     * @return string|null
     */
    public function getYandexTabs(): ?string
    {
        return $this->yandexTabs;
    }

    /**
     * @return string|null
     */
    public function getOzonTabs(): ?string
    {
        return $this->ozonTabs;
    }
    
    /**
     * @return string|null
     */
    public function getMultimarketTabs(): ?string
    {
        return $this->multimarketTabs;
    }
}
