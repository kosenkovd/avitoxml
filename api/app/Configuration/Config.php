<?

namespace App\Configuration;

class Config {
    private static $dbHost = "localhost";
    private static $dbName = "avitoxml_main";
    private static $dbUser = "avitoxml_main";
    private static $dbPassword = "S4E*9dIs";
    
    function __get($name) {
        return self::$$name;
    }
}