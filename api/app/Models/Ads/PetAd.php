<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class PetAd extends AdBase
{
    protected $breed;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->breed = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        return <<<AVITOXML
    <Ad>
$defaultTags

        <Breed>$this->breed</Breed>
    </Ad>
AVITOXML;
    }
}
