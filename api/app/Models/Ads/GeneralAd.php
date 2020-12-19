<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class GeneralAd extends AdBase
{
    protected $goodsType;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->goodsType = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        return <<<AVITOXML
    <Ad>
$defaultTags

        <GoodsType>$this->goodsType</GoodsType>
    </Ad>
AVITOXML;
    }
}
