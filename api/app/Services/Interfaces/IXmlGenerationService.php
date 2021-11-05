<?php
    
    namespace App\Services\Interfaces;
    
    use App\Models\GeneratorLaravel;

    interface IXmlGenerationService {
        /**
         * Generates Avito XML for specified spreadsheet.
         *
         * @param string $spreadsheetId spreadsheet id.
         * @param string $targetSheet sheet name to generate XML from.
         * @param int $adsLimit
         *
         * @return string generated XML.
         * @throws \Exception
         */
        public function generateAvitoXML(string $spreadsheetId, string $targetSheet, int $adsLimit): string;
        
        /**
         * Generates Yandex XML for specified spreadsheet.
         *
         * @param string $spreadsheetId spreadsheet id.
         * @param string $targetSheet sheet name to generate XML from.
         * @param int $adsLimit
         *
         * @return string generated XML.
         * @throws \Exception
         */
        public function generateYandexXML(string $spreadsheetId, string $targetSheet, int $adsLimit): string;
        
        /**
         * Generates Ula XML for specified spreadsheet.
         *
         * @param string $spreadsheetId spreadsheet id.
         * @param string $targetSheet sheet name to generate XML from.
         * @param int $adsLimit
         *
         * @return string generated XML.
         * @throws \Exception
         */
        public function generateUlaXML(string $spreadsheetId, string $targetSheet, int $adsLimit): string;
    
        /**
         * Generates OZON XML for specified spreadsheet.
         *
         * @param string $spreadsheetId
         * @param string $targetSheet
         * @param int    $adsLimit
         *
         * @return string
         */
        public function generateOzonXML(string $spreadsheetId, string $targetSheet, int $adsLimit): string;
    
        /**
         * Generates Multimarket XML for specified spreadsheet.
         *
         * @param string $spreadsheetId
         * @param string $targetSheet
         * @param int    $adsLimit
         *
         * @return string
         */
        public function generateMultimarketXML(string $spreadsheetId, string $targetSheet, int $adsLimit): string;
    
        /**
         * Get empty XML for specified spreadsheet.
         *
         * @param string $targetPlatform
         *
         * @return string
         */
        public function getEmptyGeneratedXML(string $targetPlatform): string;
    }
