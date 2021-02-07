<?php


namespace App\DTOs;


class TableDtoBase
{
    public int $tableId;

    public int $userId;

    public ?string $userPhoneNumber;

    public ?string $userSocialNetworkUrl;

    public string $googleSheetUrl;

    public ?string $googleDriveUrl;

    public array $generators;

    public ?string $notes;

    /**
     * TableDtoBase constructor.
     * @param int $tableId
     * @param int $userId
     * @param string|null $userPhoneNumber
     * @param string|null $userSocialNetworkUrl
     * @param string $googleSheetUrl
     * @param string|null $googleDriveUrl
     * @param GeneratorDTO[] $generators
     * @param string|null $notes
     */
    public function __construct(
        int $tableId,
        int $userId,
        ?string $userPhoneNumber,
        ?string $userSocialNetworkUrl,
        string $googleSheetUrl,
        ?string $googleDriveUrl,
        array $generators,
        ?string $notes)
    {
        $this->tableId = $tableId;
        $this->userId = $userId;
        $this->userPhoneNumber = $userPhoneNumber;
        $this->userSocialNetworkUrl = $userSocialNetworkUrl;
        $this->googleSheetUrl = $googleSheetUrl;
        $this->googleDriveUrl = $googleDriveUrl;
        $this->generators = $generators;
        $this->notes = $notes;
    }
}
