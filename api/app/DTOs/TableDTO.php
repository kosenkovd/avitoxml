<?php


namespace App\DTOs;


class TableDTO extends TableDtoBase
{
    public ?string $dateExpired;

    public bool $isActive;

    /**
     * TableDTO constructor.
     * @param int $tableId
     * @param int $userId
     * @param string|null $userPhoneNumber
     * @param string|null $userSocialNetworkUrl
     * @param string $googleSheetUrl
     * @param string $googleDriveUrl
     * @param array $generatorUrls
     * @param string|null $notes
     * @param string|null $dateExpired
     * @param bool $isActive
     */
    public function __construct(
        int $tableId,
        int $userId,
        ?string $userPhoneNumber,
        ?string $userSocialNetworkUrl,
        string $googleSheetUrl,
        string $googleDriveUrl,
        array $generatorUrls,
        ?string $notes,
        ?string $dateExpired,
        bool $isActive)
    {
        parent::__construct(
            $tableId,
            $userId,
            $userPhoneNumber,
            $userSocialNetworkUrl,
            $googleSheetUrl,
            $googleDriveUrl,
            $generatorUrls,
            $notes);

        $this->dateExpired = $dateExpired;
        $this->isActive = $isActive;
    }
}
