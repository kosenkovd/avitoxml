<?php


namespace App\Models;


class Generator
{
    private int $generatorId;

    private int $tableId;

    private string $generatorGuid;

    private ?int $lastGenerated;

    /**
     * Generator constructor.
     * @param int $generatorId
     * @param int $tableId
     * @param string $generatorGuid
     * @param int|null $lastGenerated
     */
    public function __construct(int $generatorId, int $tableId, string $generatorGuid, ?int $lastGenerated)
    {
        $this->generatorId = $generatorId;
        $this->tableId = $tableId;
        $this->generatorGuid = $generatorGuid;
        $this->lastGenerated = $lastGenerated;
    }

    /**
     * @return int
     */
    public function getGeneratorId(): int
    {
        return $this->generatorId;
    }

    /**
     * @return int
     */
    public function getTableId(): int
    {
        return $this->tableId;
    }

    /**
     * @return string
     */
    public function getGeneratorGuid(): string
    {
        return $this->generatorGuid;
    }

    /**
     * @return int|null
     */
    public function getLastGenerated(): ?int
    {
        return $this->lastGenerated;
    }
}
