<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class ClothingAd extends AdBase
{
    protected $goodsType;
    protected $apparel;
    protected $size;

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

        return <<<AVITOXML
    <Ad>
$defaultTags

        <GoodsType>$this->goodsType</GoodsType>
        <Apparel>$this->apparel</Apparel>
        <Size>$this->size</Size>
    </Ad>
AVITOXML;
    }
}
