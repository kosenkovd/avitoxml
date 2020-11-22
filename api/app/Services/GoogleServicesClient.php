<?php


namespace App\Services;

use App\Configuration\Config;
use App\Services\Interfaces\IGoogleServicesClient;
use DateTime;
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
    private Config $config;
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
        $result = $driveService->files->copy($this->config->getCopySpreadsheetId(), $driveFile);
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
     * GoogleServicesClient constructor.
     */
    public function __construct()
    {
        $this->config = new Config();
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
     * Creates new folder on GoogleDisk.
     *
     * @param string|null $name name of new folder.
     * @return string new folder id.
     */
    public function createFolder(string $name = null): string
    {
        if(is_null($name))
        {
            $name = strval(time());
        }
        $driveFolder = new Google_Service_Drive_DriveFile();
        $driveFolder->setName($name);
        $driveFolder->setMimeType('application/vnd.google-apps.folder');
        $driveService = new Google_Service_Drive($this->client);
        $result = $driveService->files->create($driveFolder);
        $folderId = $result->id;
        $this->setPermissions($folderId);
        return $folderId;
    }

    /**
     * Retrieves subfolder id by parent folder id and subfolder name.
     *
     * @param string $folderID parent folder id.
     * @param string $subFolderName name of subfolder.
     * @return string|null subfolder id, if subfolder with specified name exists.
     */
    public function getChildFolderByName(string $folderID, string $subFolderName): ?string
    {
        $this->client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($this->client);
        $result = $driveService->files->listFiles(['q' =>
            "('" . $folderID . "' in parents) and (mimeType = 'application/vnd.google-apps.folder')" .
            " and (name='" . trim($subFolderName) . "')"]);

        if(count($result->files) == 0)
        {
            return null;
        }

        return $result->files[0]['id'];
    }

    /**
     * Gets images in specified folder.
     * @param string $folderID folder id.
     * @return Google_Service_Drive_DriveFile[] images.
     */
    public function listFolderImages(string $folderID): array
    {
        global $client;

        $client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($client);
        $result = $driveService->files->listFiles([
            'q' => "('" . $folderID . "' in parents)" .
                "and ((mimeType = 'image/jpeg') or (mimeType = 'image/jpg') or (mimeType = 'image/png'))",
            'orderBy' => 'folder','name']);

        return $result->files;
    }

    /**
     * Move file to specified folder.
     *
     * @param Google_Service_Drive_DriveFile $file file.
     * @param string $folderId folder id.
     */
    public function moveFile(Google_Service_Drive_DriveFile $file, string $folderId): void
    {
        $file->setParents([$folderId]);

        $this->client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($this->client);
        $driveService->files->update($file);
    }

    /**
     * Get last modified time for file.
     *
     * @param string $fileId file id.
     * @return DateTime last modified time if file found.
     */
    public function getFileModifiedTime(string $fileId) : DateTime
    {
        $this->client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($this->client);
        $file = $driveService->files->get($fileId, [
            'fields' => 'modifiedTime, createdTime'
        ]);

        if(is_null($file->getModifiedTime()))
        {
            return DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, $file->getCreatedTime());
        }

        return DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, $file->getModifiedTime());
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
    
    /**
     * Update cells range for GoogleSheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @param string $range range to update.
     * @param array $values values to update.
     * @param array $params params of request to update.
     * @return void
     */
    public function updateSpreadsheetCellsRange(
        string $spreadsheetId,
        string $range,
        array $values,
        array $params
    ) : void
    {
        $body = new Google_Service_Sheets_ValueRange(
            [
                'values' => $values
            ]
        );
        $service = new Google_Service_Sheets($this->client);
        
        $service->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            $params
        );
    }
}
