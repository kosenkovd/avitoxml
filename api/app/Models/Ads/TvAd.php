<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class TvAd extends AdBase
{
    protected ?string $productsType = null;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->productsType = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        $resultXml = $this->addTagIfPropertySet($this->productsType, "ProductsType");

        return <<<AVITOXML
    <Ad>
$defaultTags
$resultXml
    </Ad>
AVITOXML;
    }
}
