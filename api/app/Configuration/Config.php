<?

namespace App\Configuration;

class Config {
    private static $dbHost = "";
    private static $dbName = "";
    private static $dbUser = "";
    private static $dbPassword = "";

    function __get($name) {
        return self::$$name;
    }
}
