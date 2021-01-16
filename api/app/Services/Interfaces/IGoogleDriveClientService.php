<?php


namespace App\Services\Interfaces;

use Google_Service_Drive_DriveFile;

interface IGoogleDriveClientService
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
}
