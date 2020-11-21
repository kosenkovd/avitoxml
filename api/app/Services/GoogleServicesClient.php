<?php


namespace App\Services;

use App\Services\Interfaces\IGoogleServicesClient;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Drive_DriveFile;
use Google_Service_Drive_Permission;
use Google_Service_Drive;

/**
 * Handles communication with Google services.
 * @package App\Services
 */
class GoogleServicesClient implements IGoogleServicesClient
{
    /**
     * @var string GoogleSheet template id.
     */
    private static string $copySpreadsheetId = '1iZiPNNjReXtxF65ZMmodkPmuLvR-DVAv7Uow_4QsZOM';
    private static string $baseFolderId = '1DmfncP64A8P7fV8K3Suj81uVKwlFebTU';
    private Google_Client $client;
    private Google_Service_Drive_Permission $drivePermissions;
    private Google_Service_Sheets $sheetsService;

    /**
     * Creates new GoogleSheet from template.
     *
     * @return string new GoogleSheet id.
     */
    private function copyTable(): string
    {
        $this->client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($this->client);
        $driveFile = new Google_Service_Drive_DriveFile();
        $result = $driveService->files->copy(self::$copySpreadsheetId, $driveFile);
        $tableId = $result->id;
        $this->setPermissions($tableId);
        return $tableId;
    }

    /**
     * Sets default permissions to Google object.
     *
     * @param $id string Google resource id.
     */
    private function setPermissions(string $id): void
    {
        $this->client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($this->client);
        $drivePermissions = new Google_Service_Drive_Permission();
        $drivePermissions->setRole('writer');
        $drivePermissions->setType('anyone');
        $driveService->permissions->create($id, $drivePermissions);
        $drivePermissions->setRole('writer');
        $drivePermissions->setType('user');
        $drivePermissions->setEmailAddress('wdenkosw@gmail.com');
        $driveService->permissions->create($id, $drivePermissions);
        $drivePermissions->setRole('writer');
        $drivePermissions->setType('user');
        $drivePermissions->setEmailAddress('xml.avito@gmail.com');
        $driveService->permissions->create($id, $drivePermissions);
        $drivePermissions->setRole('writer');
        $drivePermissions->setType('user');
        $drivePermissions->setEmailAddress('Ipagishev@gmail.com');
        $driveService->permissions->create($id, $drivePermissions);
    }

    /**
     * Creates new folder on GoogleDisk.
     *
     * @return string new folder id.
     */
    private function createFolder(): string
    {
        $driveFolder = new Google_Service_Drive_DriveFile();
        $driveFolder->setName(time());
        $driveFolder->setMimeType('application/vnd.google-apps.folder');
        $driveService = new Google_Service_Drive($this->client);
        $result = $driveService->files->create($driveFolder);
        $folderId = $result->id;
        $this->setPermissions($folderId);
        return $folderId;
    }

    /**
     * GoogleServicesClient constructor.
     */
    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Google Sheets Depeche');
        $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAccessType('offline');
        $this->client->setAuthConfig(__dir__. '/../Configuration/GoogleAccountConfig.json');

        $this->sheetsService = new Google_Service_Sheets($this->client);

        $this->drivePermissions = new Google_Service_Drive_Permission();
        $this->drivePermissions->setRole('writer');
        $this->drivePermissions->setType('anyone');
    }

    /**
     * Create new GoogleSheet and new folder on GoogleDisk.
     *
     * @return string[] newTableId, newFolderId
     */
    public function createTableInfrastructure(): array
    {
        $tableId = $this->copyTable();
        $folderId = $this->createFolder();
        return [$tableId, $folderId];
    }

    /**
     * Get cells range for GoogleSheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @param string $range range to get.
     * @return array cells in chosen range.
     */
    public function getSpreadsheetCellsRange(string $spreadsheetId, string $range) : array
    {
        $service = new Google_Service_Sheets($this->client);
        return $service->spreadsheets_values->get($spreadsheetId, $range)->getValues();
    }
}
