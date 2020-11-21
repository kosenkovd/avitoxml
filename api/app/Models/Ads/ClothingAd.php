<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class ClothingAd extends AdBase
{
    protected $goodsType;
    protected $apparel;
    protected $size;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->goodsType = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
        $this->apparel = isset($row[$propertyColumns->subTypeApparel])
            ? htmlspecialchars($row[$propertyColumns->subTypeApparel])
            : null;
        $this->size = isset($row[$propertyColumns->size])
            ? htmlspecialchars($row[$propertyColumns->size])
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

        <GoodsType>$this->goodsType</GoodsType>
        <Apparel>$this->apparel</Apparel>
        <Size>$this->size</Size>

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
