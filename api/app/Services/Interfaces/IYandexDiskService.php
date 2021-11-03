<?php
    
    
    namespace App\Services\Interfaces;
    
    use \Leonied7\Yandex\Disk\Item\File;
    
    interface IYandexDiskService
    {
        /**
         * Init Yandex Disk
         *
         * @param string $token      yandex disk token
         */
        public function init(string $token): void;
        
        /**
         * Creates new folder on YandexDisk.
         *
         * @param string|null $folderName name of new folder.
         * @param string|null $parentFolderName parent folder name.
         * @param bool $setPermissions whether to set default permissions to folder.
         * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
         * @return string|null new folder id.
         */
        public function createFolder(
            string $folderName = null,
            string $parentFolderName = null,
            bool $setPermissions = true,
            bool $toRetry = true): ?string;
        
        /**
         * Gets images in specified folder.
         * @param string $folderPath folder id.
         * @param bool $toRetry whether to retry in case of Exception on first endpoint call.
         * @return string[] images.
         */
        public function listFolderImages(string $folderPath, bool $toRetry = true): array;
        
        /**
         * Move file to specified folder.
         *
         * PS. for now it only copies file, as it is no way to delete source one.
         *
         * @param string $currentPath source file absolute path.
         * @param string $folderID destination folder id.
         * @param string|null $newName new file name, if needs to be updated.
         */
        public function moveFile(
            string $currentPath,
            string $folderID,
            string $newName = null
        ): void;
        
        /**
         * Does folder exist.
         *
         * @param string $folderPath folder path.
         * @return bool does folder exist.
         */
        public function exists(string $folderPath): bool;
        
        /**
         * Get file download url if it exists.
         *
         * @param string $filePath absolute path to file.
         * @return string|null file download url.
         */
        public function getFileUrl(string $filePath) : ?string;
    }
