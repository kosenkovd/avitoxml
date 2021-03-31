<?php
    require_once($_SERVER["DOCUMENT_ROOT"] . '/services/config/config.php');
    
    class CreateNewTable {
        private $spreadsheetId;
        private $folderId;
        private $client;
        private $drivePermissions;

        private $tableId;

        public function __construct(
            string $spreadsheetId,
            Google_Client $client,
            Google_Service_Drive_Permission $drivePermissions
        )
        {
            $this->spreadsheetId = $spreadsheetId;
            $this->client = $client;
            $this->drivePermissions = $drivePermissions;
        }

        public function create(): array
        {
            $this->copyTable();
            $this->createFolder();
            return ["newTableID" => $this->tableId, "newFolderID" => $this->folderId];
        }

        private function copyTable(): void
        {
            $this->client->addScope(Google_Service_Drive::DRIVE);
            $driveService = new Google_Service_Drive($this->client);
            $driveFile = new Google_Service_Drive_DriveFile();
            $result = $driveService->files->copy($this->spreadsheetId, $driveFile);
            $this->tableId = $result->id;
            $this->setPermissions($this->tableId);
        }

        private function setPermissions($id): void
        {
            $this->client->addScope(Google_Service_Drive::DRIVE);
            $driveService = new Google_Service_Drive($this->client);
            $drivePermissions = new Google_Service_Drive_Permission();
            $drivePermissions->setRole('writer');
            $drivePermissions->setType('anyone');
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

        private function createFolder(): void
        {
            $driveFolder = new Google_Service_Drive_DriveFile();
            $driveFolder->setName(time());
            $driveFolder->setMimeType('application/vnd.google-apps.folder');
            $driveService = new Google_Service_Drive($this->client);
            $result = $driveService->files->create($driveFolder);
            $this->folderId = $result->id;
            $this->setPermissions($this->folderId);
        }
    }
