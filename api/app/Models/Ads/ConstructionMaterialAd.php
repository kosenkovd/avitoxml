<?php


namespace App\Models\Ads;


use App\Models\TableHeader;

class ConstructionMaterialAd extends AdBase
{
    protected ?string $goodsType = null;
    protected ?string $goodsSubType = null;

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

        $resultXml = $this->addTagIfPropertySet($this->goodsType, "GoodsType");
        $resultXml.= $this->addTagIfPropertySet($this->goodsSubType, "GoodsSubType");

        return <<<AVITOXML
    <Ad>
$defaultTags
$resultXml
    </Ad>
AVITOXML;
    }
}
