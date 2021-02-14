<?php

namespace App\Repositories;

use Exception;
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
     * @throws Exception in case of failed DB connection.
     */
    protected function connect() : mysqli
    {
        $mysqli = mysqli_init();
        $mysqli->ssl_set(
            null,
            null,
            __DIR__."/../../ssl/".$this->config->getDbCertificateName(),
            null,
            null);
        $mysqli->real_connect(
            $this->config->getDBHost(),
            $this->config->getDbUser(),
            $this->config->getDbPassword(),
            $this->config->getDbName(),
            3306,
            MYSQLI_CLIENT_SSL);

        if ($mysqli->connect_errno) {
            throw new Exception(
                "Не удалось подключиться к MySQL: (" .
                $mysqli->connect_errno . ") " . $mysqli->connect_error);
        }

        $mysqli->set_charset("utf8");

        return $mysqli;
    }
}
