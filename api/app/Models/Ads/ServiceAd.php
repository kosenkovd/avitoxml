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
        $imageTags = $this->generateImageAvitoTags($this->images);

        return <<<AVITOXML
    <Ad>
        <Id>$this->id</Id>
        <DateBegin>$this->dateBegin</DateBegin>
        <ManagerName>$this->managerName</ManagerName>
        <ContactPhone>$this->contactPhone</ContactPhone>
        <Address>$this->address</Address>
        <Category>$this->category</Category>

        <ServiceType>$this->serviceType</ServiceType>
        <ServiceSubtype>$this->serviceSubtype</ServiceSubtype>

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
