<?php
    # Загружаем все необходимые файлы
    require_once('../../vendor/autoload.php');
    require_once(__DIR__."/autoload.php");
    require_once(__DIR__ . "/functions.php");
    require_once($_SERVER["DOCUMENT_ROOT"] . '/services/config/config.php');
    
    # Если не указан хэш, значит это левые люди и им тут делать нечего
    checkHash(Constants::getCreateTableHash());
    
    $configs = json_decode(file_get_contents(Constants::getTablesFile()), true);
    # Создаем новую таблицу и базовую папку
    $tableCreator = new CreateNewTable(
        $copySpreadsheetId,
        $client,
        $drivePermissions
    );
    $tableData = $tableCreator->create();
    
    $newGeneratorID = generateGuid();
    $newConfig =  [
        "tableID" => $tableData["newTableID"],
        "folderID" => $tableData["newFolderID"],
        "generatorID" => $newGeneratorID
    ];
    
    $linkData = [
        "tableLink" => LinkHelper::getGoogleSpreadsheetLink($newConfig["tableID"]),
        "folderLink" => LinkHelper::getGoogleDriveFolderLink($newConfig["folderID"]),
        "generatorLink" => LinkHelper::getXmlGeneratorLink($newConfig["generatorID"])
    ];
    
    echo View::GenerateResponseBody(View::GetCreatedResult($linkData), "Новая таблица создана");
    $configs[$newGeneratorID] = $newConfig;
    file_put_contents(Constants::getTablesFile(), json_encode($configs));
    
    # Отправляем мыло с инфой
    sendEmailWithTableData($linkData);

?>