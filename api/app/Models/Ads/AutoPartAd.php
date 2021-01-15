<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class AutoPartAd extends AdBase
{
    protected $autoPart;

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

        return <<<AVITOXML
    <Ad>
$defaultTags

        <avtozapchasti_tip>$this->autoPart</avtozapchasti_tip>
    </Ad>
AVITOXML;
    }
}
