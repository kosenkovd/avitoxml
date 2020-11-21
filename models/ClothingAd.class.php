<?php

class ClothingAd extends AdBase
{
    protected $goodsType;
    protected $apparel;
    protected $size;
     
    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);
        
        $this->goodsType = htmlspecialchars($row[$propertyColumns->goodsType]);
        $this->apparel = htmlspecialchars($row[$propertyColumns->subTypeApparel]);
        $this->size = htmlspecialchars($row[$propertyColumns->size]);
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
    </Ad>
AVITOXML;
    }
}