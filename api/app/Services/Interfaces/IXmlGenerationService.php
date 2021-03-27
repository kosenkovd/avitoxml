<?php


namespace App\Services\Interfaces;


interface IXmlGenerationService
{
    /**
     * Generates Avito XML for specified spreadsheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @param string $targetSheet sheet name to generate XML from.
     * @throws \Exception
     * @return string generated XML.
     */
    public function generateAvitoXML(string $spreadsheetId, string $targetSheet) : string;
    
    /**
     * Generates Yandex XML for specified spreadsheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @param string $targetSheet sheet name to generate XML from.
     * @throws \Exception
     * @return string generated XML.
     */
    public function generateYandexXML(string $spreadsheetId, string $targetSheet) : string;
    
    /**
     * Generates Ula XML for specified spreadsheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @param string $targetSheet sheet name to generate XML from.
     * @throws \Exception
     * @return string generated XML.
     */
    public function generateUlaXML(string $spreadsheetId, string $targetSheet) : string;
}
