<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class VehicleTypeAd extends AdBase
{
    protected ?string $vehicleType = null;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->vehicleType = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        $resultXml = $this->addTagIfPropertySet($this->vehicleType, "VehicleType");

        return <<<AVITOXML
    <Ad>
$defaultTags
$resultXml
    </Ad>
AVITOXML;
    }
}
