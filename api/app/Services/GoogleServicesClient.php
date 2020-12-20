<?php


namespace App\Services;

use App\Configuration\Config;
use App\Services\Interfaces\IGoogleServicesClient;
use DateTime;
use Exception;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Drive_DriveFile;
use Google_Service_Drive_Permission;
use Google_Service_Sheets_ValueRange;
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

        $drivePermissions->setRole('owner');
        $drivePermissions->setType('user');
        $drivePermissions->setEmailAddress('Ipagishev@gmail.com');
        $driveService->permissions->create(
            $id,
            $drivePermissions,
            [
                "transferOwnership" => true
            ]);
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
     * @inheritDoc
     */
    public function createFolder(
        string $name = null,
        string $parentId = null,
        bool $setPermissions = true): string
    {
        if(is_null($name))
        {
            $name = strval(time());
        }
        $driveFolder = new Google_Service_Drive_DriveFile();
        $driveFolder->setName($name);
        if(!is_null($parentId))
        {
            $driveFolder->setParents([$parentId]);
        }
        $this->client->addScope(Google_Service_Drive::DRIVE);
        $driveFolder->setMimeType('application/vnd.google-apps.folder');
        $driveService = new Google_Service_Drive($this->client);

        try
        {
            $result = $driveService->files->create($driveFolder);
        }
        catch (Exception $exception)
        {
            sleep(100);
            $result = $driveService->files->create($driveFolder);
        }

        $folderId = $result->id;
        if($setPermissions)
        {
            $this->setPermissions($folderId);
        }
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

        try
        {
            $result = $driveService->files->listFiles(['q' =>
                "('" . $folderID . "' in parents) and (mimeType = 'application/vnd.google-apps.folder')" .
                " and (name='" . trim($subFolderName) . "')"]);
        }
        catch (Exception $exception)
        {
            sleep(100);
            $result = $driveService->files->listFiles(['q' =>
                "('" . $folderID . "' in parents) and (mimeType = 'application/vnd.google-apps.folder')" .
                " and (name='" . trim($subFolderName) . "')"]);
        }

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
        $this->client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($this->client);

        try
        {
            $result = $driveService->files->listFiles([
                'q' => "('" . $folderID . "' in parents)" .
                    "and ((mimeType = 'image/jpeg') or (mimeType = 'image/jpg') or (mimeType = 'image/png'))",
                'orderBy' => 'folder,name',
                "pageSize" => 1000]);
        }
        catch (Exception $exception)
        {
            sleep(100);
            $result = $driveService->files->listFiles([
                'q' => "('" . $folderID . "' in parents)" .
                    "and ((mimeType = 'image/jpeg') or (mimeType = 'image/jpg') or (mimeType = 'image/png'))",
                'orderBy' => 'folder,name',
                "pageSize" => 1000]);
        }

        return $result->files;
    }

    /**
     * Move file to specified folder.
     *
     * PS. for now it only copies file, as it is no way to delete source one.
     *
     * @param Google_Service_Drive_DriveFile $file source file.
     * @param string $folderId destination folder id.
     * @param string|null $newName new file name, if needs to be updated.
     */
    public function moveFile(Google_Service_Drive_DriveFile $file, string $folderId, string $newName = null): void
    {
        $fileId = $file->getId();
        $newFile = new Google_Service_Drive_DriveFile();
        $newFile->setParents([$folderId]);
        if(!is_null($newName))
        {
            $newFile->setName($newName);
        }

        $this->client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($this->client);

        try
        {
            $driveService->files->copy($fileId, $newFile);
        }
        catch (Exception $exception)
        {
            sleep(100);
            $driveService->files->copy($fileId, $newFile);
        }
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

        try
        {
            $file = $driveService->files->get($fileId, [
                'fields' => 'modifiedTime, createdTime'
            ]);
        }
        catch (Exception $exception)
        {
            sleep(100);
            $file = $driveService->files->get($fileId, [
                'fields' => 'modifiedTime, createdTime'
            ]);
        }

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
        try
        {
            $values = $service->spreadsheets_values->get($spreadsheetId, $range)->getValues();
        }
        catch (Exception $exception)
        {
            sleep(100);
            $values = $service->spreadsheets_values->get($spreadsheetId, $range)->getValues();
        }

        if(is_null($values))
        {
            return [];
        }
        return $values;
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

        try
        {
            $service->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                $body,
                $params
            );
        }
        catch (Exception $exception)
        {
            sleep(100);
            $service->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                $body,
                $params
            );
        }
    }

    /**
     * Updates GoogleSheet cell content.
     *
     * @param string $tableID table id.
     * @param string $targetSheet sheet name.
     * @param string $cell cell name.
     * @param string $content content to put in cell.
     * @return void
     */
    public function updateCellContent(
        string $tableID, string $targetSheet, string $cell, string $content): void
    {
        $range = $targetSheet.'!' . $cell . ':' . $cell;

        $values = [
            [$content]
        ];
        $params = [
            'valueInputOption' => 'RAW'
        ];
        $this->updateSpreadsheetCellsRange(
            $tableID,
            $range,
            $values,
            $params
        );
    }
}
