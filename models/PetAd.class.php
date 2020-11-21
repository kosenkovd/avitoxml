<?php

class PetAd extends AdBase
{
    protected $breed;
     
    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);
        
        $this->breed = htmlspecialchars($row[$propertyColumns->goodsType]);
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
        
        <Breed>$this->breed</Breed>
        
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