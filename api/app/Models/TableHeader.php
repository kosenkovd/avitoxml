<?php

namespace App\Models;

/**
 * Links GoogleSpreadsheet table header names with column numbers.
 *
 * @package App\Models
 */
class TableHeader {

    public ?int $ID = null;
    public ?int $category = null;
    public ?int $goodsType = null;
    public ?int $adType = null;
    public ?int $condition = null;
    public ?int $title = null;
    public ?int $description = null;
    public ?int $price = null;
    public ?int $photoSourceFolder = null;
    public ?int $subFolderName = null;
    public ?int $imagesRaw = null;
    public ?int $videoURL = null;
    public ?int $address = null;
    public ?int $region = null;
    public ?int $city = null;
    public ?int $area = null;
    public ?int $street = null;
    public ?int $house = null;
    public ?int $phone = null;
    public ?int $manager = null;
    public ?int $dateCreated = null;
    public ?int $goodsGroup = null;
    public ?int $subTypeApparel = null;
    public ?int $size = null;
    public ?int $dateEnd = null;
    public ?int $avitoManualID = null;
    public ?int $paidControl = null;
    public ?int $photoCount = null;
    public ?int $autoPart = null;

    public ?int $industry = null;
    public ?int $workSchedule = null;
    public ?int $experience = null;
    public ?int $salary = null;

    public ?int $priceSpintax = null;
    public ?int $descriptionSpintax = null;
    public ?int $titleSpintax = null;

    public ?int $autoPartOem = null;
    public ?int $brand = null;
    public ?int $rimDiameter = null;
    public ?int $tireType = null;
    public ?int $wheelAxle = null;
    public ?int $rimType = null;
    public ?int $tireSectionWidth = null;
    public ?int $tireAspectRatio = null;
    public ?int $rimBolts = null;
    public ?int $rimBoltsDiameter = null;
    public ?int $displayAreas = null;

    public ?int $placementType = null;
    public ?int $messages = null;


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
                case "категория":
                    $this->category = $colNum;
                    break;
                case "видтовара":
                case "подкатегория":
                    $this->goodsType = $colNum;
                    break;
                case "видобъявления":
                    $this->adType = $colNum;
                    break;
                case "состояниетовара":
                    $this->condition = $colNum;
                    break;
                case "заголовокобъявления":
                case "названиевакансии":
                case "названиезапчасти":
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
                case "ссылка(и)нафото":
                    $this->imagesRaw = $colNum;
                    break;
                case "ссылканавидео":
                    $this->videoURL = $colNum;
                    break;
                case "регионрф":
                    $this->region = $colNum;
                    break;
                case "адрес":
                    $this->address = $colNum;
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
                case "типавтозапчастей":
                    $this->autoPart = $colNum;
                    break;
                case "датаивремяначаларазмещенияобъявления(часовойпоясmsk)":
                case "датаивремяпубликации(помск)":
                case "датапубликации":
                case "датаивремяпубликации":
                    $this->dateCreated = $colNum;
                    break;
                case "группатоваров":
                    $this->goodsGroup = $colNum;
                    break;
                case "подтипуслугиивидодежды":
                case "подтипуслугииливидодежды":
                case "подтипуслугивидодеждытипстроймат":
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
                case "способразмещения":
                    $this->placementType = $colNum;
                    break;
                case "сообщения":
                    $this->messages = $colNum;
                    break;
                case "сферадеятельности":
                    $this->industry = $colNum;
                    break;
                case "графикработы":
                    $this->workSchedule = $colNum;
                    break;
                case "опытработы":
                    $this->experience = $colNum;
                    break;
                case "зарплата":
                    $this->salary = $colNum;
                    break;
                case "номердеталиoem":
                    $this->autoPartOem = $colNum;
                    break;
                case "производитель":
                    $this->brand = $colNum;
                    break;
                case "диаметрвдюймах":
                    $this->rimDiameter = $colNum;
                    break;
                case "сезонностьшиниликолес":
                    $this->tireType = $colNum;
                    break;
                case "осьмотошины":
                    $this->wheelAxle = $colNum;
                    break;
                case "типдиска":
                    $this->rimType = $colNum;
                    break;
                case "ширинапрофиляшины":
                    $this->tireSectionWidth = $colNum;
                    break;
                case "высотапрофиляшины":
                    $this->tireAspectRatio = $colNum;
                    break;
                case "ширинаобода,дюймов":
                    $this->rimBolts = $colNum;
                    break;
                case "диаметрподболты,дюймов":
                    $this->rimBoltsDiameter = $colNum;
                    break;
                case "зоныпоказа(встолбик)":
                    $this->displayAreas = $colNum;
                    break;
            }
        }
    }
}

