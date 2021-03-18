<?php


namespace App\Mappers;

use App\Models\User;
use App\DTOs\UserDTO;
use Carbon\Carbon;
use \DateTime;

class UserDTOMapper
{
    public static function mapModelToDTO(User $user) : UserDTO
    {
        $dateCreated = new DateTime();
        $dateCreated->setTimestamp($user->getDateCreated());
        $dateCreatedString = $dateCreated->format(DateTime::ISO8601);
        
        $userDTO = new UserDTO();
        $userDTO
            ->setUserId($user->getUserId())
            ->setRoleId($user->getRoleId())
            ->setDateCreated($dateCreatedString)
            ->setPhoneNumber($user->getPhoneNumber())
            ->setSocialNetworkUrl($user->getSocialNetworkUrl())
            ->setIsBlocked($user->isBlocked())
            ->setNotes($user->getNotes())
            ->setName($user->getName())
            ->setToken($user->getApiKey());
        
        return $userDTO;
    }
    
    public static function mapDTOToModel(UserDTO $userDTO) : User
    {
        $dateCreated = Carbon::createFromTimeString($userDTO->dateCreated)->timestamp;
        
        return new User(
            $userDTO->userId,
            $userDTO->roleId,
            $dateCreated,
            $userDTO->phoneNumber,
            $userDTO->socialNetworkUrl,
            $userDTO->isBlocked,
            $userDTO->token,
            $userDTO->notes,
            $userDTO->name
        );
    }
}
