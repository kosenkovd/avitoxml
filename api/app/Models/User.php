<?php

namespace App\Models;

class User
{
    private int $userId;

    private int $roleId;

    private int $dateCreated;

    private ?string $phoneNumber;

    private ?string $socialNetworkUrl;

    private bool $isBlocked;

    private string $apiKey;

    private ?string $notes;

    /**
     * User constructor.
     * @param int $userId
     * @param int $roleId
     * @param int $dateCreated
     * @param string|null $phoneNumber
     * @param string|null $socialNetworkUrl
     * @param bool $isBlocked
     * @param string $apiKey
     * @param string|null $notes
     */
    public function __construct(
        int $userId,
        int $roleId,
        int $dateCreated,
        ?string $phoneNumber,
        ?string $socialNetworkUrl,
        bool $isBlocked,
        string $apiKey,
        ?string $notes)
    {
        $this->userId = $userId;
        $this->roleId = $roleId;
        $this->dateCreated = $dateCreated;
        $this->phoneNumber = $phoneNumber;
        $this->socialNetworkUrl = $socialNetworkUrl;
        $this->isBlocked = $isBlocked;
        $this->apiKey = $apiKey;
        $this->notes = $notes;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return int
     */
    public function getRoleId(): int
    {
        return $this->roleId;
    }

    /**
     * @return int
     */
    public function getDateCreated(): int
    {
        return $this->dateCreated;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @return string|null
     */
    public function getSocialNetworkUrl(): ?string
    {
        return $this->socialNetworkUrl;
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }
}
