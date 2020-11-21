<?php


namespace App\Mappers;

use DateTime;
use App\Models;
use App\DTOs;
use App\Helpers\LinkHelper;

class TableDtoMapper
{
    private static function GetGeneratorUrls(string $tableGuid, array $generators)
    {
        $generatorUrls = [];
        foreach ($generators as $generator)
        {
            $generatorUrls[] = LinkHelper::getXmlGeneratorLink($tableGuid, $generator->getGeneratorGuid());
        }

        return $generatorUrls;
    }

    public static function MapTableDTO(Models\Table $table, Models\User $user) : DTOs\TableDTO
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

        return new DTOs\TableDTO(
            $table->getTableId(),
            $user->getUserId(),
            $user->getPhoneNumber(),
            $user->getSocialNetworkUrl(),
            LinkHelper::getGoogleSpreadsheetLink($table->getGoogleSheetId()),
            LinkHelper::getGoogleDriveFolderLink($table->getGoogleDriveId()),
            self::GetGeneratorUrls($table->getTableGuid(), $table->getGenerators()),
            $table->getNotes(),
            $dateExpiredString,
            $isActive);
    }

    public static function MapDeletedTableDTO(Models\Table $table, Models\User $user)
        : DTOs\DeletedTableDTO
    {
        return new DTOs\DeletedTableDTO(
            $table->getTableId(),
            $user->getUserId(),
            $user->getPhoneNumber(),
            $user->getSocialNetworkUrl(),
            LinkHelper::getGoogleSpreadsheetLink($table->getGoogleSheetId()),
            LinkHelper::getGoogleDriveFolderLink($table->getGoogleDriveId()),
            self::GetGeneratorUrls($table->getTableGuid(), $table->getGenerators()),
            $table->getNotes(),
            $table->getDateDeleted());
    }
}
