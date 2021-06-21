<?php


namespace App\DTOs;


class TableDtoBase
{
    public int $tableId;

    public int $userId;

    public string $googleSheetUrl;

    public ?string $googleDriveUrl;

    public array $generators;

    public ?string $notes;
    
    public string $tableGuid;
    
    public string $googleSheetId;
    
    /**
     * @return string
     */
    public function getGoogleSheetId(): string
    {
        return $this->googleSheetId;
    }
    
    /**
     * @param string $googleSheetId
     * @return TableDtoBase
     */
    public function setGoogleSheetId(string $googleSheetId): TableDtoBase
    {
        $this->googleSheetId = $googleSheetId;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getTableGuid(): string
    {
        return $this->tableGuid;
    }
    
    /**
     * @param string $tableGuid
     * @return TableDtoBase
     */
    public function setTableGuid(string $tableGuid): TableDtoBase
    {
        $this->tableGuid = $tableGuid;
        return $this;
    }
    
    /**
     * @param int $tableId
     * @return TableDtoBase
     */
    public function setTableId(int $tableId): TableDtoBase
    {
        $this->tableId = $tableId;
        return $this;
    }
    
    /**
     * @param int $userId
     * @return TableDtoBase
     */
    public function setUserId(int $userId): TableDtoBase
    {
        $this->userId = $userId;
        return $this;
    }
    
    /**
     * @param string $googleSheetUrl
     * @return TableDtoBase
     */
    public function setGoogleSheetUrl(string $googleSheetUrl): TableDtoBase
    {
        $this->googleSheetUrl = $googleSheetUrl;
        return $this;
    }
    
    /**
     * @param string|null $googleDriveUrl
     * @return TableDtoBase
     */
    public function setGoogleDriveUrl(?string $googleDriveUrl): TableDtoBase
    {
        $this->googleDriveUrl = $googleDriveUrl;
        return $this;
    }
    
    /**
     * @param array $generators
     * @return TableDtoBase
     */
    public function setGenerators(array $generators): TableDtoBase
    {
        $this->generators = $generators;
        return $this;
    }
    
    /**
     * @param string|null $notes
     * @return TableDtoBase
     */
    public function setNotes(?string $notes): TableDtoBase
    {
        $this->notes = $notes;
        return $this;
    }
    
}
