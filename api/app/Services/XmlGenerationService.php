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
     * @param TableHeader $propertyColumns
     * @return bool is valid.
     */
    private function validateRequiredColumnsPresent(array $row, TableHeader $propertyColumns) : bool
    {
        return isset($row[$propertyColumns->ID]) && isset($row[$propertyColumns->category]);
    }

    /**
     * Defines if ad in row is construction material.
     *
     * @param array $row
     * @param TableHeader $propertyColumns
     * @return bool is construction material.
     */
    private function isConstructionMaterial(array $row, TableHeader  $propertyColumns) : bool
    {
        return isset($propertyColumns->goodsType) &&
            isset($row[$propertyColumns->goodsType]) &&
            $row[$propertyColumns->goodsType] == "Стройматериалы";
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
    public function generateAvitoXML(string $spreadsheetId) : string
    {
        $headerRange = 'Avito!A1:FZ1';
        $headerResponse = $this->googleClient->getSpreadsheetCellsRange($spreadsheetId, $headerRange);
        $propertyColumns = new TableHeader($headerResponse[0]);

        $range = 'Avito!A2:FZ5001';
        $values = $this->googleClient->getSpreadsheetCellsRange($spreadsheetId, $range);

        $xml = '<?xml version=\'1.0\' encoding=\'UTF-8\'?>'
            .PHP_EOL."<Ads formatVersion=\"3\" target=\"Avito.ru\">".PHP_EOL;
        if (empty($values))
        {
            return $xml.'</Ads>';
        }
        else
        {
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
                    case "Ремонт и строительство":
                        if($this->isConstructionMaterial($row, $propertyColumns))
                        {
                            $ad = new Ads\ConstructionMaterialAd($row, $propertyColumns);
                            break;
                        }
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
