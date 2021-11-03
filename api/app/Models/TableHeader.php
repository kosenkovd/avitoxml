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
    public ?int $apparelType = null;

    public ?int $workTypes = null;
    public ?int $extraBox = null;
    public ?int $bodyRepair = null;
    public ?int $workExperience = null;
    public ?int $guarantee = null;
    public ?int $selfService = null;
    public ?int $diagnostics = null;
    public ?int $wheelService = null;
    public ?int $additionalEquipment = null;
    public ?int $tuning = null;
    public ?int $maintenance = null;
    public ?int $transmissionRepair = null;
    public ?int $brakeRepair = null;
    public ?int $steeringRepair = null;
    public ?int $suspensionRepair = null;
    public ?int $conditionerRepair = null;
    public ?int $lockRepair = null;
    public ?int $engineRepair = null;
    public ?int $exhaustRepair = null;
    public ?int $buyingHelp = null;
    public ?int $roadsideHelp = null;
    public ?int $painting = null;
    public ?int $reEquipment = null;
    public ?int $windowTinting = null;
    public ?int $electricalRepair = null;
    public ?int $glassRepair = null;
    public ?int $washAndCare = null;

    public ?int $transportType = null;
    public ?int $purpose = null;
    public ?int $rentType = null;
    public ?int $minimumRentalPeriod = null;
    public ?int $trailerType = null;
    public ?int $height = null;
    public ?int $width = null;
    public ?int $length = null;
    public ?int $carryingCapacity = null;
    public ?int $maximumPermittedWeight = null;
    public ?int $pledge = null;
    public ?int $pledgeAmount = null;
    public ?int $commission = null;
    public ?int $commissionAmount = null;
    public ?int $buyout = null;
    public ?int $delivery = null;
    public ?int $rentPurpose = null;
    public ?int $extraTaxi = null;
    public ?int $extraSelf = null;

    public ?int $adType = null;
    public ?int $condition = null;
    public ?int $title = null;
    public ?int $description = null;
    public ?int $price = null;
    public ?int $photoSourceFolder = null;
    public ?int $subFolderName = null;
    public ?int $imagesRaw = null;
    public ?int $videoURL = null;
    public ?int $urlAd = null;
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
    
    public ?int $unloadingStatus = null;
    public ?int $unloadingAvitoStatus = null;
    public ?int $unloadingMessages = null;
    public ?int $unloadingDateStart = null;
    public ?int $unloadingDateEnd = null;
    public ?int $unloadingAvitoId = null;
    public ?int $unloadingUrl = null;
    public ?int $unloadingDateInfo = null;

    public ?int $statisticViews = null;
    public ?int $statisticMessage = null;
    public ?int $statisticInfo = null;
    public ?int $statisticFav = null;
    public ?int $statisticViewsDay = null;
    public ?int $statisticMessageDay = null;
    public ?int $statisticInfoDay = null;
    public ?int $statisticFavDay = null;
    public ?int $statisticViewsWeek = null;
    public ?int $statisticMessageWeek = null;
    public ?int $statisticInfoWeek = null;
    public ?int $statisticFavWeek = null;
    public ?int $statisticViewsMonth = null;
    public ?int $statisticMessageMonth = null;
    public ?int $statisticInfoMonth = null;
    public ?int $statisticFavMonth = null;
    
    public ?int $ozonOfferId = null;
    public ?int $ozonPrice = null;
    public ?int $ozonOldPrice = null;
    public ?int $ozonPremiumPrice = null;
    public ?int $ozonWarehouseName = null;
    public ?int $ozonInstock = null;
    public ?int $ozonWarehouseName2 = null;
    public ?int $ozonInstock2 = null;
    public ?int $ozonWarehouseName3 = null;
    public ?int $ozonInstock3 = null;
    public ?int $ozonWarehouseName4 = null;
    public ?int $ozonInstock4 = null;
    public ?int $ozonWarehouseName5 = null;
    public ?int $ozonInstock5 = null;
    public ?int $ozonWarehouseName6 = null;
    public ?int $ozonInstock6 = null;
    public ?int $ozonWarehouseName7 = null;
    public ?int $ozonInstock7 = null;
    public ?int $ozonWarehouseName8 = null;
    public ?int $ozonInstock8 = null;
    public ?int $ozonWarehouseName9 = null;
    public ?int $ozonInstock9 = null;
    public ?int $ozonWarehouseName10 = null;
    public ?int $ozonInstock10 = null;

    public function __construct(array $headers)
    {
        /**
         * @var int $colNum
         * @var string $header
         */
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
                case "видуслуги":
                    $this->goodsType = $colNum;
                    break;
                case "видобъявления":
                    $this->adType = $colNum;
                    break;
                case "видверхнейодежды":
                    $this->apparelType = $colNum;
                    break;
                    
                case "видработ":
                    $this->workTypes = $colNum;
                    break;
                case "теплыйбокс":
                    $this->extraBox = $colNum;
                    break;
                case "кузовнойремонт":
                    $this->bodyRepair = $colNum;
                    break;
                case "опытработывгодах":
                    $this->workExperience = $colNum;
                    break;
                case "гарантия.есть/нет":
                    $this->guarantee = $colNum;
                    break;
                case "автосервисысамообслуживания":
                    $this->selfService = $colNum;
                    break;
                case "диагностикаавто":
                    $this->diagnostics = $colNum;
                    break;
                case "шиномонтажиремонтдисков":
                    $this->wheelService = $colNum;
                    break;
                case "установкадопоборудования":
                    $this->additionalEquipment = $colNum;
                    break;
                case "тюнингавто":
                    $this->tuning = $colNum;
                    break;
                case "техническоеобслуживаниеавто":
                    $this->maintenance = $colNum;
                    break;
                case "ремонттрансмиссии":
                    $this->transmissionRepair = $colNum;
                    break;
                case "ремонттормознойсистемы":
                    $this->brakeRepair = $colNum;
                    break;
                case "ремонтрулевогоуправления":
                    $this->steeringRepair = $colNum;
                    break;
                case "ремонтподвески":
                    $this->suspensionRepair = $colNum;
                    break;
                case "ремонткондиционеровиотопленияавто":
                    $this->conditionerRepair = $colNum;
                    break;
                case "ремонтзамковиключейавто":
                    $this->lockRepair = $colNum;
                    break;
                case "ремонтдвигателя":
                    $this->engineRepair = $colNum;
                    break;
                case "ремонтвыхлопнойсистемы":
                    $this->exhaustRepair = $colNum;
                    break;
                case "помощьприпокупкеавто":
                    $this->buyingHelp = $colNum;
                    break;
                case "помощьнадороге":
                    $this->roadsideHelp = $colNum;
                    break;
                case "покраскаавто":
                    $this->painting = $colNum;
                    break;
                case "переоборудованиетранспорт":
                    $this->reEquipment = $colNum;
                    break;
                case "тонировкаиоклейкаавто":
                    $this->windowTinting = $colNum;
                    break;
                case "ремонтэлектрооборудования":
                    $this->electricalRepair = $colNum;
                    break;
                case "ремонтавтостёкол":
                    $this->glassRepair = $colNum;
                    break;
                case "мойкаиуходзаавто":
                    $this->washAndCare = $colNum;
                    break;
                    
                case "типтранспорта":
                    $this->transportType = $colNum;
                    break;
                case "назначение":
                    $this->purpose = $colNum;
                    break;
                case "типаренды":
                    $this->rentType = $colNum;
                    break;
                case "минимальныйсрокарендывднях":
                    $this->minimumRentalPeriod = $colNum;
                    break;
                case "типприцепа":
                    $this->trailerType = $colNum;
                    break;
                case "высотавметрах":
                    $this->height = $colNum;
                    break;
                case "ширинавметрах":
                    $this->width = $colNum;
                    break;
                case "длиннавметрах":
                    $this->length = $colNum;
                    break;
                case "грузоподъемностьв(кг)":
                case "грузоподъемностьвкг.целоечисло.":
                    $this->carryingCapacity = $colNum;
                    break;
                case "максимальнаяразрешеннаямасса(кг)":
                case "максимальнаяразрешеннаямассавкг.целоечисло.":
                    $this->maximumPermittedWeight = $colNum;
                    break;
                case "залог":
                    $this->pledge = $colNum;
                    break;
                case "размерзалогаврублях":
                    $this->pledgeAmount = $colNum;
                    break;
                case "комиссия":
                    $this->commission = $colNum;
                    break;
                case "размеркомиссиив%":
                    $this->commissionAmount = $colNum;
                    break;
                case "возможностьвыкупитьавто":
                case "возможностьвыкупитьавто.":
                    $this->buyout = $colNum;
                    break;
                case "доставка":
                    $this->delivery = $colNum;
                    break;
                case "цельаренды":
                    $this->rentPurpose = $colNum;
                    break;
                case "дополнительнодля\"автоподтакси\"":
                    $this->extraTaxi = $colNum;
                    break;
                case "дополнительнодля\"автодляличногопользования\"":
                    $this->extraSelf = $colNum;
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
                case "ссылканаобъявление":
                    $this->urlAd = $colNum;
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
                case "улицарайонметро":
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
                case "улицарайонметро2":
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
                case "улицарайонметро3":
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
                case "улицарайонметро4":
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
                case "улицарайонметро5":
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
                case "улицарайонметро6":
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
                case "улицарайонметро7":
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
                case "улицарайонметро8":
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
                case "улицарайонметро9":
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
                case "улицарайонметро10":
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
                case "тип":
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
                case "подтипуслугивидодеждытипстройматтипмотоцикла":
                case "подтипуслуги":
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
                case "№объявлениядлясвязки":
                    $this->avitoManualID = $colNum;
                    break;
                case "номеробъявления":
                    $this->unloadingAvitoId = $colNum;
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
                case "статусвыгрузки":
                    $this->unloadingStatus = $colNum;
                    break;
                case "статуснаavito":
                    $this->unloadingAvitoStatus = $colNum;
                    break;
                case "сообщенияособытиях":
                    $this->unloadingMessages = $colNum;
                    break;
                case "точнаядатаивремяподачи":
                    $this->unloadingDateStart = $colNum;
                    break;
                case "точнаядатаивремяокончания":
                    $this->unloadingDateEnd = $colNum;
                    break;
                case "ссылканаобьявление":
                    $this->unloadingUrl = $colNum;
                    break;
                case "информацияоботчете":
                    $this->unloadingDateInfo = $colNum;
                    break;
                case "👁":
                    $this->statisticViews = $colNum;
                    break;
                case "💬":
                    $this->statisticMessage = $colNum;
                    break;
                case "📊":
                    $this->statisticInfo = $colNum;
                    break;
                case "💚":
                    $this->statisticFav = $colNum;
                    break;
                case "👁м":
                    $this->statisticViewsMonth = $colNum;
                    break;
                case "💬м":
                    $this->statisticMessageMonth = $colNum;
                    break;
                case "📊м":
                    $this->statisticInfoMonth = $colNum;
                    break;
                case "💚м":
                    $this->statisticFavMonth = $colNum;
                    break;
                case "👁н":
                    $this->statisticViewsWeek = $colNum;
                    break;
                case "💬н":
                    $this->statisticMessageWeek = $colNum;
                    break;
                case "📊н":
                    $this->statisticInfoWeek = $colNum;
                    break;
                case "💚н":
                    $this->statisticFavWeek = $colNum;
                    break;
                case "👁д":
                    $this->statisticViewsDay = $colNum;
                    break;
                case "💬д":
                    $this->statisticMessageDay = $colNum;
                    break;
                case "📊д":
                    $this->statisticInfoDay = $colNum;
                    break;
                case "💚д":
                    $this->statisticFavDay = $colNum;
                    break;
                case "артикулozon":
                    $this->ozonOfferId = $colNum;
                    break;
                case "ценасоскидкой":
                    $this->ozonPrice = $colNum;
                    break;
                case "ценабезскидки":
                    $this->ozonOldPrice = $colNum;
                    break;
                case "ценаozonpremium":
                    $this->ozonPremiumPrice = $colNum;
                    break;
                case "названиесклада1":
                    $this->ozonWarehouseName = $colNum;
                    break;
                case "остатокнаскладе1":
                    $this->ozonInstock = $colNum;
                    break;
                case "названиесклада2":
                    $this->ozonWarehouseName2 = $colNum;
                    break;
                case "остатокнаскладе2":
                    $this->ozonInstock2 = $colNum;
                    break;
                case "названиесклада3":
                    $this->ozonWarehouseName3 = $colNum;
                    break;
                case "остатокнаскладе3":
                    $this->ozonInstock3 = $colNum;
                    break;
                case "названиесклада4":
                    $this->ozonWarehouseName4 = $colNum;
                    break;
                case "остатокнаскладе4":
                    $this->ozonInstock4 = $colNum;
                    break;
                case "названиесклада5":
                    $this->ozonWarehouseName5 = $colNum;
                    break;
                case "остатокнаскладе5":
                    $this->ozonInstock5 = $colNum;
                    break;
                case "названиесклада6":
                    $this->ozonWarehouseName6 = $colNum;
                    break;
                case "остатокнаскладе6":
                    $this->ozonInstock6 = $colNum;
                    break;
                case "названиесклада7":
                    $this->ozonWarehouseName7 = $colNum;
                    break;
                case "остатокнаскладе7":
                    $this->ozonInstock7 = $colNum;
                    break;
                case "названиесклада8":
                    $this->ozonWarehouseName8 = $colNum;
                    break;
                case "остатокнаскладе8":
                    $this->ozonInstock8 = $colNum;
                    break;
                case "названиесклада9":
                    $this->ozonWarehouseName9 = $colNum;
                    break;
                case "остатокнаскладе9":
                    $this->ozonInstock9 = $colNum;
                    break;
                case "названиесклада10":
                    $this->ozonWarehouseName10 = $colNum;
                    break;
                case "остатокнаскладе10":
                    $this->ozonInstock10 = $colNum;
                    break;
            }
        }
    }
}

