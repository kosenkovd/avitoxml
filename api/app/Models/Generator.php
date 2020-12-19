<?php


namespace App\Models;


class Generator
{
    private ?int $generatorId;

    private int $tableId;

    private string $generatorGuid;

    private ?int $lastGenerated;

    private string $targetPlatform;

    /**
     * Generator constructor.
     * @param ?int $generatorId
     * @param int $tableId
     * @param string $generatorGuid
     * @param int|null $lastGenerated
     * @param string $targetPlatform
     */
    public function __construct(?int $generatorId, int $tableId, string $generatorGuid, ?int $lastGenerated, string $targetPlatform)
    {
        $this->generatorId = $generatorId;
        $this->tableId = $tableId;
        $this->generatorGuid = $generatorGuid;
        $this->lastGenerated = $lastGenerated;
        $this->targetPlatform = $targetPlatform;
    }

    /**
     * @return int
     */
    public function getGeneratorId(): ?int
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

    /**
     * @param int|null $generatorId
     * @return Generator
     */
    public function setGeneratorId(?int $generatorId): Generator
    {
        $this->generatorId = $generatorId;
        return $this;
    }

    /**
     * @param int|null $lastGenerated
     * @return Generator
     */
    public function setLastGenerated(?int $lastGenerated): Generator
    {
        $this->lastGenerated = $lastGenerated;
        return $this;
    }

    /**
     * @return string
     */
    public function getTargetPlatform(): string
    {
        return $this->targetPlatform;
    }

    /**
     * @param string $targetPlatform
     * @return Generator
     */
    public function setTargetPlatform(string $targetPlatform): Generator
    {
        $this->targetPlatform = $targetPlatform;
        return $this;
    }
}
