<?php


namespace App\Services\Interfaces;


interface IXmlGenerationService
{
    /**
     * Generates XML for specified spreadsheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @return string|null generated xml.
     */
    public function generateAvitoXML(string $spreadsheetId) : ?string;
}
