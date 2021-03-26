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
    
    public ?int $address2 = null;
    public ?int $region2 = null;
    public ?int $city2 = null;
    public ?int $area2 = null;
    public ?int $street2 = null;
    public ?int $house2 = null;
    
    public ?int $address3 = null;
    public ?int $region3 = null;
    public ?int $city3 = null;
    public ?int $area3 = null;
    public ?int $street3 = null;
    public ?int $house3 = null;
    
    public ?int $address4 = null;
    public ?int $region4 = null;
    public ?int $city4 = null;
    public ?int $area4 = null;
    public ?int $street4 = null;
    public ?int $house4 = null;
    
    public ?int $address5 = null;
    public ?int $region5 = null;
    public ?int $city5 = null;
    public ?int $area5 = null;
    public ?int $street5 = null;
    public ?int $house5 = null;
    
    public ?int $address6 = null;
    public ?int $region6 = null;
    public ?int $city6 = null;
    public ?int $area6 = null;
    public ?int $street6 = null;
    public ?int $house6 = null;
    
    public ?int $address7 = null;
    public ?int $region7 = null;
    public ?int $city7 = null;
    public ?int $area7 = null;
    public ?int $street7 = null;
    public ?int $house7 = null;
    
    public ?int $address8 = null;
    public ?int $region8 = null;
    public ?int $city8 = null;
    public ?int $area8 = null;
    public ?int $street8 = null;
    public ?int $house8 = null;
    
    public ?int $address9 = null;
    public ?int $region9 = null;
    public ?int $city9 = null;
    public ?int $area9 = null;
    public ?int $street9 = null;
    public ?int $house9 = null;
    
    public ?int $address10 = null;
    public ?int $region10 = null;
    public ?int $city10 = null;
    public ?int $area10 = null;
    public ?int $street10 = null;
    public ?int $house10 = null;
    
    public ?int $phone = null;
    public ?int $manager = null;
    public ?int $contactsType = null;
    public ?int $dateCreated = null;
    public ?int $timezone = null;
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
                case "адрес":
                    $this->address = $colNum;
                    break;
                case "регионрф":
                    $this->region = $colNum;
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
                case "адрес2":
                    $this->address2 = $colNum;
                    break;
                case "регионрф2":
                    $this->region2 = $colNum;
                    break;
                case "населенныйпункт2":
                    $this->city2 = $colNum;
                    break;
                case "районнаселенногопункта2":
                    $this->area2 = $colNum;
                    break;
                case "улица2":
                case "улицаилиметро2":
                    $this->street2 = $colNum;
                    break;
                case "номердома2":
                    $this->house2 = $colNum;
                    break;
                case "адрес3":
                    $this->address3 = $colNum;
                    break;
                case "регионрф3":
                    $this->region3 = $colNum;
                    break;
                case "населенныйпункт3":
                    $this->city3 = $colNum;
                    break;
                case "районнаселенногопункта3":
                    $this->area3 = $colNum;
                    break;
                case "улица3":
                case "улицаилиметро3":
                    $this->street3 = $colNum;
                    break;
                case "номердома3":
                    $this->house3 = $colNum;
                    break;
                case "адрес4":
                    $this->address4 = $colNum;
                    break;
                case "регионрф4":
                    $this->region4 = $colNum;
                    break;
                case "населенныйпункт4":
                    $this->city4 = $colNum;
                    break;
                case "районнаселенногопункта4":
                    $this->area4 = $colNum;
                    break;
                case "улица4":
                case "улицаилиметро4":
                    $this->street4 = $colNum;
                    break;
                case "номердома4":
                    $this->house4 = $colNum;
                    break;
                case "адрес5":
                    $this->address5 = $colNum;
                    break;
                case "регионрф5":
                    $this->region5 = $colNum;
                    break;
                case "населенныйпункт5":
                    $this->city5 = $colNum;
                    break;
                case "районнаселенногопункта5":
                    $this->area5 = $colNum;
                    break;
                case "улица5":
                case "улицаилиметро5":
                    $this->street5 = $colNum;
                    break;
                case "номердома5":
                    $this->house5 = $colNum;
                    break;
                case "адрес6":
                    $this->address6 = $colNum;
                    break;
                case "регионрф6":
                    $this->region6 = $colNum;
                    break;
                case "населенныйпункт6":
                    $this->city6 = $colNum;
                    break;
                case "районнаселенногопункта6":
                    $this->area6 = $colNum;
                    break;
                case "улица6":
                case "улицаилиметро6":
                    $this->street6 = $colNum;
                    break;
                case "номердома6":
                    $this->house6 = $colNum;
                    break;
                case "адрес7":
                    $this->address7 = $colNum;
                    break;
                case "регионрф7":
                    $this->region7 = $colNum;
                    break;
                case "населенныйпункт7":
                    $this->city7 = $colNum;
                    break;
                case "районнаселенногопункта7":
                    $this->area7 = $colNum;
                    break;
                case "улица7":
                case "улицаилиметро7":
                    $this->street7 = $colNum;
                    break;
                case "номердома7":
                    $this->house7 = $colNum;
                    break;
                case "адрес8":
                    $this->address8 = $colNum;
                    break;
                case "регионрф8":
                    $this->region8 = $colNum;
                    break;
                case "населенныйпункт8":
                    $this->city8 = $colNum;
                    break;
                case "районнаселенногопункта8":
                    $this->area8 = $colNum;
                    break;
                case "улица8":
                case "улицаилиметро8":
                    $this->street8 = $colNum;
                    break;
                case "номердома8":
                    $this->house8 = $colNum;
                    break;
                case "адрес9":
                    $this->address9 = $colNum;
                    break;
                case "регионрф9":
                    $this->region9 = $colNum;
                    break;
                case "населенныйпункт9":
                    $this->city9 = $colNum;
                    break;
                case "районнаселенногопункта9":
                    $this->area9 = $colNum;
                    break;
                case "улица9":
                case "улицаилиметро9":
                    $this->street9 = $colNum;
                    break;
                case "номердома9":
                    $this->house9 = $colNum;
                    break;
                case "адрес10":
                    $this->address10 = $colNum;
                    break;
                case "регионрф10":
                    $this->region10 = $colNum;
                    break;
                case "населенныйпункт10":
                    $this->city10 = $colNum;
                    break;
                case "районнаселенногопункта10":
                    $this->area10 = $colNum;
                    break;
                case "улица10":
                case "улицаилиметро10":
                    $this->street10 = $colNum;
                    break;
                case "номердома10":
                    $this->house10 = $colNum;
                    break;
                case "телефон":
                    $this->phone = $colNum;
                    break;
                case "менеджер":
                    $this->manager = $colNum;
                    break;
                case "способсвязи":
                    $this->contactsType = $colNum;
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
                case "часовойпояс":
                    $this->timezone = $colNum;
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

