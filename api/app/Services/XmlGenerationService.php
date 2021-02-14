<?php


namespace App\Services;

use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Models\Ads;
use App\Models\TableHeader;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IXmlGenerationService;
use DateTime;
use DateTimeZone;
use http\Exception;
use Illuminate\Support\Facades\Log;

/**
 * Handles XML generation.
 *
 * @package App\Services
 */
class XmlGenerationService implements IXmlGenerationService
{
    /**
     * @var ISpreadsheetClientService Google Spreadsheet services client.
     */
    private ISpreadsheetClientService $spreadsheetClientService;

    /**
     * @var SheetNames configuration with sheet names.
     */
    private SheetNames $sheetNamesConfig;

    /**
     * @var XmlGeneration xml generation configs.
     */
    private XmlGeneration $xmlGeneration;

    /**
     * Checks if row contains all required properties.
     *
     * @param array $row
     * @param TableHeader $propertyColumns
     * @return bool is valid.
     */
    private function validateRequiredColumnsPresent(array $row, TableHeader $propertyColumns) : bool
    {
        return isset($row[$propertyColumns->ID]) &&
            isset($row[$propertyColumns->category]) &&
            !is_null($row[$propertyColumns->ID]) &&
            trim($row[$propertyColumns->ID]) !== "";
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
     * @return bool
     */
    private function shouldSkipRow(
        array $row, TableHeader $propertyColumns, string $sheetName) : bool
    {
        return !$this->validateRequiredColumnsPresent($row, $propertyColumns) ||
            (strpos($sheetName, $this->sheetNamesConfig->getYandex()) !== false &&
                $this->shouldSkipYandexRow($row, $propertyColumns));
    }

    /**
     * Should row from Yandex sheet be skipped.
     *
     * @param array $row
     * @param TableHeader $propertyColumns
     * @return bool
     */
    private function shouldSkipYandexRow(array $row, TableHeader $propertyColumns) : bool
    {
        $idFieldPresent = @$row[@$propertyColumns->ID] != '';

        $dateCreated = @$row[@$propertyColumns->dateCreated];
        if(is_null($dateCreated) || $dateCreated == '')
        {
            return !$idFieldPresent;
        }
        else
        {
            if(strpos($row[$propertyColumns->dateCreated], ":"))
            {
                $date = DateTime::createFromFormat(
                    'd.m.Y H:i', $row[$propertyColumns->dateCreated], new DateTimeZone("Europe/Moscow"));
            }
            else
            {
                $date = DateTime::createFromFormat(
                    'd.m.Y', $row[$propertyColumns->dateCreated], new DateTimeZone("Europe/Moscow"));
            }

            if($date !== false)
            {
                return !$idFieldPresent && $date->getTimestamp() <= time();
            }
            else
            {
                return !$idFieldPresent;
            }
        }
    }

    /**
     * Create ads from sheet rows.
     *
     * @param array $values rows from sheet.
     * @param TableHeader $propertyColumns
     * @param string $targetSheet
     * @return string generated ads.
     */
    private function createAdsForSheet(array $values, TableHeader $propertyColumns, string $targetSheet): string
    {
        $xml = "";
        foreach ($values as $numRow => $row)
        {
            if($this->shouldSkipRow($row, $propertyColumns, $targetSheet))
            {
                continue;
            }

            $category = $row[$propertyColumns->category];
            switch(trim($category))
            {
                case "Велосипеды":
                    $ad = new Ads\BicycleAd($row, $propertyColumns);
                    break;
                case "Вакансии":
                    $ad = new Ads\JobAd($row, $propertyColumns);
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
                case "Запчасти и аксессуары":
                    $ad = new Ads\AvitoAutoPartAd($row, $propertyColumns);
                    break;
                case "Ремонт и строительство":
                    if($this->isConstructionMaterial($row, $propertyColumns))
                    {
                        $ad = new Ads\ConstructionMaterialAd($row, $propertyColumns);
                    }
                    else
                    {
                        $ad = new Ads\GeneralAd($row, $propertyColumns);
                    }
                    break;
                case "Запчасти и автотовары":
                    if($this->isAutoPart($row, $propertyColumns))
                    {
                        $ad = new Ads\AutoPartAd($row, $propertyColumns);
                    }
                    else
                    {
                        $ad = new Ads\GeneralAd($row, $propertyColumns);
                    }
                    break;
                default:
                    $ad = new Ads\GeneralAd($row, $propertyColumns);
            }

            $xml.= $ad->toAvitoXml().PHP_EOL;
        }

        return $xml;
    }


    public function __construct(
        ISpreadsheetClientService $spreadsheetClientService, SheetNames $sheetNames, XmlGeneration $xmlGeneration)
    {
        $this->spreadsheetClientService = $spreadsheetClientService;
        $this->sheetNamesConfig = $sheetNames;
        $this->xmlGeneration = $xmlGeneration;
    }

    /**
     * @inheritDoc
     */
    public function generateAvitoXML(string $spreadsheetId, string $targetSheet) : string
    {
        $xml = '<?xml version=\'1.0\' encoding=\'UTF-8\'?>'
            .PHP_EOL."<Ads formatVersion=\"3\" target=\"Avito.ru\">".PHP_EOL;

        switch($targetSheet)
        {
            case "Avito":
                $targetSheets = $this->xmlGeneration->getAvitoTabs();
                break;
            case "Юла":
                $targetSheets = $this->xmlGeneration->getYoulaTabs();
                break;
            case "Яндекс":
                $targetSheets = $this->xmlGeneration->getYandexTabs();
                break;
            default:
                return $xml."</Ads>";
        }

        $splitTargetSheets = explode(",", $targetSheets);

        $existingSheets = $this->spreadsheetClientService->getSheets($spreadsheetId);
        foreach ($splitTargetSheets as $targetSheet)
        {
            $targetSheet = trim($targetSheet);
            if(!in_array($targetSheet, $existingSheets))
            {
                continue;
            }

            $targetSheet = trim($targetSheet);
            $headerRange = $targetSheet.'!A1:FZ1';
            $headerResponse = $this->spreadsheetClientService->getSpreadsheetCellsRange($spreadsheetId, $headerRange, false);
            $propertyColumns = new TableHeader($headerResponse[0]);

            $range = $targetSheet.'!A2:FZ5001';
            $values = $this->spreadsheetClientService->getSpreadsheetCellsRange($spreadsheetId, $range, false);

            $xml.= $this->createAdsForSheet($values, $propertyColumns, $targetSheet);
        }

        return $xml.'</Ads>';
    }
}
