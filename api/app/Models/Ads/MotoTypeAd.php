<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class MotoTypeAd extends AdBase
{
    protected ?string $motoType = null;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->motoType = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        $resultXml = $this->addTagIfPropertySet($this->motoType, "MotoType");

        return <<<AVITOXML
    <Ad>
$defaultTags
$resultXml
    </Ad>
AVITOXML;
    }
}
