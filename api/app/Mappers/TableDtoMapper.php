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
        $generatorDTOs = [];
        foreach ($generators as $generator)
        {
            $generatorDTO = new DTOs\GeneratorDTO();
            $generatorDTO->setTargetPlatform($generator->getTargetPlatform())
                ->setGeneratorUrl(LinkHelper::getXmlGeneratorLink($tableGuid, $generator->getGeneratorGuid()))
                ->setGeneratorGuid($generator->getGeneratorGuid())
                ->setMaxAds($generator->getMaxAds());
            
            $generatorDTOs[] = $generatorDTO;
        }

        return $generatorDTOs;
    }

    public static function mapModelToDTO(Models\Table $table, Models\User $user) : DTOs\TableDTO
    {
        $isActive = !$user->isBlocked() && (is_null($table->getDateExpired()) || $table->getDateExpired() > time());

        $googleDriveUrl = $table->getGoogleDriveId() != null
            ? LinkHelper::getGoogleDriveFolderLink($table->getGoogleDriveId())
            : null;

        $tableDTO = new DTOs\TableDTO();
        $tableDTO->setTableId($table->getTableId())
            ->setUserId($table->getUserId())
            ->setTableGuid($table->getTableGuid())
            ->setGoogleSheetId($table->getGoogleSheetId())
            ->setGoogleSheetUrl(LinkHelper::getGoogleSpreadsheetLink($table->getGoogleSheetId()))
            ->setGoogleDriveUrl($googleDriveUrl)
            ->setGenerators(self::GetGenerators($table->getTableGuid(), $table->getGenerators()))
            ->setTableNotes($table->getNotes())
            ->setDateExpired($table->getDateExpired())
            ->setIsActive($isActive)
            ->setIsYandexTokenPresent($table->getYandexToken() != null);
        
        return $tableDTO;
    }
}
