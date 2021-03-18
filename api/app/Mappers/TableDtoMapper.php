<?php


namespace App\Mappers;

use DateTime;
use App\Models;
use App\DTOs;
use App\Helpers\LinkHelper;

class TableDtoMapper
{
    /**
     * @param string $tableGuid
     * @param Models\Generator[] $generators
     * @return DTOs\GeneratorDTO[]
     */
    private static function GetGenerators(string $tableGuid, array $generators) : array
    {
        $generatorDtos = [];
        foreach ($generators as $generator)
        {
            $generatorDtos[] = new DTOs\GeneratorDTO(
                $generator->getTargetPlatform(),
                LinkHelper::getXmlGeneratorLink($tableGuid, $generator->getGeneratorGuid())
            );
        }

        return $generatorDtos;
    }

    public static function mapModelToDTO(Models\Table $table, Models\User $user) : DTOs\TableDTO
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

        $googleDriveUrl = $table->getGoogleDriveId() != null
            ? LinkHelper::getGoogleDriveFolderLink($table->getGoogleDriveId())
            : null;

        return new DTOs\TableDTO(
            $table->getTableId(),
            $user->getUserId(),
            null,
			null,
            LinkHelper::getGoogleSpreadsheetLink($table->getGoogleSheetId()),
            $googleDriveUrl,
            self::GetGenerators($table->getTableGuid(), $table->getGenerators()),
            $table->getNotes(),
            $dateExpiredString,
            $isActive,
            $table->getYandexToken() != null);
    }

    public static function MapDeletedModelToDTO(Models\Table $table, Models\User $user)
        : DTOs\DeletedTableDTO
    {
        return new DTOs\DeletedTableDTO(
            $table->getTableId(),
            $user->getUserId(),
			null,
			null,
            LinkHelper::getGoogleSpreadsheetLink($table->getGoogleSheetId()),
            LinkHelper::getGoogleDriveFolderLink($table->getGoogleDriveId()),
            self::GetGenerators($table->getTableGuid(), $table->getGenerators()),
            $table->getNotes(),
            $table->getDateDeleted());
    }
}
