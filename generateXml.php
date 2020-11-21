<?php
    require_once('../../vendor/autoload.php');
    require_once(__DIR__."/autoload.php");
    require_once(__DIR__ . "/services/config/config.php");
# Загружаем все необходимые документы
    require_once(__DIR__ . "/functions.php");

# Если не указан хэш, значит это левые люди и им тут делать нечего
    checkHash(Constants::getGenerateXmlHash());
    
    if ($_GET["generatorID"] == null) {
        echo View::GenerateResponseBody(View::$TPL_MISSING_TABLE_ID);
        exit;
    }
    $generatorID = $_GET["generatorID"];
    $configs = json_decode(file_get_contents(Constants::getTablesFile()), true);
    if ($configs[$generatorID] == null) {
        echo View::GenerateResponseBody(View::$TPL_INCORRECT_TABLE_ID);
        exit;
    }
    
    header('Content-Type: text/xml; charset=utf-8');
    generateXmlForTable($configs[$generatorID], $service);
