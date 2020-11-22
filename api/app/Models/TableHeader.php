<?php

namespace App\Models;

/**
 * Links GoogleSpreadsheet table header names with column numbers.
 *
 * @package App\Models
 */
class TableHeader {

    public $ID;
    public $category;
    public $goodsType;
    public $adType;
    public $condition;
    public $title;
    public $description;
    public $price;
    public $photoSourceFolder;
    public $subFolderName;
    public $imagesRaw;
    public $videoURL;
    public $region;
    public $district;
    public $city;
    public $area;
    public $street;
    public $house;
    public $phone;
    public $manager;
    public $dateCreated;
    public $goodsGroup;
    public $subTypeApparel;
    public $size;
    public $dateEnd;
    public $avitoManualID;
    public $paidControl;
    public $photoCount;

    public $priceSpintax;
    public $descriptionSpintax;
    public $titleSpintax;


    public function __construct(array $headers)
    {
        foreach($headers as $colNum => $header)
        {
            // Lowering case and removing space characters to avoid errors in case of line break, etc.
            $header = mb_strtolower(preg_replace('/\s/i', "", $header));

            switch($header)
            {
                case "id":
                    $this->ID = $colNum;
                    break;
                case "категориятовара":
                    $this->category = $colNum;
                    break;
                case "видтовара":
                    $this->goodsType = $colNum;
                    break;
                case "видобъявления":
                    $this->adType = $colNum;
                    break;
                case "состояниетовара":
                    $this->condition = $colNum;
                    break;
                case "заголовокобъявления":
                    $this->title = $colNum;
                    break;
                case "рандомизаторзаголовка":
                    $this->titleSpintax = $colNum;
                    break;
                case "описание":
                    $this->description = $colNum;
                    break;
                case "рандомизаторописания":
                    $this->descriptionSpintax = $colNum;
                    break;
                case "цена":
                    $this->price = $colNum;
                    break;
                case "рандомизаторцен":
                    $this->priceSpintax = $colNum;
                    break;
                case "генераторпапоксфотографиями":
                    $this->photoSourceFolder = $colNum;
                    break;
                case "названиепапкисфотографиями":
                    $this->subFolderName = $colNum;
                    break;
                case "ссылкинафотографии(заполняетсяпрограммойавтоматически,еслизаполненоназваниепапки)":
                    $this->imagesRaw = $colNum;
                    break;
                case "ссылканавидео":
                    $this->videoURL = $colNum;
                    break;
                case "регионрф":
                    $this->region = $colNum;
                    break;
                case "район/административныйокруг":
                    $this->district = $colNum;
                    break;
                case "населенныйпункт":
                    $this->city = $colNum;
                    break;
                case "районнаселенногопункта":
                    $this->area = $colNum;
                    break;
                case "улица":
                case "улицаилиметро":
                    $this->street = $colNum;
                    break;
                case "номердома":
                    $this->house = $colNum;
                    break;
                case "телефон":
                    $this->phone = $colNum;
                    break;
                case "менеджер":
                    $this->manager = $colNum;
                    break;
                case "датаивремяначаларазмещенияобъявления(часовойпоясmsk)":
                case "датаивремяпубликации(помск)":
                    $this->dateCreated = $colNum;
                    break;
                case "группатоваров":
                    $this->goodsGroup = $colNum;
                    break;
                case "подтипуслугиивидодежды":
                case "Подтипуслугииливидодежды":
                    $this->subTypeApparel = $colNum;
                    break;
                case "размер":
                    $this->size = $colNum;
                    break;
                case "кол-вофото":
                    $this->photoCount = $colNum;
                    break;
                case "датаокончанияразмещенияобъявления":
                case "датаокончанияразмещения":
                    $this->dateEnd = $colNum;
                    break;
                case "avitoid(дляобъявлений,размещенныхвручную)[?]":
                case "avitoid":
                    $this->avitoManualID = $colNum;
                    break;
                case "управлениеплатнымиуслугами[?]":
                case "управлениеплатнымиуслугами":
                    $this->paidControl = $colNum;
                    break;
            }
        }
    }
}

