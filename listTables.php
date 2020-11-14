<?php
# Загружаем все необходимые длокументы
    require_once(__DIR__."/autoload.php");
    require_once(__DIR__ . "/functions.php");

# Если не указан хэш, значит это левые люди и им тут делать нечего
    checkHash(Constants::getTableListHash());

    $tables = json_decode(file_get_contents(Constants::getTablesFile()), true);
    echo View::GenerateResponseBody(View::GenerateTable($tables), "Список созданных таблиц");
?>