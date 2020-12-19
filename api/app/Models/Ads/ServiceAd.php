<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class ServiceAd extends AdBase
{
    protected $serviceType;
    protected $serviceSubtype;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->serviceType = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
        $this->serviceSubtype = isset($row[$propertyColumns->subTypeApparel])
            ? htmlspecialchars($row[$propertyColumns->subTypeApparel])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        return <<<AVITOXML
    <Ad>
$defaultTags

        <ServiceType>$this->serviceType</ServiceType>
        <ServiceSubtype>$this->serviceSubtype</ServiceSubtype>
    </Ad>
AVITOXML;
    }
}
