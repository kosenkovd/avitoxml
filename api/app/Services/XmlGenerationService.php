<?php


namespace App\Services;

use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Models\Ads;
use App\Models\TableHeader;
use App\Repositories\Interfaces\IDictRepository;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IXmlGenerationService;
use DateTimeZone;
use Illuminate\Support\Carbon;
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
     * @var IDictRepository dict repository.
     */
    private IDictRepository $dictRepository;
    
    private bool $adsLimitEnabled = true;

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
//        return !$idFieldPresent;
    
        $dateCreated = @$row[@$propertyColumns->dateCreated];
        if(is_null($dateCreated) || $dateCreated == '')
        {
            return !$idFieldPresent;
        }
        else
        {
            $dateRaw = $row[$propertyColumns->dateCreated];
            $dateRawFixed = $dateRaw;
            if(!strpos($dateRawFixed, ":")) {
                $dateRawFixed .= ' 00:00';
            }
            $dateRawFixed = preg_replace('/\./', '-', $dateRawFixed);
    
            try {
                $date = Carbon::createFromTimeString($dateRawFixed, new DateTimeZone("Europe/Moscow"));
                return !$idFieldPresent || $date->getTimestamp() > time();
            } catch (\Exception $exception) {
                Log::notice("Notice on 'yandex' ". $dateRaw);
                
                return !$idFieldPresent;
            }
        }
    }

	/**
	 * Create ads from sheet rows for Avito.
	 *
	 * @param array       $values rows from sheet.
	 * @param TableHeader $propertyColumns
	 * @param string      $targetSheet
	 * @param int         $adsLimit
	 * @return string generated ads.
	 */
    private function createAdsForAvitoSheet(
    	array $values,
		TableHeader $propertyColumns,
		string $targetSheet,
		int $adsLimit
	): string
    {
        $xml = "";
        $ads = 0;
        foreach ($values as $numRow => $row) {
            if($this->shouldSkipRow($row, $propertyColumns, $targetSheet)) {
                continue;
            }
            
            $ads++;
            if ($this->isAdsLimitReached($ads, $adsLimit)) {
            	break;
			}

            $ad = $this->getAvitoAd($row, $propertyColumns);

            $xml.= $ad->toAvitoXml().PHP_EOL;
        }

        return $xml;
    }

    private function isAdsLimitReached(int $adNumber, int $limit): bool
	{
		return $this->adsLimitEnabled && ($adNumber > $limit);
	}
    
    private function getAvitoAd(array $row, TableHeader $propertyColumns): Ads\AdBase
    {
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
        
        return $ad;
    }

	/**
	 * Create ads from sheet rows for Yandex.
	 *
	 * @param array       $values rows from sheet.
	 * @param TableHeader $propertyColumns
	 * @param string      $targetSheet
	 * @param int         $adsLimit
	 * @return string generated ads.
	 */
    private function createAdsForYandexSheet(
    	array $values,
		TableHeader $propertyColumns,
		string $targetSheet,
		int $adsLimit
	): string
    {
        $xml = "";
        $ads = 0;
        foreach ($values as $numRow => $row) {
            if($this->shouldSkipRow($row, $propertyColumns, $targetSheet))
            {
                continue;
            }
    
            $ads++;
			if ($this->isAdsLimitReached($ads, $adsLimit)) {
				break;
			}
            
            $ad = new Ads\YandexAd($row, $propertyColumns);
            
            $xml.= $ad->toYandexXml().PHP_EOL;
        }
    
        return $xml;
    }

	/**
	 * Create ads from sheet rows for Ula.
	 *
	 * @param array       $values rows from sheet.
	 * @param TableHeader $propertyColumns
	 * @param string      $targetSheet
	 * @param int         $adsLimit
	 * @return string generated ads.
	 */
    private function createAdsForUlaSheet(
    	array $values,
		TableHeader $propertyColumns,
		string $targetSheet,
		int $adsLimit
	): string
    {
        $ulaCategories = $this->dictRepository->getUlaCategories();
        
        $xml = "";
        $ads = 0;
        foreach ($values as $numRow => $row) {
            if($this->shouldSkipRow($row, $propertyColumns, $targetSheet)) {
                continue;
            }
            
            if ($this->shouldSkipUlaRow($row, $propertyColumns)) {
                continue;
            }
    
            $ads++;
			if ($this->isAdsLimitReached($ads, $adsLimit)) {
				break;
			}

            $ad = new Ads\UlaAd($row, $propertyColumns, $ulaCategories);
            
            $xml.= $ad->toUlaXml().PHP_EOL;
        }
    
        return $xml;
    }
    
    private function shouldSkipUlaRow(array $row, TableHeader $propertyColumns): bool
    {
        if (!$this->isExistsInRow($row, $propertyColumns->dateCreated)) {
            return false;
        }
        
        $dateRaw = $row[$propertyColumns->dateCreated];
        $dateRawFixed = $dateRaw;
        if(!strpos($dateRawFixed, ":")) {
            $dateRawFixed .= ' 00:00';
        }
        $dateRawFixed = preg_replace('/\./', '-', $dateRawFixed);
    
        try {
            $date = Carbon::createFromTimeString($dateRawFixed, new DateTimeZone("Europe/Moscow"));
            return $date->getTimestamp() > time();
        } catch (\Exception $exception) {
            Log::notice("Notice on 'ula' ".$dateRaw);
        
            return true;
        }
    }
    
    private function isExistsInRow(array $row, ?int $column): bool
    {
        return !is_null($column) &&
            isset($row[$column]) &&
            (trim($row[$column]) != '');
    }

    public function __construct(
        ISpreadsheetClientService $spreadsheetClientService,
        SheetNames $sheetNames,
        XmlGeneration $xmlGeneration,
        IDictRepository $dictRepository
    )
    {
        $this->spreadsheetClientService = $spreadsheetClientService;
        $this->sheetNamesConfig = $sheetNames;
        $this->xmlGeneration = $xmlGeneration;
        $this->dictRepository = $dictRepository;
    }

    /**
     * @inheritDoc
     */
    public function generateAvitoXML(string $spreadsheetId, string $targetSheet, int $adsLimit) : string
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

        $existingSheets = $this->spreadsheetClientService->getSheets(
            $spreadsheetId
        );
        foreach ($splitTargetSheets as $targetSheet)
        {
            $targetSheet = trim($targetSheet);
            if(!in_array($targetSheet, $existingSheets))
            {
                continue;
            }
    
            try {
                $range = $targetSheet.'!A1:FZ5001';
                $values = $this->spreadsheetClientService->getSpreadsheetCellsRange(
                    $spreadsheetId,
                    $range
                );
                $propertyColumns = new TableHeader(array_shift($values));
            } catch (\Exception $exception) {
                $message = "Error on '". $spreadsheetId."' while getting spreadsheet values".PHP_EOL.$exception->getMessage();
//                Log::error($message);
                
                throw $exception;
            }
            
            sleep(1);
            
            $xml.= $this->createAdsForAvitoSheet($values, $propertyColumns, $targetSheet, $adsLimit);
        }

        return $xml.'</Ads>';
    }
    
    /**
     * @inheritDoc
     */
    public function generateYandexXML(string $spreadsheetId, string $targetSheet, int $adsLimit) : string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.
            '<feed version="1">'.PHP_EOL.
            '<offers>'.PHP_EOL;
    
        switch($targetSheet) {
            case "Яндекс":
                $targetSheets = $this->xmlGeneration->getYandexTabs();
                break;
            default:
                return $xml.'</offers>'.PHP_EOL.
                    '</feed>';
        }
    
        $splitTargetSheets = explode(",", $targetSheets);

        $existingSheets = $this->spreadsheetClientService->getSheets(
            $spreadsheetId
        );
    
        foreach ($splitTargetSheets as $targetSheet)
        {
            $targetSheet = trim($targetSheet);
            if(!in_array($targetSheet, $existingSheets))
            {
                continue;
            }
        
            try {
                $range = $targetSheet.'!A1:FZ5001';
                $values = $this->spreadsheetClientService->getSpreadsheetCellsRange(
                    $spreadsheetId,
                    $range
                );
                $propertyColumns = new TableHeader(array_shift($values));
            } catch (\Exception $exception) {
                $message = "Error on '". $spreadsheetId."' while getting spreadsheet values".PHP_EOL.$exception->getMessage();
//                Log::error($message);
            
                throw $exception;
            }
        
            sleep(1);
        
            $xml.= $this->createAdsForYandexSheet($values, $propertyColumns, $targetSheet, $adsLimit);
        }
    
        return $xml.'</offers>'.PHP_EOL.
            '</feed>';
    }
    
    public function generateUlaXML(string $spreadsheetId, string $targetSheet, int $adsLimit): string
    {
        $defaultTime = Carbon::now(new DateTimeZone("Europe/Moscow"))
            ->format('Y-m-d H:i:s');
        
        switch ($targetSheet) {
            case "Юла":
                $targetSheets = $this->xmlGeneration->getYoulaTabs();
                break;
            default:
                $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.
                    '<yml_catalog date="'.$defaultTime.'">'.PHP_EOL.
                    '<shop>'.PHP_EOL.
                    '<offers>'.PHP_EOL.
                    '</offers>'.PHP_EOL.
                    '</shop>'.PHP_EOL.
                    '</yml_catalog>';
        }
    
        $splitTargetSheets = explode(",", $targetSheets);
    
        $existingSheets = $this->spreadsheetClientService->getSheets(
            $spreadsheetId
        );
    
        foreach ($splitTargetSheets as $targetSheet) {
            $targetSheet = trim($targetSheet);
            if (!in_array($targetSheet, $existingSheets)) {
                continue;
            }
        
            try {
                $range = $targetSheet.'!A1:FZ5001';
                $values = $this->spreadsheetClientService->getSpreadsheetCellsRange(
                    $spreadsheetId,
                    $range
                );
                $propertyColumns = new TableHeader(array_shift($values));
            } catch (\Exception $exception) {
                $message = "Error on '".$spreadsheetId."' while getting spreadsheet values".PHP_EOL.$exception->getMessage();
//                Log::error($message);
            
                throw $exception;
            }
        
            sleep(1);
    
            $dateBegin = $defaultTime;
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.
                '<yml_catalog date="'.$dateBegin.'">'.PHP_EOL.
                '<shop>'.PHP_EOL.
                '<offers>'.PHP_EOL;
        
            $xml .= $this->createAdsForUlaSheet($values, $propertyColumns, $targetSheet, $adsLimit);
            return $xml.'</offers>'.PHP_EOL.
                '</shop>'.PHP_EOL.
                '</yml_catalog>';
        }
        
        return $xml;
    }
}
