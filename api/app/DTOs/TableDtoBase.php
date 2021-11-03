<?php


namespace App\DTOs;


use App\Http\Resources\GeneratorCollection;

class TableDtoBase
{
    public int $tableId;

    public int $userId;

    public string $googleSheetUrl;

    public ?string $googleDriveUrl;

//    /** @var GeneratorCollection */
    public $generators; // TODO change to GeneratorCollection

    public ?string $tableNotes;
    
    public string $tableGuid;
    
    public string $googleSheetId;
    
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
     * @param array $generators // |GeneratorCollection
     * @return TableDtoBase
     */
    public function setGenerators($generators): TableDtoBase // TODO change to GeneratorCollection
    {
        $this->generators = $generators;
        return $this;
    }
    
    /**
     * @param string|null $tableNotes
     * @return TableDtoBase
     */
    public function setTableNotes(?string $tableNotes): TableDtoBase
    {
        $this->tableNotes = $tableNotes;
        return $this;
    }
    
}
