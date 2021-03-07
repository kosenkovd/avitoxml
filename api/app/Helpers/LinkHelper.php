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
        return "http://avitoxml.beget.tech/api/tables/".$tableGuid."/generators/".$generatorGuid;
    }

    public static function getPictureDownloadLink(string $fileID) : string
    {
        return "http://avitoxml.beget.tech/pictures/pictureWrapper.php?fileID=".$fileID;
    }

    public static function getYandexPictureDownloadLink(string $tableId, string $fileInfo) : string
    {
        return "http://avitoxml.beget.tech/api/tables/".$tableId."/yandexFile?fileInfo=".$fileInfo;
    }

    public static function getTestPictureDownloadLink(string $tableId, string $fileInfo) : string
    {
        return "http://agishev-autoz.ru/tables/".$tableId."/images/".$fileInfo;
    }
}
