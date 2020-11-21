<?php

class Constants
{
    // Абсолютный путь к файлу со списком прикрепленных таблиц 
    private static $tablesFile = __DIR__."/../tables.json";
    
    // Хэш для авторизации базовой страницы
    private static $indexHash = "679d91ab5a8e4870436b29f3059001a6";
    
    // Хэш для авторизации списка таблиц
    private static $tableListHash = "423f86c8a785c9195eeabf6b24d30786";
    
    // Хэш для авторизации создания таблицы
    private static $createTableHash = "1262bac3a0ae843d54e6d754024f89c6";
    
    // Хэш для авторизации генерации XML
    private static $generateXmlHash = "50d85d842daf96c6ceee29ef9c2eb18a";
    
    // Хэш для авторизации генерации XML
    private static $serviceEmail = "robot@avitoxml.beget.tech";
    
    // Хэш для авторизации генерации XML
    private static $adminEmail = "maksimagishev@mail.ru";
    
    public static function getTablesFile()
    {
        return self::$tablesFile;
    }
    
    public static function getIndexHash()
    {
        return self::$indexHash;
    }
    
    public static function getTableListHash()
    {
        return self::$tableListHash;
    }
    
    public static function getCreateTableHash()
    {
        return self::$createTableHash;
    }
    
    public static function getGenerateXmlHash()
    {
        return self::$generateXmlHash;
    }
    
    public static function getServiceEmail()
    {
        return self::$serviceEmail;
    }
    
    public static function getAdminEmail()
    {
        return self::$adminEmail;
    }
}

?>