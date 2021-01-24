<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class ClothingAd extends AdBase
{
    protected ?string $goodsType = null;
    protected ?string $apparel = null;
    protected ?string $size = null;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->goodsType = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
        $this->apparel = isset($row[$propertyColumns->subTypeApparel])
            ? htmlspecialchars($row[$propertyColumns->subTypeApparel])
            : null;
        $this->size = isset($row[$propertyColumns->size])
            ? htmlspecialchars($row[$propertyColumns->size])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        $resultXml = $this->addTagIfPropertySet($this->goodsType, "GoodsType");
        $resultXml.= $this->addTagIfPropertySet($this->apparel, "Apparel");
        $resultXml.= $this->addTagIfPropertySet($this->size, "Size");

        return <<<AVITOXML
    <Ad>
$defaultTags
$resultXml
    </Ad>
AVITOXML;
    }
}
