<?php

class ServiceAd extends AdBase
{
    protected $serviceType;
    protected $serviceSubtype;
     
    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);
        
        $this->serviceType = htmlspecialchars($row[$propertyColumns->goodsType]);
        $this->serviceSubtype = htmlspecialchars($row[$propertyColumns->subTypeApparel]);
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
    </Ad>
AVITOXML;
    }
}