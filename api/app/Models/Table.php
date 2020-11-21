<?

namespace App\Models;

class Table {
    private ?int $tableId;

    private int $userId;

    private string $googleSheetId;

    private string $googleDriveId;

    private ?int $dateExpired;

    private bool $isDeleted;

    private ?int $dateDeleted;

    private ?string $notes;

    private string $tableGuid;

    /**
     * @var Generator[] generators for table.
     */
    private array $generators;

    /**
     * Table constructor.
     * @param ?int $tableId
     * @param int $userId
     * @param string $googleSheetId
     * @param string $googleDriveId
     * @param int|null $dateExpired
     * @param bool $isDeleted
     * @param int|null $dateDeleted
     * @param string|null $notes
     * @param string $tableGuid
     * @param array $generators
     */
    public function __construct(
        ?int $tableId,
        int $userId,
        string $googleSheetId,
        string $googleDriveId,
        ?int $dateExpired,
        bool $isDeleted,
        ?int $dateDeleted,
        ?string $notes,
        string $tableGuid,
        array $generators = [])
    {
        $this->tableId = $tableId;
        $this->userId = $userId;
        $this->googleSheetId = $googleSheetId;
        $this->googleDriveId = $googleDriveId;
        $this->dateExpired = $dateExpired;
        $this->isDeleted = $isDeleted;
        $this->dateDeleted = $dateDeleted;
        $this->notes = $notes;
        $this->tableGuid = $tableGuid;
        $this->generators = $generators;
    }

    /**
     * @return int
     */
    public function getTableId(): ?int
    {
        return $this->tableId;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getGoogleSheetId(): string
    {
        return $this->googleSheetId;
    }

    /**
     * @return string
     */
    public function getGoogleDriveId(): string
    {
        return $this->googleDriveId;
    }

    /**
     * @return int|null
     */
    public function getDateExpired(): ?int
    {
        return $this->dateExpired;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    /**
     * @return int|null
     */
    public function getDateDeleted(): ?int
    {
        return $this->dateDeleted;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @return string
     */
    public function getTableGuid(): string
    {
        return $this->tableGuid;
    }

    /**
     * @return array
     */
    public function getGenerators(): array
    {
        return $this->generators;
    }

    /**
     * @param Generator $generator
     * @return Table
     */
    public function addGenerator(Generator $generator): Table
    {
        $this->generators[] = $generator;
        return $this;
    }

    /**
     * @param int|null $tableId
     * @return Table
     */
    public function setTableId(?int $tableId): Table
    {
        $this->tableId = $tableId;
        return $this;
    }
}
