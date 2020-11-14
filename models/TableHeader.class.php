<?php

class TableHeader {
    
    public $ID;
    public $category;
    public $goodsType;
    public $adType;
    public $condition;
    public $title;
    public $description;
    public $price;
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
    
    
    public function __construct(array $headers)
    {
        foreach($headers as $colNum => $header)
        {
            $header = trim($header);
            switch($header)
            {
                case "ID":
                    $this->ID = $colNum;
                    break;
                case "Категория товара":
                    $this->category = $colNum;
                    break;
                case "Вид товара":
                    $this->goodsType = $colNum;
                    break;
                case "Вид объявления":
                    $this->adType = $colNum;
                    break;
                case "Состояние товара":
                    $this->condition = $colNum;
                    break;
                case "Заголовок объявления":
                    $this->title = $colNum;
                    break;
                case "Описание":
                    $this->description = $colNum;
                    break;
                case "Цена":
                    $this->price = $colNum;
                    break;
                case "Название папки с фотографиями":
                    $this->subFolderName = $colNum;
                    break;
                case "Ссылки на фотографии (заполняется программой автоматически, если заполнено название папки)":
                    $this->imagesRaw = $colNum;
                    break;
                case "Ссылка на видео":
                    $this->videoURL = $colNum;
                    break;
                case "Регион РФ":
                    $this->region = $colNum;
                    break;
                case "Район/Административный округ":
                    $this->district = $colNum;
                    break;
                case "Населенный пункт":
                    $this->city = $colNum;
                    break;
                case "Район населенного пункта":
                    $this->area = $colNum;
                    break;
                case "Улица":
                    $this->street = $colNum;
                    break;
                case "Номер дома":
                    $this->house = $colNum;
                    break;
                case "Телефон":
                    $this->phone = $colNum;
                    break;
                case "Менеджер":
                    $this->manager = $colNum;
                    break;
                case "Дата и время начала размещения объявления (часовой пояс MSK)":
                    $this->dateCreated = $colNum;
                    break;
                case "Группа товаров":
                    $this->goodsGroup = $colNum;
                    break;
                case "Подтип услуги  и Вид одежды":
                    $this->subTypeApparel = $colNum;
                    break;
                case "Размер":
                    $this->size = $colNum;
                    break;
                case "Дата окончания размещения объявления":
                    $this->dateEnd = $colNum;
                    break;
                case "Avito ID (для объявлений, размещенных вручную) [?]":
                    $this->avitoManualID = $colNum;
                    break;
                case "Управление платными услугами [?]":
                    $this->paidControl = $colNum;
                    break;
            }
        }
    }
}

?>