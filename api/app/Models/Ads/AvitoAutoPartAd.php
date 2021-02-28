<?php

namespace App\Models\Ads;

use App\Models\TableHeader;

class AvitoAutoPartAd extends AdBase
{
    protected ?string $goodsType = null;
    protected ?string $autoPartOem = null;
    protected ?string $brand = null;
    protected ?string $rimDiameter = null;
    protected ?string $tireType = null;
    protected ?string $wheelAxle = null;
    protected ?string $rimType = null;
    protected ?string $tireSectionWidth = null;
    protected ?string $tireAspectRatio = null;
    protected ?string $rimBolts = null;
    protected ?string $rimBoltsDiameter = null;
    protected array $displayAreas = [];

    private function mapTypeId(?string $goodsType) : ?string
    {
        if(is_null($goodsType))
        {
            return null;
        }

        $kindParts = explode("/", $goodsType);
        $lastKind = trim($kindParts[count($kindParts) - 1]);

        switch($lastKind)
        {
            case "Автосвет":
                return "11-618";
            case "Автомобиль на запчасти":
                return "19-2855";
            case "Аккумуляторы":
                return "11-619";
            case "Блок цилиндров, головка, картер":
                return "116-827";
            case "Вакуумная система":
                return "16-828";
            case "Генераторы, стартеры":
                return "16-829";
            case "Двигатель в сборе":
                return "16-830";
            case "Катушка зажигания, свечи, электрика":
                return "16-831";
            case "Клапанная крышка":
                return "16-832";
            case "Коленвал, маховик":
                return "16-833";
            case "Коллекторы":
                return "16-834";
            case "Крепление двигателя":
                return "16-835";
            case "Масляный насос, система смазки":
                return "16-836";
            case "Патрубки вентиляции":
                return "16-837";
            case "Поршни, шатуны, кольца":
                return "16-838";
            case "Приводные ремни, натяжители":
                return "16-839";
            case "Прокладки и ремкомплекты":
                return "16-840";
            case "Ремни, цепи, элементы ГРМ":
                return "16-841";
            case "Турбины, компрессоры":
                return "16-842";
            case "Электродвигатели и компоненты":
                return "16-843";
            case "Запчасти для ТО":
                return "11-621";
            case "Балки, лонжероны":
                return "16-805";
            case "Бамперы":
                return "16-806";
            case "Брызговики":
                return "16-807";
            case "Двери":
                return "16-808";
            case "Заглушки":
                return "16-809";
            case "Замки":
                return "16-810";
            case "Защита":
                return "16-811";
            case "Зеркала":
                return "16-812";
            case "Кабина":
                return "16-813";
            case "Капот":
                return "16-814";
            case "Крепления":
                return "16-815";
            case "Крылья":
                return "16-816";
            case "Крыша":
                return "16-817";
            case "Крышка, дверь багажника":
                return "16-818";
            case "Кузов по частям":
                return "16-819";
            case "Кузов целиком":
                return "16-820";
            case "Лючок бензобака":
                return "16-821";
            case "Молдинги, накладки":
                return "16-822";
            case "Пороги":
                return "16-823";
            case "Рама":
                return "16-824";
            case "Решетка радиатора":
                return "16-825";
            case "Стойка кузова":
                return "16-826";
            case "Подвеска":
                return "11-623";
            case "Рулевое управление":
                return "11-624";
            case "Салон":
                return "11-625";
            case "Система охлаждения":
                return "16-521";
            case "Стекла":
                return "11-626";
            case "Топливная и выхлопная системы":
                return "11-627";
            case "Тормозная система":
                return "11-628";
            case "Трансмиссия и привод":
                return "11-629";
            case "Электрооборудование":
                return "11-630";
            case "Для мототехники":
                return "6-401";
            case "Для спецтехники":
                return "6-406";
            case "Для водного транспорта":
                return "6-411";
            case "Аксессуары":
                return "4-943";
            case "GPS-навигаторы":
                return "21";
            case "Автокосметика и автохимия":
                return "4-942";
            case "Аудио- и видеотехника":
                return "20";
            case "Багажники и фаркопы":
                return "4-964";
            case "Инструменты":
                return "4-963";
            case "Прицепы":
                return "4-965";
            case "Автосигнализации":
                return "11-631";
            case "Иммобилайзеры":
                return "11-632";
            case "Механические блокираторы":
                return "11-633";
            case "Спутниковые системы":
                return "11-634";
            case "Тюнинг":
                return "22";
            case "Шины":
                return "10-048";
            case "Мотошины":
                return "10-047";
            case "Диски":
                return "10-046";
            case "Колёса":
                return "10-045";
            case "Колпаки":
                return "10-044";
            case "Экипировка":
                return "6-416";
            default:
                return null;
        }
    }

    private function generateDisplayAreaAvitoTags(array $displayAreas) : ?string
    {
        if(count($displayAreas) == 0 || (count($displayAreas) == 1 && $displayAreas[0] == ""))
        {
            return "";
        }

        $displayAreaTags = PHP_EOL;
        foreach($displayAreas as $displayArea)
        {
            $displayAreaTags.= "\t\t\t<Area>" . $displayArea . "</Area>".PHP_EOL;
        }

        return $displayAreaTags."\t\t";
    }

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        parent::__construct($row, $propertyColumns);

        $this->goodsType = isset($row[$propertyColumns->goodsType])
            ? htmlspecialchars($row[$propertyColumns->goodsType])
            : null;
        $this->autoPartOem = isset($row[$propertyColumns->autoPartOem])
            ? htmlspecialchars($row[$propertyColumns->autoPartOem])
            : null;
        $this->brand = isset($row[$propertyColumns->brand])
            ? htmlspecialchars($row[$propertyColumns->brand])
            : null;
        $this->rimDiameter = isset($row[$propertyColumns->rimDiameter])
            ? htmlspecialchars($row[$propertyColumns->rimDiameter])
            : null;
        $this->tireType = isset($row[$propertyColumns->tireType])
            ? htmlspecialchars($row[$propertyColumns->tireType])
            : null;
        $this->wheelAxle = isset($row[$propertyColumns->wheelAxle])
            ? htmlspecialchars($row[$propertyColumns->wheelAxle])
            : null;
        $this->rimType = isset($row[$propertyColumns->rimType])
            ? htmlspecialchars($row[$propertyColumns->rimType])
            : null;
        $this->tireSectionWidth = isset($row[$propertyColumns->tireSectionWidth])
            ? htmlspecialchars($row[$propertyColumns->tireSectionWidth])
            : null;
        $this->tireAspectRatio = isset($row[$propertyColumns->tireAspectRatio])
            ? htmlspecialchars($row[$propertyColumns->tireAspectRatio])
            : null;
        $this->rimBolts = isset($row[$propertyColumns->rimBolts])
            ? htmlspecialchars($row[$propertyColumns->rimBolts])
            : null;
        $this->rimBoltsDiameter = isset($row[$propertyColumns->rimBoltsDiameter])
            ? htmlspecialchars($row[$propertyColumns->rimBoltsDiameter])
            : null;
        $this->displayAreas = isset($row[$propertyColumns->displayAreas])
            ? explode(PHP_EOL, $row[$propertyColumns->displayAreas])
            : [];
    }

    public function toAvitoXml() : string
    {
        $defaultTags = $this->generateDefaultXML();
        $resultXml = $this->addTagIfPropertySet($this->mapTypeId($this->goodsType), "TypeId");
        $resultXml.= $this->addTagIfPropertySet($this->autoPartOem, "OEM");
        $resultXml.= $this->addTagIfPropertySet($this->brand, "Brand");
        $resultXml.= $this->addTagIfPropertySet($this->rimDiameter, "RimDiameter");
        $resultXml.= $this->addTagIfPropertySet($this->tireType, "TireType");
        $resultXml.= $this->addTagIfPropertySet($this->wheelAxle, "WheelAxle");
        $resultXml.= $this->addTagIfPropertySet($this->rimType, "RimType");
        $resultXml.= $this->addTagIfPropertySet($this->tireSectionWidth, "TireSectionWidth");
        $resultXml.= $this->addTagIfPropertySet($this->tireAspectRatio, "TireAspectRatio");
        $resultXml.= $this->addTagIfPropertySet($this->rimBolts, "RimBolts");
        $resultXml.= $this->addTagIfPropertySet($this->rimBoltsDiameter, "RimBoltsDiameter");
        $resultXml.= $this->addTagIfPropertySet(
            $this->generateDisplayAreaAvitoTags($this->displayAreas), "DisplayAreas");

        return <<<AVITOXML
    <Ad>
$defaultTags
$resultXml
    </Ad>
AVITOXML;
    }
}
