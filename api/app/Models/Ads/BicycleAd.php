<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class BicycleAd extends AdBase
{
    protected $vehicleType;

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

        return <<<AVITOXML
    <Ad>
$defaultTags

        <VehicleType>$this->vehicleType</VehicleType>
    </Ad>
AVITOXML;
    }
}
