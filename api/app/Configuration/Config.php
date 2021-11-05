<?php

namespace App\Configuration;

class Config {
    private string $dbHost;
    private string $dbName;
    private string $dbUser;
    private string $dbPassword;
    private string $copySpreadsheetId;
    private string $baseFolderId;
    private string $rootDirectory;
    private string $dbCertificateName;
    /**
     * @var int $needsToUpdateTimeStamp hack to check Table if FillImagesJob not finished
     */
    private int $needsToUpdateTimeStamp;
    private string $copyMarketplaceSpreadsheetId;
    private string $terminalPassword;
    private string $maxAdsLimit;
    
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
    public function getRootDirectory(): string
    {
        return $this->rootDirectory;
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
    
    /**
     * @return string
     */
    public function getTerminalPassword(): string
    {
        return $this->terminalPassword;
    }
    
    /**
     * @return int
     */
    public function getMaxAdsLimit(): int
    {
        return $this->maxAdsLimit;
    }
}
