<?php
    
    namespace App\Services;
    
    use App\Helpers\LinkHelper;
    use App\Services\Interfaces\IMailService;
    use App\Models\Table;
    
    class MailService implements IMailService {
        // Хэш для авторизации генерации XML
        private string $serviceEmail = "robot@avitoxml.beget.tech";
        // Хэш для авторизации генерации XML
//        private string $adminEmail = "maksimagishev@mail.ru";
        private string $adminEmail = "lightzerling@gmail.com";

        public function sendEmailWithTableData(Table $dataForEmail): void
        {
            $files = '';
            foreach ($dataForEmail->getGenerators() as $generator) {
                $files .= "
    Файл: " . LinkHelper::getXmlGeneratorLink($dataForEmail->getTableGuid(), $generator->getGeneratorGuid());
            }
            
            $headers = 'From: ' . $this->serviceEmail . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
            $subject = "Новая связанная таблица";
            $message = "Создана новая таблица с автогенерацией XML файла.
    
Ссылки на новые ресурсы:
    Таблица: " . LinkHelper::getGoogleSpreadsheetLink($dataForEmail->getGoogleSheetId()) . "
    Папка: " . LinkHelper::getGoogleDriveFolderLink($dataForEmail->getGoogleDriveId()) .
                $files . "
    
С уважением,
Генератор XML для Авито";
            
            mail($this->adminEmail, $subject, $message, $headers);
        }
    }
