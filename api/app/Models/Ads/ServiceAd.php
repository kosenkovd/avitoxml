<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class ServiceAd extends AdBase
{
    protected ?string $serviceType = null;
    protected ?string $serviceSubtype = null;

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

        $resultXml = $this->addTagIfPropertySet($this->serviceType, "ServiceType");
        $resultXml.= $this->addTagIfPropertySet($this->serviceSubtype, "ServiceSubtype");

        return <<<AVITOXML
    <Ad>
$defaultTags
$resultXml
    </Ad>
AVITOXML;
    }
}
