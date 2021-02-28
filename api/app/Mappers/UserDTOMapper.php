<?php


namespace App\Mappers;

use App\DTOs\UserRowDTO;
use App\Models\User;
use App\DTOs\UserBaseDTO;
use \DateTime;

class UserDTOMapper
{
    public static function mapUserInfo(User $user) : UserBaseDTO
    {
        $dateCreated = new DateTime();
        $dateCreated->setTimestamp($user->getDateCreated());
        $dateCreatedString = $dateCreated->format(DateTime::ISO8601);

        return new UserBaseDTO(
            $user->getUserId(),
            $user->getRoleId(),
            $dateCreatedString,
            $user->getPhoneNumber(),
            $user->getSocialNetworkUrl(),
            $user->isBlocked(),
            $user->getNotes(),
            $user->getName()
        );
    }
    
    public static function mapUserRow(User $user) : UserRowDTO
    {
        $dateCreated = new DateTime();
        $dateCreated->setTimestamp($user->getDateCreated());
        $dateCreatedString = $dateCreated->format(DateTime::ISO8601);

        return new UserRowDTO(
            $user->getUserId(),
            $user->getRoleId(),
            $dateCreatedString,
            $user->getPhoneNumber(),
            $user->getSocialNetworkUrl(),
            $user->isBlocked(),
            $user->getNotes(),
            $user->getName(),
            $user->getToken()
        );
    }
}
