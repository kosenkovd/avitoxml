<?php


namespace App\Models\Ads;


use App\Models\TableHeader;

class ConstructionMaterialAd extends AdBase
{
    protected $goodsType;
    protected $goodsSubType;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->goodsType = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
        $this->goodsSubType = isset($row[$propertyColumns->subTypeApparel])
            ? htmlspecialchars($row[$propertyColumns->subTypeApparel])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        return <<<AVITOXML
    <Ad>
$defaultTags

        <GoodsType>$this->goodsType</GoodsType>
        <GoodsSubType>$this->goodsSubType</GoodsSubType>
    </Ad>
AVITOXML;
    }
}
