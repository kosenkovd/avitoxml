<?php


namespace App\Configuration;


class XmlGeneration
{
    private ?string $avitoTabs = null;

    private ?string $avitoFirstLimit = null;

    private ?string $avitoDefaultLimit = null;

    private ?string $youlaTabs = null;

    private ?string $youlaFirstLimit = null;

    private ?string $youlaDefaultLimit = null;

    private ?string $yandexTabs = null;

    private ?string $yandexFirstLimit = null;

    private ?string $yandexDefaultLimit = null;

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
    public function getAvitoFirstLimit(): ?string
    {
        return $this->avitoFirstLimit;
    }

    /**
     * @return string|null
     */
    public function getAvitoDefaultLimit(): ?string
    {
        return $this->avitoDefaultLimit;
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
    public function getYoulaFirstLimit(): ?string
    {
        return $this->youlaFirstLimit;
    }

    /**
     * @return string|null
     */
    public function getYoulaDefaultLimit(): ?string
    {
        return $this->youlaDefaultLimit;
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
    public function getYandexFirstLimit(): ?string
    {
        return $this->yandexFirstLimit;
    }

    /**
     * @return string|null
     */
    public function getYandexDefaultLimit(): ?string
    {
        return $this->yandexDefaultLimit;
    }
}
