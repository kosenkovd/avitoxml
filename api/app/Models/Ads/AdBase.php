<?

namespace App\Models\Ads;

use App\Models\TableHeader;
use DateTime;
use DateTimeZone;

abstract class AdBase
{
    protected $id;
    protected $dateBegin;
    protected $managerName;
    protected $contactPhone;
    protected $category;
    protected $adType;
    protected $condition;
    protected $title;
    protected $description;
    protected $price;
    protected $images;
    protected $videoURL;
    protected $address;
    protected $avitoId;
    protected $adStatus;

    public function __construct(array $row, TableHeader $propertyColumns)
    {
        $this->id = htmlspecialchars($row[$propertyColumns->ID]);
        if(isset($row[$propertyColumns->dateCreated]))
        {
            if(strpos($row[$propertyColumns->dateCreated], ":"))
            {
                $date = DateTime::createFromFormat(
                    'd.m.Y H:i', $row[$propertyColumns->dateCreated], new DateTimeZone("Europe/Moscow"));
            }
            else
            {
                $date = DateTime::createFromFormat(
                    'd.m.Y', $row[$propertyColumns->dateCreated], new DateTimeZone("Europe/Moscow"));
            }
            if($date !== false)
            {
                $this->dateBegin = $date->format('Y-m-d\TH:i:sP');
            }
        }
        else
        {
            $this->dateBegin = null;
        }
        $this->managerName = isset($row[$propertyColumns->manager])
            ? htmlspecialchars($row[$propertyColumns->manager])
            : null;
        $this->contactPhone = isset($row[$propertyColumns->phone])
            ? htmlspecialchars($row[$propertyColumns->phone])
            : null;
        $this->category = htmlspecialchars($row[$propertyColumns->category]);
        $this->adType = isset($row[$propertyColumns->adType])
            ? htmlspecialchars($row[$propertyColumns->adType])
            : null;
        $this->condition = isset($row[$propertyColumns->condition])
            ? htmlspecialchars($row[$propertyColumns->condition])
            : null;;
        $this->title = isset($row[$propertyColumns->title])
            ? htmlspecialchars($row[$propertyColumns->title])
            : null;
        if(isset($row[$propertyColumns->description]))
        {
            $this->description = preg_replace(
                '#<br\s+/>#', '<br/>', nl2br($row[$propertyColumns->description], true));
        }
        else
        {
            $this->description = null;
        }
        $this->price = isset($row[$propertyColumns->price])
            ? htmlspecialchars($row[$propertyColumns->price])
            : null;
        $this->images = isset($row[$propertyColumns->imagesRaw])
            ? explode(PHP_EOL, $row[$propertyColumns->imagesRaw])
            : [];
        $this->videoURL = isset($row[$propertyColumns->videoURL])
            ? htmlspecialchars($row[$propertyColumns->videoURL])
            : null;

        $address = [];
        if (isset($row[$propertyColumns->address]) && $row[$propertyColumns->address] != '')
        {
            $address[] = htmlspecialchars($row[$propertyColumns->address]);
        }
        if (isset($row[$propertyColumns->region]) && $row[$propertyColumns->region] != '')
        {
            $address[] = htmlspecialchars($row[$propertyColumns->region]);
        }
        if (isset($row[$propertyColumns->city]) && $row[$propertyColumns->city] != '')
        {
            $address[] = htmlspecialchars($row[$propertyColumns->city]);
        }
        if (isset($row[$propertyColumns->area]) && $row[$propertyColumns->area] != '')
        {
            $address[] = htmlspecialchars($row[$propertyColumns->area]);
        }
        if (isset($row[$propertyColumns->street]) && $row[$propertyColumns->street] != '')
        {
            $address[] = htmlspecialchars($row[$propertyColumns->street]);
        }
        if (isset($row[$propertyColumns->house]) && $row[$propertyColumns->house] != '')
        {
            $address[] = htmlspecialchars($row[$propertyColumns->house]);
        }
        $this->address = join(', ', $address);

        $this->avitoId = isset($row[$propertyColumns->avitoManualID])
            ? htmlspecialchars($row[$propertyColumns->avitoManualID])
            : null;
        $this->adStatus = isset($row[$propertyColumns->paidControl])
            ? htmlspecialchars($row[$propertyColumns->paidControl])
            : null;
    }

    /**
     * Generates default XML content.
     *
     * @return string default XML tags.
     */
    protected function generateDefaultXML(): string
    {
        $imageTags = $this->generateImageAvitoTags($this->images);

        if(strcmp(strtolower($this->condition), "неприменимо") === 0)
        {
            $this->condition = "inapplicable";
        }

        return <<<AVITOXML
        <Id>$this->id</Id>
        <DateBegin>$this->dateBegin</DateBegin>
        <ManagerName>$this->managerName</ManagerName>
        <ContactPhone>$this->contactPhone</ContactPhone>
        <Address>$this->address</Address>
        <Category>$this->category</Category>
        <AdType>$this->adType</AdType>
        <Condition>$this->condition</Condition>
        <Title>$this->title</Title>
        <Description><![CDATA[$this->description]]></Description>
        <Price>$this->price</Price>
        <Images>$imageTags</Images>
        <VideoURL>$this->videoURL</VideoURL>
        <AvitoId>$this->avitoId</AvitoId>
        <AdStatus>$this->adStatus</AdStatus>
AVITOXML;
    }

    protected function generateImageAvitoTags(array $images)
    {
        if(count($images) == 0 || (count($images) == 1 && $images[0] == ""))
        {
            return "";
        }
        $imageTags = PHP_EOL;
        foreach($images as $image)
        {
            $imageTags.= "\t\t\t<Image url=\"" . str_replace('&', '&amp;', $image) . '"/>'.PHP_EOL;
        }
        return $imageTags."\t\t";
    }

    public abstract function toAvitoXml() : string;
}
