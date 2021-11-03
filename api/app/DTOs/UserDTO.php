<?php


namespace App\DTOs;


class UserDTO
{
    public int $userId;

    public int $roleId;

    public string $dateCreated;

    public ?string $phoneNumber;

    public ?string $socialNetworkUrl;

    public bool $isBlocked;

    public ?string $notes;
    
    public ?string $name;
    
    public ?string $token;
    
    public ?string $email;
    
    public ?bool $hasVerifyEmail;

    public ?string $balance;
    
    /**
     * @param string|null $balance
     *
     * @return UserDTO
     */
    public function setBalance(?string $balance): UserDTO
    {
        $this->balance = $balance;
        return $this;
    }
    
    /**
     * @param int $userId
     * @return UserDTO
     */
    public function setUserId(int $userId): UserDTO
    {
        $this->userId = $userId;
        return $this;
    }
    
    /**
     * @param int $roleId
     * @return UserDTO
     */
    public function setRoleId(int $roleId): UserDTO
    {
        $this->roleId = $roleId;
        return $this;
    }
    
    /**
     * @param string $dateCreated
     * @return UserDTO
     */
    public function setDateCreated(string $dateCreated): UserDTO
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }
    
    /**
     * @param string|null $phoneNumber
     * @return UserDTO
     */
    public function setPhoneNumber(?string $phoneNumber): UserDTO
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }
    
    /**
     * @param string|null $socialNetworkUrl
     * @return UserDTO
     */
    public function setSocialNetworkUrl(?string $socialNetworkUrl): UserDTO
    {
        $this->socialNetworkUrl = $socialNetworkUrl;
        return $this;
    }
    
    /**
     * @param bool $isBlocked
     * @return UserDTO
     */
    public function setIsBlocked(bool $isBlocked): UserDTO
    {
        $this->isBlocked = $isBlocked;
        return $this;
    }
    
    /**
     * @param string|null $notes
     * @return UserDTO
     */
    public function setNotes(?string $notes): UserDTO
    {
        $this->notes = $notes;
        return $this;
    }
    
    /**
     * @param string|null $name
     * @return UserDTO
     */
    public function setName(?string $name): UserDTO
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * @param string|null $token
     * @return UserDTO
     */
    public function setToken(?string $token): UserDTO
    {
        $this->token = $token;
        return $this;
    }
    
    /**
     * @param string|null $email
     *
     * @return UserDTO
     */
    public function setEmail(?string $email): UserDTO
    {
        $this->email = $email;
        return $this;
    }
    
    /**
     * @param bool|null $hasVerifyEmail
     *
     * @return UserDTO
     */
    public function setHasVerifyEmail(?bool $hasVerifyEmail): UserDTO
    {
        $this->hasVerifyEmail = $hasVerifyEmail;
        return $this;
    }
    
    /**
     * UserDTO constructor.
     */
    public function __construct(
    )
    {
    }
    
}
