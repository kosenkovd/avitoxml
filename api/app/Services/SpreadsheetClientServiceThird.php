<?php
    
    
    namespace App\Services;
    
    
    use App\Configuration\Config;
    use App\Services\Interfaces\ISpreadsheetClientService;
    use DateTime;
    use Exception;
    use Google_Client;
    use Google_Service_Drive;
    use Google_Service_Drive_DriveFile;
    use Google_Service_Drive_Permission;
    use Google_Service_Sheets;
    use Google_Service_Sheets_ValueRange;
    use Illuminate\Support\Facades\Log;

    class SpreadsheetClientServiceThird implements ISpreadsheetClientService {
        private Config $config;
        private Google_Client $client;
        private Google_Service_Drive_Permission $drivePermissions;
        private Google_Service_Sheets $sheetsService;
        
        private int $secondToSleep = 60;
        private int $attemptsAfterGettingQuota = 2;
        
        /**
         * Creates new GoogleSheet from template.
         *
         * @return string new GoogleSheet id.
         */
        public function copyTable(): string
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
            $drivePermissions->setEmailAddress('Ipagishev@gmail.com');
            $driveService->permissions->create($id, $drivePermissions);
            
            $drivePermissions->setRole('owner');
            $drivePermissions->setType('user');
            $drivePermissions->setEmailAddress('xml.avito@gmail.com');
            $driveService->permissions->create(
                $id,
                $drivePermissions,
                [
                    "transferOwnership" => true
                ]);
        }
    
        /**
         * Функция обертка для обработки ошибки google квоты
         *
         * @param string $tableId
         * @param callable $action
         * @param int $failedAttempts
         * @return mixed
         * @throws Exception
         */
        private function handleQuota(
            string $tableId,
            callable $action,
            int $failedAttempts = 0
        )
        {
            try {
                return ($action)();
            } catch (Exception $exception) {
                $this->logTableError($tableId, $exception);
                
                $status = (int)$exception->getCode();
                if (!is_null($status) && $this->isQuota($status)) {
                    Log::channel('project3')->alert('sleep '.$this->secondToSleep);
                    sleep($this->secondToSleep);
    
                    $failedAttempts++;
                    if ($failedAttempts >= $this->attemptsAfterGettingQuota) {
                        throw $exception;
                    }
                    
                    return $this->handleQuota(
                        $tableId,
                        $action,
                        $failedAttempts
                    );
                } else {
                    throw $exception;
                }
            }
        }
    
        private function isQuota(int $status): bool
        {
            return $status === 429;
        }
    
        private function logTableError(string $tableId, Exception $exception): void
        {
            $message = "Error on '" . $tableId . "' ". $exception->getCode() . PHP_EOL . $exception->getMessage();
            Log::channel('project3')->error($message);
        }
        
        /**
         * GoogleServicesClient constructor.
         * @throws \Google\Exception
         */
        public function __construct()
        {
            $this->config = new Config();
            $this->client = new Google_Client();
            $this->client->setApplicationName('Google Sheets Depeche 2');
            $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
            $this->client->setAccessType('offline');
            $this->client->setAuthConfig(__dir__. '/../Configuration/GoogleAccountThirdConfig.json');
            
            $this->sheetsService = new Google_Service_Sheets($this->client);
            
            $this->drivePermissions = new Google_Service_Drive_Permission();
            $this->drivePermissions->setRole('writer');
            $this->drivePermissions->setType('anyone');
        }
        
        /**
         * @inheritDoc
         * @throws Exception
         */
        public function getFileModifiedTime(string $fileId) : ?DateTime
        {
            $this->client->addScope(Google_Service_Drive::DRIVE);
            $driveService = new Google_Service_Drive($this->client);
            
            try
            {
                $file = $this->handleQuota(
                    $fileId,
                    function () use ($driveService, $fileId) {
                        return $driveService->files->get($fileId, [
                            'fields' => 'modifiedTime, createdTime'
                        ]);
                    }
                );
            }
            catch (Exception $exception)
            {
                throw $exception;
            }
            
            if(is_null($file->getModifiedTime()))
            {
                return DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, $file->getCreatedTime());
            }
            
            return DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, $file->getModifiedTime());
        }
        
        /**
         * @inheritDoc
         */
        public function getSpreadsheetCellsRange(string $spreadsheetId, string $range, bool $toRetry = true) : array
        {
            $service = new Google_Service_Sheets($this->client);
            try
            {
                $values = $this->handleQuota(
                    $spreadsheetId,
                    function () use ($service, $spreadsheetId, $range) {
                        return $service->spreadsheets_values->get($spreadsheetId, $range)->getValues();
                    }
                );
            }
            catch (Exception $exception)
            {
                Log::channel('project3')->error("Error on '".$spreadsheetId."' while reading".PHP_EOL.
                    $exception->getMessage());
                
                throw $exception;
            }
            
            if(is_null($values))
            {
                return [];
            }
            return $values;
        }
        
        /**
         * @inheritDoc
         * @throws Exception
         */
        public function updateSpreadsheetCellsRange(
            string $spreadsheetId,
            string $range,
            array $values,
            array $params,
            bool $toRetry = true
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
                $this->handleQuota(
                    $spreadsheetId,
                    function () use ($service, $spreadsheetId, $range, $body, $params) {
                        $service->spreadsheets_values->update(
                            $spreadsheetId,
                            $range,
                            $body,
                            $params
                        );
                    }
                );
            } catch (Exception $exception) {
                throw $exception;
            }
        }
        
        /**
         * @inheritDoc
         * @throws Exception
         */
        public function updateCellContent(
            string $tableID, string $targetSheet, string $cell, string $content, bool $toRetry = true): void
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
                $params,
                $toRetry
            );
        }
    
        /**
         * @inheritDoc
         * @throws Exception
         */
        public function getSheets(string $tableId): array
        {
            $sheets = [];
            $service = new Google_Service_Sheets($this->client);
            
            try {
                $response = $this->handleQuota(
                    $tableId,
                    function () use ($service, $tableId) {
                        return $service->spreadsheets->get($tableId);
                    }
                );
            } catch (Exception $exception) {
                throw $exception;
            }
            foreach($response->getSheets() as $s) {
                $sheets[] = $s['properties']['title'];
            }
            
            return $sheets;
        }
    
        public function markAsDeleted(string $fileId): void
        {
            // stub
        }
    
        /**
         * @inheritDoc
         */
        public function copyTableMarketplace(): string
        {
            // TODO: Implement copyTableMarketplace() method.
        }
    }
