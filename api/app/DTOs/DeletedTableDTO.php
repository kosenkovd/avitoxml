<?php


namespace App\DTOs;


class DeletedTableDTO extends TableDtoBase
{
    public string $dateDeleted;

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
     * @param string $dateDeleted
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
        string $dateDeleted)
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

        $this->dateDeleted = $dateDeleted;
    }
}
