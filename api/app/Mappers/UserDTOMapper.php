<?php


namespace App\Mappers;

use App\Models\User;
use App\DTOs\UserDTO;
use \DateTime;

class UserDTOMapper
{
    public static function mapUser(User $user) : UserDTO
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
            $user->getNotes()
        );
    }
}
