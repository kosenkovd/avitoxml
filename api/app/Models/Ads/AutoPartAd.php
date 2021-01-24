<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class AutoPartAd extends AdBase
{
    protected ?string $autoPart = null;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->autoPart = isset($row[$propertyColumns->autoPart])
            ? htmlspecialchars($row[$propertyColumns->autoPart])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        $resultXml = $this->addTagIfPropertySet($this->autoPart, "avtozapchasti_tip");

        return <<<AVITOXML
    <Ad>
$defaultTags
$resultXml
    </Ad>
AVITOXML;
    }
}
