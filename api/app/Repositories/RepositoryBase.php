<?

namespace App\Repositories;

use mysqli;
use App\Configuration\Config;

/**
 * Base class for all repositories.
 */
class RepositoryBase
{
    /**
     * @var Config Service configuration.
     */
    protected Config $config;

    function __construct()
    {
        $this->config = new Config();
    }

    /**
     * Connect to database.
     * @return mysqli established database connection.
     */
    protected function connect() : mysqli
    {
        $connection = new mysqli(
            $this->config::getDBHost(),
            $this->config::getDbUser(),
            $this->config::getDbPassword(),
            $this->config::getDbName());

        if ($connection->connect_errno) {
            throw new Exception(
                "Не удалось подключиться к MySQL: (" .
                $connection->connect_errno . ") " . $connection->connect_error);
        }

        return $connection;
    }
}
