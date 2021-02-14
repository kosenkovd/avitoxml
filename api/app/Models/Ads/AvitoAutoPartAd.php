<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class AvitoAutoPartAd extends AdBase
{
    protected ?string $goodsType = null;
    protected ?string $autoPartOem = null;
    protected ?string $brand = null;
    protected ?string $rimDiameter = null;
    protected ?string $tireType = null;
    protected ?string $wheelAxle = null;
    protected ?string $rimType = null;
    protected ?string $tireSectionWidth = null;
    protected ?string $tireAspectRatio = null;
    protected ?string $rimBolts = null;
    protected ?string $rimBoltsDiameter = null;


    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->goodsType = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
        $this->autoPartOem = isset($row[$propertyColumns->autoPartOem])
            ? htmlspecialchars($row[$propertyColumns->autoPartOem])
            : null;
        $this->brand = isset($row[$propertyColumns->brand])
            ? htmlspecialchars($row[$propertyColumns->brand])
            : null;
        $this->rimDiameter = isset($row[$propertyColumns->rimDiameter])
            ? htmlspecialchars($row[$propertyColumns->rimDiameter])
            : null;
        $this->tireType = isset($row[$propertyColumns->tireType])
            ? htmlspecialchars($row[$propertyColumns->tireType])
            : null;
        $this->wheelAxle = isset($row[$propertyColumns->wheelAxle])
            ? htmlspecialchars($row[$propertyColumns->wheelAxle])
            : null;
        $this->rimType = isset($row[$propertyColumns->rimType])
            ? htmlspecialchars($row[$propertyColumns->rimType])
            : null;
        $this->tireSectionWidth = isset($row[$propertyColumns->tireSectionWidth])
            ? htmlspecialchars($row[$propertyColumns->tireSectionWidth])
            : null;
        $this->tireAspectRatio = isset($row[$propertyColumns->tireAspectRatio])
            ? htmlspecialchars($row[$propertyColumns->tireAspectRatio])
            : null;
        $this->rimBolts = isset($row[$propertyColumns->rimBolts])
            ? htmlspecialchars($row[$propertyColumns->rimBolts])
            : null;
        $this->rimBoltsDiameter = isset($row[$propertyColumns->rimBoltsDiameter])
            ? htmlspecialchars($row[$propertyColumns->rimBoltsDiameter])
            : null;
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();

        $resultXml = $this->addTagIfPropertySet($this->goodsType, "TypeId");
        $resultXml.= $this->addTagIfPropertySet($this->autoPartOem, "OEM");
        $resultXml.= $this->addTagIfPropertySet($this->brand, "Brand");
        $resultXml.= $this->addTagIfPropertySet($this->rimDiameter, "RimDiameter");
        $resultXml.= $this->addTagIfPropertySet($this->tireType, "TireType");
        $resultXml.= $this->addTagIfPropertySet($this->wheelAxle, "WheelAxle");
        $resultXml.= $this->addTagIfPropertySet($this->rimType, "RimType");
        $resultXml.= $this->addTagIfPropertySet($this->tireSectionWidth, "TireSectionWidth");
        $resultXml.= $this->addTagIfPropertySet($this->tireAspectRatio, "TireAspectRatio");
        $resultXml.= $this->addTagIfPropertySet($this->rimBolts, "RimBolts");
        $resultXml.= $this->addTagIfPropertySet($this->rimBoltsDiameter, "RimBoltsDiameter");

        return <<<AVITOXML
    <Ad>
$defaultTags
$resultXml
    </Ad>
AVITOXML;
    }
}
