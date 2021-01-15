<?php


namespace App\Services;

use App\Configuration\Spreadsheet\SheetNames;
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
    /**
     * @var IGoogleServicesClient Google services client.
     */
    private IGoogleServicesClient $googleClient;

    /**
     * @var SheetNames configuration with sheet names.
     */
    private SheetNames $sheetNamesConfig;

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
    private function isConstructionMaterial(array $row, TableHeader $propertyColumns) : bool
    {
        return isset($propertyColumns->goodsType) &&
            isset($row[$propertyColumns->goodsType]) &&
            $row[$propertyColumns->goodsType] == "Стройматериалы";
    }

    /**
     * Defines if ad in row is construction material.
     *
     * @param array $row
     * @param TableHeader $propertyColumns
     * @return bool is auto part.
     */
    private function isAutoPart(array $row, TableHeader $propertyColumns) : bool
    {
        return isset($propertyColumns->goodsType) &&
            isset($row[$propertyColumns->goodsType]) &&
            $row[$propertyColumns->goodsType] == "Запчасти" &&
            isset($propertyColumns->autoPart) &&
            isset($row[$propertyColumns->autoPart]);
    }

    /**
     * Defines if row should not be processed.
     *
     * @param array $row
     * @param TableHeader $propertyColumns
     * @param string $sheetName
     * @param string[] $ids ad ids for yandex.
     * @param int $numRow
     * @return bool
     */
    private function shouldSkipRow(
        array $row, TableHeader $propertyColumns, string $sheetName, array $ids, int $numRow) : bool
    {
        $isIdPresent = ($sheetName == $this->sheetNamesConfig->getYandex() &&
                            isset($ids[$numRow]) &&
                            $ids[$numRow] != '') ||
                       ($sheetName != $this->sheetNamesConfig->getYandex() &&
                            @$row[@$propertyColumns->ID] != '');
        return !$this->validateRequiredColumnsPresent($row, $propertyColumns) || !$isIdPresent;
    }


    public function __construct(IGoogleServicesClient $googleClient, SheetNames $sheetNames)
    {
        $this->googleClient = $googleClient;
        $this->sheetNamesConfig = $sheetNames;
    }

    /**
     * @inheritDoc
     */
    public function generateAvitoXML(string $spreadsheetId, string $targetSheet) : string
    {
        $headerRange = $targetSheet.'!A1:FZ1';
        $headerResponse = $this->googleClient->getSpreadsheetCellsRange($spreadsheetId, $headerRange, false);
        $propertyColumns = new TableHeader($headerResponse[0]);

        $range = $targetSheet.'!A2:FZ5001';
        $values = $this->googleClient->getSpreadsheetCellsRange($spreadsheetId, $range, false);

        $xml = '<?xml version=\'1.0\' encoding=\'UTF-8\'?>'
            .PHP_EOL."<Ads formatVersion=\"3\" target=\"Avito.ru\">".PHP_EOL;
        if (empty($values))
        {
            return $xml.'</Ads>';
        }
        else
        {
            $idValues = [];
            if($targetSheet == $this->sheetNamesConfig->getYandex())
            {
                $range = $this->sheetNamesConfig->getYandexSettings().'!C2:C5001';
                $idValues = $this->googleClient->getSpreadsheetCellsRange($spreadsheetId, $range, false);
            }

            foreach ($values as $numRow => $row) {
                if($this->shouldSkipRow($row, $propertyColumns, $targetSheet, $idValues, $numRow))
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
                    case "Запчасти и автотовары":
                        if($this->isAutoPart($row, $propertyColumns))
                        {
                            $ad = new Ads\AutoPartAd($row, $propertyColumns);
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
