<?php


namespace App\DTOs;


class TableDetailDTO extends TableDtoBase
{
    public ?int $dateExpired;
    
    public ?string $yandexToken;
    
    public ?string $avitoClientId;
    
    public ?string $avitoClientSecret;
    
    public ?string $avitoUserId;
    
    /**
     * @param string|null $yandexToken
     *
     * @return TableDetailDTO
     */
    public function setYandexToken(?string $yandexToken): TableDetailDTO
    {
        $this->yandexToken = $yandexToken;
        return $this;
    }
    
    /**
     * @param string|null $avitoClientId
     *
     * @return TableDetailDTO
     */
    public function setAvitoClientId(?string $avitoClientId): TableDetailDTO
    {
        $this->avitoClientId = $avitoClientId;
        return $this;
    }
    
    /**
     * @param string|null $avitoClientSecret
     *
     * @return TableDetailDTO
     */
    public function setAvitoClientSecret(?string $avitoClientSecret): TableDetailDTO
    {
        $this->avitoClientSecret = $avitoClientSecret;
        return $this;
    }
    
    /**
     * @param string|null $avitoUserId
     *
     * @return TableDetailDTO
     */
    public function setAvitoUserId(?string $avitoUserId): TableDetailDTO
    {
        $this->avitoUserId = $avitoUserId;
        return $this;
    }

    /**
     * @param int|null $dateExpired
     * @return TableDetailDTO
     */
    public function setDateExpired(?int $dateExpired): TableDetailDTO
    {
        $this->dateExpired = $dateExpired;
        return $this;
    }
}
