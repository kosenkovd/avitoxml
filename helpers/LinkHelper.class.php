<?php

require_once(__DIR__ . "/Constants.class.php");

// Класс, генерирующий ссылки на ресурсы по данному идентификатору
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
    
    public static function getXmlGeneratorLink(string $generatorID) : string
    {
        return "http://avitoxml.beget.tech/generateXml.php?hash=".
            Constants::getGenerateXmlHash()."&generatorID=".$generatorID;
    }
    
    public static function getPictureDownloadLink(string $fileID) : string
    {
        return "http://avitoxml.beget.tech/pictures/pictureWrapper.php?fileID=".$fileID;
    }
}

?>