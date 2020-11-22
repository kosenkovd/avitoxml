<?php


namespace App\Services\Interfaces;

use DateTime;
use Google_Service_Drive_DriveFile;

interface IGoogleServicesClient
{
    /**
     * Creates new folder on GoogleDisk.
     *
     * @param string|null $name name of new folder.
     * @return string new folder id.
     */
    public function createFolder(string $name = null): string;

    /**
     * Retrieves subfolder id by parent folder id and subfolder name.
     *
     * @param string $folderID parent folder id.
     * @param string $subFolderName name of subfolder.
     * @return string|null subfolder id, if subfolder with specified name exists.
     */
    public function getChildFolderByName(string $folderID, string $subFolderName): ?string;

    /**
     * Gets images in specified folder.
     * @param string $folderID folder id.
     * @return Google_Service_Drive_DriveFile[] images.
     */
    public function listFolderImages(string $folderID): array;

    /**
     * Get last modified time for file.
     *
     * @param string $fileId file id.
     * @return DateTime last modified time if file found.
     */
    public function getFileModifiedTime(string $fileId) : DateTime;

    /**
     * Move file to specified folder.
     *
     * @param Google_Service_Drive_DriveFile $file file.
     * @param string $folderId folder id.
     */
    public function moveFile(Google_Service_Drive_DriveFile $file, string $folderId): void;

    /**
     * Create new GoogleSheet and new folder on GoogleDisk.
     *
     * @return string[] newTableId, newFolderId
     */
    public function createTableInfrastructure(): array;

    /**
     * Get cells range for GoogleSheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @param string $range range to get.
     * @return array cells in chosen range.
     */
    public function getSpreadsheetCellsRange(string $spreadsheetId, string $range) : array;
    
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
    ) : void;
}
