<?php


namespace App\Models;


class TableUpdateLock
{
    private ?int $tableUpdateLockId;

    private int $tableId;

    private int $fillImagesLock;

    private int $randomizeTextLock;

    /**
     * TableUpdateLock constructor.
     * @param int|null $tableUpdateLockId
     * @param int $tableId
     * @param int $fillImagesLock
     * @param int $randomizeTextLock
     */
    public function __construct(?int $tableUpdateLockId, int $tableId, int $fillImagesLock, int $randomizeTextLock)
    {
        $this->tableUpdateLockId = $tableUpdateLockId;
        $this->tableId = $tableId;
        $this->fillImagesLock = $fillImagesLock;
        $this->randomizeTextLock = $randomizeTextLock;
    }

    /**
     * @return int|null
     */
    public function getTableUpdateLockId(): ?int
    {
        return $this->tableUpdateLockId;
    }

    /**
     * @param int|null $tableUpdateLockId
     * @return TableUpdateLock
     */
    public function setTableUpdateLockId(?int $tableUpdateLockId): TableUpdateLock
    {
        $this->tableUpdateLockId = $tableUpdateLockId;
        return $this;
    }

    /**
     * @return int
     */
    public function getTableId(): int
    {
        return $this->tableId;
    }

    /**
     * @param int $tableId
     * @return TableUpdateLock
     */
    public function setTableId(int $tableId): TableUpdateLock
    {
        $this->tableId = $tableId;
        return $this;
    }

    /**
     * @return int
     */
    public function getFillImagesLock(): int
    {
        return $this->fillImagesLock;
    }

    /**
     * @param int $fillImagesLock
     * @return TableUpdateLock
     */
    public function setFillImagesLock(int $fillImagesLock): TableUpdateLock
    {
        $this->fillImagesLock = $fillImagesLock;
        return $this;
    }

    /**
     * @return int
     */
    public function getRandomizeTextLock(): int
    {
        return $this->randomizeTextLock;
    }

    /**
     * @param int $randomizeTextLock
     * @return TableUpdateLock
     */
    public function setRandomizeTextLock(int $randomizeTextLock): TableUpdateLock
    {
        $this->randomizeTextLock = $randomizeTextLock;
        return $this;
    }
}
