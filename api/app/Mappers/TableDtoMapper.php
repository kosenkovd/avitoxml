<?php


namespace App\Mappers;

use App\Models;
use App\Helpers\LinkHelper;

class TableDtoMapper
{
    private static function GetGeneratorUrls(array $generators, Models\User $user)
    {
        $generatorUrls = [];
        foreach ($generators as $generator)
        {
            $generatorUrls[] = LinkHelper::getXmlGeneratorLink($generator->getGeneratorId(), $user->getApiKey());
        }

        return $generatorUrls;
    }

    public static function MapTableDTO(Models\Table $table, Models\User $user, array $generators) : Models\TableDTO
    {

        if(is_null($table->getDateExpired()))
        {
            $dateExpiredString = null;
        }
        else
        {
            $dateExpired = new DateTime();
            $dateExpired->setTimestamp($table->getDateExpired());
            $dateExpiredString = $dateExpired->format(DateTime::ISO8601);
        }

        $isActive = !$user->isBlocked() && (is_null($table->getDateExpired()) || $table->getDateExpired() > time());

        return new Models\TableDTO(
            $table->getTableId(),
            $user->getUserId(),
            $user->getPhoneNumber(),
            $user->getSocialNetworkUrl(),
            LinkHelper::getGoogleSpreadsheetLink($table->getGoogleSheetId()),
            LinkHelper::getGoogleDriveFolderLink($table->getGoogleDriveId()),
            self::GetGeneratorUrls($generators, $user),
            $dateExpiredString,
            $isActive);
    }

    public static function MapDeletedTableDTO(Models\Table $table, Models\User $user, array $generators)
        : Models\DeletedTableDTO
    {
        return new Models\DeletedTableDTO(
            $table->getTableId(),
            $user->getUserId(),
            $user->getPhoneNumber(),
            $user->getSocialNetworkUrl(),
            LinkHelper::getGoogleSpreadsheetLink($table->getGoogleSheetId()),
            LinkHelper::getGoogleDriveFolderLink($table->getGoogleDriveId()),
            self::GetGeneratorUrls($generators, $user),
            $table->getDateDeleted());
    }
}
