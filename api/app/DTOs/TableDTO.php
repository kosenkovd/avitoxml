<?php


namespace App\DTOs;


class TableDTO extends TableDtoBase
{
    public ?int $dateExpired;

    public bool $isActive;

    public bool $isYandexTokenPresent;
    
    public string $type;
    
    /**
     * @param int|null $dateExpired
     * @return TableDTO
     */
    public function setDateExpired(?int $dateExpired): TableDTO
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
    
    /**
     * @param string $type
     *
     * @return TableDTO
     */
    public function setType(string $type): TableDTO
    {
        $this->type = $type;
        return $this;
    }
}
