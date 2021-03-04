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

    class SpreadsheetClientService_quota implements ISpreadsheetClientService {
        private Config $config;
        private Google_Client $client;
        private Google_Service_Drive_Permission $drivePermissions;
        private Google_Service_Sheets $sheetsService;

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
         * @throws \Google\Exception
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
         * @throws Exception
         */
        public function getFileModifiedTime(string $fileId, string $quotaUser) : ?DateTime
        {
            $this->client->addScope(Google_Service_Drive::DRIVE);
            $driveService = new Google_Service_Drive($this->client);

            try
            {
                $file = $driveService->files->get($fileId, [
                    'fields' => 'modifiedTime, createdTime',
                    'quotaUser' => $quotaUser
                ]);
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
         * @throws Exception
         */
        public function getSpreadsheetCellsRange(string $spreadsheetId, string $range, string $quotaUser, bool $toRetry = true) : array
        {
            $optParams = [ 'quotaUser' => $quotaUser ];

            $service = new Google_Service_Sheets($this->client);
            try
            {
                $values = $service->spreadsheets_values->get($spreadsheetId, $range, $optParams)->getValues();
            }
            catch (Exception $exception)
            {
                Log::error($spreadsheetId.PHP_EOL.$exception->getMessage());
                if(!$toRetry)
                {
                    throw $exception;
                }

                sleep(60);
                $values = $service->spreadsheets_values->get($spreadsheetId, $range, $optParams)->getValues();
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
            string $quotaUser,
            bool $toRetry = true) : void
        {
            $params['quotaUser'] = $quotaUser;

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
                if(!$toRetry)
                {
                    throw $exception;
                }

                sleep(60);
                $service->spreadsheets_values->update(
                    $spreadsheetId,
                    $range,
                    $body,
                    $params
                );
            }
        }

        /**
         * @inheritDoc
         * @throws Exception
         */
        public function updateCellContent(
            string $tableID,
            string $targetSheet,
            string $cell,
            string $content,
            string $quotaUser,
            bool $toRetry = true): void
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
                $quotaUser,
                $toRetry
            );
        }

        /**
         * @inheritDoc
         */
        public function getSheets(string $tableId, string $quotaUser): array
        {
            $optParams = [ 'quotaUser' => $quotaUser ];

            $sheets = [];
            $service = new Google_Service_Sheets($this->client);

            $response = $service->spreadsheets->get($tableId, $optParams);
            foreach($response->getSheets() as $s) {
                $sheets[] = $s['properties']['title'];
            }

            return $sheets;
        }
    }
