<?php


namespace App\DTOs;


class TableDTO extends TableDtoBase
{
    public ?string $dateExpired;

    public bool $isActive;

    public bool $isYandexTokenPresent;

    /**
     * TableDTO constructor.
     * @param int $tableId
     * @param int $userId
     * @param string|null $userPhoneNumber
     * @param string|null $userSocialNetworkUrl
     * @param string $googleSheetUrl
     * @param string|null $googleDriveUrl
     * @param GeneratorDTO[] $generators
     * @param string|null $notes
     * @param string|null $dateExpired
     * @param bool $isActive
     * @param bool $isYandexTokenPresent
     */
    public function __construct(
        int $tableId,
        int $userId,
        ?string $userPhoneNumber,
        ?string $userSocialNetworkUrl,
        string $googleSheetUrl,
        ?string $googleDriveUrl,
        array $generators,
        ?string $notes,
        ?string $dateExpired,
        bool $isActive,
        bool $isYandexTokenPresent)
    {
        parent::__construct(
            $tableId,
            $userId,
            $userPhoneNumber,
            $userSocialNetworkUrl,
            $googleSheetUrl,
            $googleDriveUrl,
            $generators,
            $notes);

        $this->dateExpired = $dateExpired;
        $this->isActive = $isActive;
        $this->isYandexTokenPresent = $isYandexTokenPresent;
    }
}
