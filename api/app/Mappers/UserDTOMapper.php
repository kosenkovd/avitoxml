<?php


namespace App\Mappers;

use App\Models\User;
use App\DTOs\UserDTO;
use Carbon\Carbon;
use \DateTime;

class UserDTOMapper
{
    public static function mapModelToUserDTO(User $user) : UserDTO
    {
        $dateCreated = new DateTime();
        $dateCreated->setTimestamp($user->getDateCreated());
        $dateCreatedString = $dateCreated->format(DateTime::ISO8601);

        return new UserDTO(
            $user->getUserId(),
            $user->getRoleId(),
            $dateCreatedString,
            $user->getPhoneNumber(),
            $user->getSocialNetworkUrl(),
            $user->isBlocked(),
            $user->getNotes(),
            $user->getName(),
            $user->getApiKey()
        );
    }
    
    public static function mapUserDTOToModel(UserDTO $userDTO) : User
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
