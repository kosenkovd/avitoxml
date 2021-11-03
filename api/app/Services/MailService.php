<?php

    namespace App\Services;

    use App\Helpers\LinkHelper;
    use App\Models\GeneratorLaravel;
    use App\Models\TableLaravel;
    use App\Models\TableMarketplace;
    use App\Services\Interfaces\IMailService;

    class MailService implements IMailService {
        // Хэш для авторизации генерации XML
        private string $serviceEmail = "robot@avitoxml.beget.tech";
        // Хэш для авторизации генерации XML
        private string $adminEmail = "maksimagishev@mail.ru";

        public function sendEmailWithTableDataLaravel(TableLaravel $table): void
        {
            $files = '';
            /** @var GeneratorLaravel $generator */
            foreach ($table->generators as $generator) {
                $files .= "
    Файл для ".$generator->targetPlatform.": " . LinkHelper::getXmlGeneratorLink($table->tableGuid, $generator->generatorGuid);
            }

            $headers = 'From: ' . $this->serviceEmail . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
            $subject = "Новая связанная таблица";
            $message = "Создана новая таблица с автогенерацией XML файла.

Ссылки на новые ресурсы:
    Таблица: " . LinkHelper::getGoogleSpreadsheetLink($table->googleSheetId) .
                $files . "

С уважением,
Генератор XML для Авито";

            mail($this->adminEmail, $subject, $message, $headers);
        }
        
        public function sendEmailWithTableDataMarketplace(TableMarketplace $table): void
        {
            $files = '';
            /** @var GeneratorLaravel $generator */
            foreach ($table->generators as $generator) {
                $files .= "
    Файл для ".$generator->targetPlatform.": " . LinkHelper::getXmlGeneratorLink($table->tableGuid, $generator->generatorGuid);
            }

            $headers = 'From: ' . $this->serviceEmail . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
            $subject = "Новая связанная таблица";
            $message = "Создана новая таблица с автогенерацией XML файла.

Ссылки на новые ресурсы:
    Таблица: " . LinkHelper::getGoogleSpreadsheetLink($table->googleSheetId) .
                $files . "

С уважением,
Генератор XML для Авито";

            mail($this->adminEmail, $subject, $message, $headers);
        }
    }
