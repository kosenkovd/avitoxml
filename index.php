<?php
    # Загружаем все необходимые файлы
    require_once(__DIR__."/autoload.php");
    require_once(__DIR__ . "/functions.php");
    # Если не указан хэш, значит это левые люди и им тут делать нечего
    checkHash(Constants::getIndexHash());
    echo View::GenerateResponseBody(View::GetIndexPageContent(), "Генератор XML для Авито");
?>