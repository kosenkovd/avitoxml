<?

namespace App\Repositories;

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
        $config = new Config();
    }

    /**
     * Connect to database.
     * @return mysqli established database connection.
     */
    protected function connect() : mysqli
    {
        $connection = new mysqli(
            $this->config->dbHost,
            $this->config->dbUser,
            $this->config->dbPassword,
            $this->config->dbName);

        if ($connection->connect_errno) {
            throw new Exception(
                "Не удалось подключиться к MySQL: (" .
                $connection->connect_errno . ") " .$connection->connect_error);
        }

        return $connection;
    }
}
