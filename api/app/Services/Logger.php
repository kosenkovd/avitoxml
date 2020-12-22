<?php


namespace App\Services;


use App\Configuration\Config;

class Logger
{
    private string $loggedClass;

    private Config $config;

    /**
     * Logger constructor.
     * @param string $loggedClass
     * @param Config $config
     */
    public function __construct(string $loggedClass, Config $config)
    {
        $this->loggedClass = $loggedClass;
        $this->config = $config;
    }

    public function log(string $message) : void
    {
        echo $message.PHP_EOL;
        if(!$this->config->isLoggingEnabled())
        {
            return;
        }

        $timestamp = new \DateTime();
        $timestamp->setTimestamp(time());
        $file = __DIR__."/../../../".$this->loggedClass.".log";
        file_put_contents($file,
            $timestamp->format(DATE_ISO8601)." ".$message.PHP_EOL,
            FILE_APPEND | LOCK_EX);
    }
}
