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
        $imageTags = $this->generateImageAvitoTags($this->images);

        return <<<AVITOXML
    <Ad>
        <Id>$this->id</Id>
        <DateBegin>$this->dateBegin</DateBegin>
        <ManagerName>$this->managerName</ManagerName>
        <ContactPhone>$this->contactPhone</ContactPhone>
        <Address>$this->address</Address>
        <Category>$this->category</Category>

        <VehicleType>$this->vehicleType</VehicleType>

        <AdType>$this->adType</AdType>
        <Condition>$this->condition</Condition>
        <Title>$this->title</Title>
        <Description><![CDATA[$this->description]]></Description>
        <Price>$this->price</Price>
        <Images>$imageTags</Images>
        <VideoURL>$this->videoURL</VideoURL>
        <AvitoId>$this->avitoId</AvitoId>
        <AdStatus>$this->adStatus</AdStatus>
    </Ad>
AVITOXML;
    }
}
