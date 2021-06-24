<?php

namespace App\Configuration;

class Config {
    private string $dbHost;
    private string $dbName;
    private string $dbUser;
    private string $dbPassword;
    private string $usersTableName;
    private string $tablesTableName;
    private string $tableUpdateLockTableName;
    private string $tableUlaName;
    private string $generatorsTableName;
    private string $copySpreadsheetId;
    private string $baseFolderId;
    private string $rootDirectory;
    private string $dbCertificateName;
    private bool $isLoggingEnabled;
    private int $needsToUpdateTimeStamp;
    private string $copyMarketplaceSpreadsheetId;

    public function __construct()
    {
        $configurationJson =  json_decode(file_get_contents(__dir__. '/appsettings.json'), true);
        foreach ($configurationJson as $key => $value)
        {
            if(property_exists(Config::class, $key))
            {
                $this->$key = strval($value);
            }
        }
    }
    
    /**
     * @return int
     */
    public function getNeedsToUpdateTimeStamp(): int
    {
        return $this->needsToUpdateTimeStamp;
    }
    
    /**
     * @return string
     */
    public function getTableUlaName(): string
    {
        return $this->tableUlaName;
    }

    /**
     * @return string
     */
    public function getDbHost(): string
    {
        return $this->dbHost;
    }

    /**
     * @return string
     */
    public function getDbName(): string
    {
        return $this->dbName;
    }

    /**
     * @return string
     */
    public function getDbUser(): string
    {
        return $this->dbUser;
    }

    /**
     * @return string
     */
    public function getDbPassword(): string
    {
        return $this->dbPassword;
    }

    /**
     * @return string
     */
    public function getUsersTableName(): string
    {
        return $this->usersTableName;
    }

    /**
     * @return string
     */
    public function getTablesTableName(): string
    {
        return $this->tablesTableName;
    }

    /**
     * @return string
     */
    public function getGeneratorsTableName(): string
    {
        return $this->generatorsTableName;
    }

    /**
     * @return string
     */
    public function getCopySpreadsheetId(): string
    {
        return $this->copySpreadsheetId;
    }

    /**
     * @return string
     */
    public function getBaseFolderId(): string
    {
        return $this->baseFolderId;
    }

    /**
     * @return string
     */
    public function getTableUpdateLockTableName(): string
    {
        return $this->tableUpdateLockTableName;
    }

    /**
     * @return string
     */
    public function getRootDirectory(): string
    {
        return $this->rootDirectory;
    }

    /**
     * @return bool
     */
    public function isLoggingEnabled(): bool
    {
        return $this->isLoggingEnabled;
    }

    /**
     * @return string
     */
    public function getDbCertificateName(): string
    {
        return $this->dbCertificateName;
    }
    
    /**
     * @return string
     */
    public function getCopyMarketplaceSpreadsheetId(): string
    {
        return $this->copyMarketplaceSpreadsheetId;
    }
}
