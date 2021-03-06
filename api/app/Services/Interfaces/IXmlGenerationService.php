<?php


namespace App\Services\Interfaces;


interface IXmlGenerationService
{
    /**
     * Generates XML for specified spreadsheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @param string $targetSheet sheet name to generate XML from.
     * @throws \Exception
     * @return string generated XML.
     */
    public function generateAvitoXML(string $spreadsheetId, string $targetSheet) : string;
}
