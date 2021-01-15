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
     * @param string|null $parentId parent folder id.
     * @param bool $setPermissions whether to set default permissions to folder.
     * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
     * @return string|null new folder id.
     */
    public function createFolder(
        string $name = null,
        string $parentId = null,
        bool $setPermissions = true,
        bool $toRetry = true): ?string;

    /**
     * Retrieves subfolder id by parent folder id and subfolder name.
     *
     * @param string $folderID parent folder id.
     * @param string $subFolderName name of subfolder.
     * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
     * @return string|null subfolder id, if subfolder with specified name exists.
     */
    public function getChildFolderByName(string $folderID, string $subFolderName, bool $toRetry = true): ?string;

    /**
     * Gets images in specified folder.
     * @param string $folderID folder id.
     * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
     * @return Google_Service_Drive_DriveFile[] images.
     */
    public function listFolderImages(string $folderID, bool $toRetry = true): array;

    /**
     * Get last modified time for file.
     *
     * @param string $fileId file id.
     * @return DateTime|null last modified time if file found.
     */
    public function getFileModifiedTime(string $fileId) : ?DateTime;

    /**
     * Move file to specified folder.
     *
     * PS. for now it only copies file, as it is no way to delete source one.
     *
     * @param Google_Service_Drive_DriveFile $file source file.
     * @param string $folderId destination folder id.
     * @param string|null $newName new file name, if needs to be updated.
     * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
     */
    public function moveFile(
        Google_Service_Drive_DriveFile $file,
        string $folderId,
        string $newName = null,
        bool $toRetry = true): void;

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
     * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
     * @return array cells in chosen range.
     */
    public function getSpreadsheetCellsRange(string $spreadsheetId, string $range, bool $toRetry = true) : array;

    /**
     * Update cells range for GoogleSheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @param string $range range to update.
     * @param array $values values to update.
     * @param array $params params of request to update.
     * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
     * @return void
     */
    public function updateSpreadsheetCellsRange(
        string $spreadsheetId,
        string $range,
        array $values,
        array $params,
        bool $toRetry = true
    ) : void;

    /**
     * Updates GoogleSheet cell content.
     *
     * @param string $tableID table id.
     * @param string $targetSheet sheet name.
     * @param string $cell cell name.
     * @param string $content content to put in cell.
     * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
     * @return void
     */
    function updateCellContent(
        string $tableID, string $targetSheet, string $cell, string $content, bool $toRetry = true): void;
}
