<?php
    function generateXmlForTable(array $table, Google_Service_Sheets $service): void
    {
        $tableID = $table["tableID"];
        $folderID = $table["folderID"];
        
        $headerRange = 'Sheet1!A1:FZ1';
        $headerResponse = $service->spreadsheets_values->get($tableID, $headerRange);
        $propertyColumns = new TableHeader($headerResponse->getValues()[0]);
        
        $range = 'Sheet1!A2:FZ5001';
        $response = $service->spreadsheets_values->get($tableID, $range);
        $values = $response->getValues();
        if (empty($values)) {
            echo "No data found.\n";
        } else {
            $response = '<?xml version=\'1.0\' encoding=\'UTF-8\'?>'
                .PHP_EOL."\t<Ads formatVersion=\"3\" target=\"Avito.ru\">".PHP_EOL;
            foreach ($values as $numRow => $row) {
                if($row[$propertyColumns->ID] == '')
                {
                    continue;
                }
                $imagesRaw = $row[$propertyColumns->imagesRaw];
                $category = $row[$propertyColumns->category];
                
                
                if ($imagesRaw == '') {
                    $subFolderName = trim($row[$propertyColumns->subFolderName]);
                    if ($subFolderName == '') {
                        $images = [];
                    } else {
                        $images = fillImages($folderID, $subFolderName);
                    }
                    if ($images != []) {
                        addImagesToTable($images, $numRow, $tableID, getColumnLetterByNumber($propertyColumns->imagesRaw));
                        $row[$propertyColumns->imagesRaw] = join(PHP_EOL, $images);
                    }
                }
                
                switch(trim($category))
                {
                    case "Велосипеды":
                        $ad = new BicycleAd($row, $propertyColumns);
                        break;
                    case "Предложение услуг":
                        $ad = new ServiceAd($row, $propertyColumns);
                        break;
                    case "Одежда, обувь, аксессуары":
                    case "Детская одежда и обувь":
                        $ad = new ClothingAd($row, $propertyColumns);
                        break;
                    case "Собаки":
                    case "Кошки":
                        $ad = new PetAd($row, $propertyColumns);
                        break;
                    default:
                        $ad = new GeneralAd($row, $propertyColumns);
                        break;
                }
                
                $response.= $ad->toAvitoXml().PHP_EOL;
            }
            $response.= '</Ads>';
            
            echo $response;
        }
    }
    
    function fillImages(string $folderID, string $subFolderName): array
    {
        if($subFolderName == '')
        {
            return [];
        }
        
        $childFolder = getChildFolderByName($folderID, $subFolderName);
        
        if($childFolder == '')
        {
            return [];
        }
        
        return listFolderImages($childFolder);
    }
    
    function getChildFolderByName(string $folderID, string $subFolderName): string
    {
        global $client;
        
        $client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($client);
        $result = $driveService->files->listFiles(['q' =>
            "('" . $folderID . "' in parents) and (mimeType = 'application/vnd.google-apps.folder')" .
            " and (name='" . trim($subFolderName) . "')"]);
        
        if(count($result->files) == 0)
        {
            return '';    
        }
        
        return $result->files[0]['id'];
    }
    
    function listFolderImages(string $folderID): array
    {
        global $client;
        
        $client->addScope(Google_Service_Drive::DRIVE);
        $driveService = new Google_Service_Drive($client);
        $result = $driveService->files->listFiles([
            'q' => "('" . $folderID . "' in parents)" .
                "and ((mimeType = 'image/jpeg') or (mimeType = 'image/jpg') or (mimeType = 'image/png'))",
            'orderBy' => 'folder']);
        $links = [];
        $files = $result->files;
        usort($files, "fileCompareByName");
        foreach ($files as $file) {
            $links[] = LinkHelper::getPictureDownloadLink($file->id);
        }
        return $links;
    }
    
    
    function addImagesToTable(array $images, int $numRow, string $tableID, string $columnName): void
    {
        global $service;
        // Счет строк начинается с 1, а не с 0 и первая строка - заголовок
        $numRow += +2;
        $range = 'Sheet1!' . $columnName . $numRow . ':' . $columnName . $numRow;
        
        $imagesString = join(PHP_EOL, $images);
        $values = [
            [$imagesString]
        ];
        $body = new Google_Service_Sheets_ValueRange(
            [
                'values' => $values
            ]
        );
        $params = [
            'valueInputOption' => 'RAW'
        ];
        $service->spreadsheets_values->update(
            $tableID,
            $range,
            $body,
            $params
        );
    }
    
    
    function generateGuid()
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }
        
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
    
    function sendEmailWithTableData($dataForEmail)
    {
        $headers = 'From: ' . Constants::getServiceEmail() . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
        $subject = "Новая связанная таблица";
        $message = "Создана новая таблица с автогенерацией XML файла.
    
Ссылки на новые ресурсы:
    Таблица: " . $dataForEmail["tableLink"] . "
    Папка: " . $dataForEmail["folderLink"] . "
    Генератор: " . $dataForEmail["generatorLink"] . "
    
С уважением,
Генератор XML для Авито";
        
        mail(Constants::getAdminEmail(), $subject, $message, $headers);
    }
    
    function checkHash(string $hash): void
    {
        if ($_GET["hash"] != $hash) {
            http_response_code(403);
            exit;
        }
    }

    function fileCompareByName($a, $b)
    {
        if ($a["name"] == $b["name"]) {
            return 0;
        }
        return ($a["name"] < $b["name"]) ? -1 : 1;
    }
    
    function getColumnLetterByNumber(int $numCol): string
    {
        // В английском алфавите 26 букв
        $remainder = $numCol % 26;
        $quotient = ($numCol - $remainder) / 26;
        if($quotient > 0) {
            return getColumnLetterByNumber($quotient - 1).chr($remainder + 65);
        } else {
            // 65 - это код символа A
            return chr($remainder + 65);
        }
    }
?>