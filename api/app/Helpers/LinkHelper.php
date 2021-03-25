<?php


namespace App\Helpers;


class LinkHelper
{
    public static function getGoogleSpreadsheetLink(string $tableID) : string
    {
        return "https://docs.google.com/spreadsheets/d/".$tableID;
    }

    public static function getGoogleDriveFolderLink(string $folderID) : string
    {
        return "https://drive.google.com/drive/folders/".$folderID;
    }

    /**
     * Create link for xml generation.
     *
     * @param string $tableGuid
     * @param string $generatorGuid
     * @return string
     */
    public static function getXmlGeneratorLink(string $tableGuid, string $generatorGuid) : string
    {
        return "http://xml.agishev-autoz.ru/api/tables/".$tableGuid."/generators/".$generatorGuid;
    }

    public static function getPictureDownloadLink(string $tableId, string $fileInfo) : string
    {
        return "http://agishev-autoz.ru/tables/".$tableId."/images/".$fileInfo;
    }
}
