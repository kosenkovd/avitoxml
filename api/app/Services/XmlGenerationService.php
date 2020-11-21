<?php


namespace App\Services;

use App\Models\Ads;
use App\Models\TableHeader;
use App\Services\Interfaces\IGoogleServicesClient;
use App\Services\Interfaces\IXmlGenerationService;

/**
 * Handles XML generation.
 *
 * @package App\Services
 */
class XmlGenerationService implements IXmlGenerationService
{
    private IGoogleServicesClient $googleClient;

    /**
     * Checks if row contains all required properties.
     *
     * @param array $row
     * @param TableHeader
     * @return bool is valid.
     */
    private function validateRequiredColumnsPresent(array $row, TableHeader $propertyColumns) : bool
    {
        return isset($row[$propertyColumns->ID]) && isset($row[$propertyColumns->category]);
    }


    public function __construct(IGoogleServicesClient $googleClient)
    {
        $this->googleClient = $googleClient;
    }

    /**
     * Generates XML for specified spreadsheet.
     *
     * @param string $spreadsheetId spreadsheet id.
     * @return string|null generated xml.
     */
    public function generateAvitoXML(string $spreadsheetId) : ?string
    {
        $headerRange = 'Sheet1!A1:FZ1';
        $headerResponse = $this->googleClient->getSpreadsheetCellsRange($spreadsheetId, $headerRange);
        $propertyColumns = new TableHeader($headerResponse[0]);

        $range = 'Sheet1!A2:FZ5001';
        $values = $this->googleClient->getSpreadsheetCellsRange($spreadsheetId, $range);

        if (empty($values))
        {
            return null;
        } else
        {
            $xml = '<?xml version=\'1.0\' encoding=\'UTF-8\'?>'
                .PHP_EOL."<Ads formatVersion=\"3\" target=\"Avito.ru\">".PHP_EOL;

            foreach ($values as $numRow => $row) {
                if(!$this->validateRequiredColumnsPresent($row, $propertyColumns) || $row[$propertyColumns->ID] == '')
                {
                    continue;
                }

                $category = $row[$propertyColumns->category];
                switch(trim($category))
                {
                    case "Велосипеды":
                        $ad = new Ads\BicycleAd($row, $propertyColumns);
                        break;
                    case "Предложение услуг":
                        $ad = new Ads\ServiceAd($row, $propertyColumns);
                        break;
                    case "Одежда, обувь, аксессуары":
                    case "Детская одежда и обувь":
                        $ad = new Ads\ClothingAd($row, $propertyColumns);
                        break;
                    case "Собаки":
                    case "Кошки":
                        $ad = new Ads\PetAd($row, $propertyColumns);
                        break;
                    default:
                        $ad = new Ads\GeneralAd($row, $propertyColumns);
                        break;
                }

                $xml.= $ad->toAvitoXml().PHP_EOL;
            }
            $xml.= '</Ads>';

            return $xml;
        }
    }
}
