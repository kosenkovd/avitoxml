<?php

namespace App\Models\Ads;

use App\Models\TableHeader;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MultimarketAd extends AdBase
{
    protected Collection $usedCategories;
    
    protected ?string $groupId;
    
    protected ?string $multimarketParam1Name;
    protected ?string $multimarketParam2Name;
    protected ?string $multimarketParam3Name;
    protected ?string $multimarketParam4Name;
    protected ?string $multimarketParam1Value;
    protected ?string $multimarketParam2Value;
    protected ?string $multimarketParam3Value;
    protected ?string $multimarketParam4Value;
    
    protected ?string $quantity;
    protected ?string $oldPrice;
    protected ?string $dimensions;
    protected ?string $vendorCode;
    protected ?string $weight;
    
    public function __construct(array $row, TableHeader $propertyColumns, Collection $usedCategories)
    {
        parent::__construct($row, $propertyColumns);
        
        $this->usedCategories = $usedCategories;
        
        $this->groupId = isset($row[$propertyColumns->goodsGroup])
            ? htmlspecialchars(trim($row[$propertyColumns->goodsGroup]))
            : null;
        
        $this->multimarketParam1Name = isset($row[$propertyColumns->multimarketParam1Name])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketParam1Name]))
            : null;
        $this->multimarketParam1Value = isset($row[$propertyColumns->multimarketParam1Value])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketParam1Value]))
            : null;
        $this->multimarketParam2Name = isset($row[$propertyColumns->multimarketParam2Name])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketParam2Name]))
            : null;
        $this->multimarketParam2Value = isset($row[$propertyColumns->multimarketParam2Value])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketParam2Value]))
            : null;
        $this->multimarketParam3Name = isset($row[$propertyColumns->multimarketParam3Name])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketParam3Name]))
            : null;
        $this->multimarketParam3Value = isset($row[$propertyColumns->multimarketParam3Value])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketParam3Value]))
            : null;
        $this->multimarketParam4Name = isset($row[$propertyColumns->multimarketParam4Name])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketParam4Name]))
            : null;
        $this->multimarketParam4Value = isset($row[$propertyColumns->multimarketParam4Value])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketParam4Value]))
            : null;

        $this->quantity = isset($row[$propertyColumns->multimarketQuantity])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketQuantity]))
            : null;
        $this->oldPrice = isset($row[$propertyColumns->multimarketOldprice])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketOldprice]))
            : null;
        $this->dimensions = isset($row[$propertyColumns->multimarketDimensions])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketDimensions]))
            : null;
        $this->weight = isset($row[$propertyColumns->multimarketWeight])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketWeight]))
            : null;
        $this->vendorCode = isset($row[$propertyColumns->multimarketVendorCode])
            ? htmlspecialchars(trim($row[$propertyColumns->multimarketVendorCode]))
            : null;
    
        $this->description = isset($row[$propertyColumns->description])
            ? htmlspecialchars(trim($row[$propertyColumns->description]))
            : null;
    }
    
    public function toAvitoXml(): string
    {
        return '';
    }
    
    public function toMultimarketXml(): string
    {
        $id = $this->id;
        $groupId = $this->groupId;
        $defaultTags = $this->generateUlaXML();
        
        return <<<XML
            <offer id="$id" available="true" group_id="$groupId">
            $defaultTags
            </offer>
            XML;
    }
    
    protected function generateUlaXML(): string
    {
        $imageTags = $this->generateImageUlaTags($this->images);
        $params = $this->generateMultimarketParams();
        
        $resultXml = $this->addTagIfPropertySet($this->price, "price");
        $resultXml .= $this->addTagIfPropertySet('RUB', "currencyId");
        $resultXml .= $this->addTagIfPropertySet($this->generateCategoryId(), "categoryId");
        $resultXml .= $imageTags;
        $resultXml .= $this->addTagIfPropertySet($this->title, "name");
        $resultXml .= $this->addTagIfPropertySet("<![CDATA[$this->description]]>", "description");
        $resultXml .= $params;
        $resultXml .= $this->addTagIfPropertySet($this->quantity, "quantity");
        $resultXml .= $this->addTagIfPropertySet($this->oldPrice, "oldprice");
        $resultXml .= $this->addTagIfPropertySet($this->dimensions, "dimensions");
        $resultXml .= $this->addTagIfPropertySet($this->weight, "weight");
        $resultXml .= $this->addTagIfPropertySet($this->vendorCode, "vendorCode");
    
        return $resultXml;
    }
    
    protected function generateImageUlaTags(array $images): string
    {
        if (count($images) == 0 || (count($images) == 1 && $images[0] == "")) {
            return "";
        }
        $imageTags = PHP_EOL;
        foreach ($images as $image) {
            $image = trim($image);
            $image = str_replace('&', '&amp;', $image);
            $imageTags .= "<picture>".$image."</picture>".PHP_EOL;
        }
        return $imageTags;
    }
    
    protected function generateMultimarketParams(): string
    {
        $res = '';
        for ($i = 0; $i < 4; $i++) {
            $res .= $this->generateMultimarketParam($i + 1);
        }
        return $res;
    }
    
    protected function generateMultimarketParam(int $number): string
    {
        $nameProp = 'multimarketParam'.$number.'Name';
        $valueProp = 'multimarketParam'.$number.'Value';
        return '<param name="'.$this->$nameProp.'">'.$this->$valueProp.'</param>'.PHP_EOL;
    }
    
    protected function generateCategoryId(): string
    {
        if (!$this->category) {
            return "";
        }

        $search = preg_replace('/\s/i', "", trim($this->category));
        $categoryId = $this->usedCategories->search($search);
        
        if ($categoryId === false) {
            Log::channel('notice')->alert('can\'t find '.$this->category);
            return "";
        }
    
        return $categoryId + 1;
    }
}
