<?php


namespace App\DTOs;


class TableDTO extends TableDtoBase
{
    public ?string $dateExpired;

    public bool $isActive;

    public bool $isYandexTokenPresent;
    
    /**
     * @param string|null $dateExpired
     * @return TableDTO
     */
    public function setDateExpired(?string $dateExpired): TableDTO
    {
        $this->dateExpired = $dateExpired;
        return $this;
    }
    
    /**
     * @param bool $isActive
     * @return TableDTO
     */
    public function setIsActive(bool $isActive): TableDTO
    {
        $this->isActive = $isActive;
        return $this;
    }
    
    /**
     * @param bool $isYandexTokenPresent
     * @return TableDTO
     */
    public function setIsYandexTokenPresent(bool $isYandexTokenPresent): TableDTO
    {
        $this->isYandexTokenPresent = $isYandexTokenPresent;
        return $this;
    }
}
