<?php


namespace App\DTOs;


class UserBaseDTO
{
    public int $userId;

    public int $roleId;

    public string $dateCreated;

    public ?string $phoneNumber;

    public ?string $socialNetworkUrl;

    public bool $isBlocked;

    public ?string $notes;
    
    public ?string $name;
    
    /**
     * UserDTO constructor.
     * @param int $userId
     * @param int $roleId
     * @param string $dateCreated
     * @param string|null $phoneNumber
     * @param string|null $socialNetworkUrl
     * @param bool $isBlocked
     * @param string|null $notes
     * @param string|null $name
     */
    public function __construct(
        int $userId,
        int $roleId,
        string $dateCreated,
        ?string $phoneNumber,
        ?string $socialNetworkUrl,
        bool $isBlocked,
        ?string $notes,
        ?string $name
    )
    {
        $this->userId = $userId;
        $this->roleId = $roleId;
        $this->dateCreated = $dateCreated;
        $this->phoneNumber = $phoneNumber;
        $this->socialNetworkUrl = $socialNetworkUrl;
        $this->isBlocked = $isBlocked;
        $this->notes = $notes;
        $this->name = $name;
    }


}
