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

    public static function getXmlGeneratorLink(string $tableGuid, string $generatorGuid) : string
    {
        return "http://avitoxml.beget.tech/api/tables/".$tableGuid."/generators/".$generatorGuid;
    }

    public static function getPictureDownloadLink(string $fileID) : string
    {
        return "http://avitoxml.beget.tech/pictures/pictureWrapper.php?fileID=".$fileID;
    }
}
