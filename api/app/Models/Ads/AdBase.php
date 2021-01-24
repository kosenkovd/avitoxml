<?

namespace App\Models\Ads;

use App\Models\TableHeader;
use DateTime;
use DateTimeZone;

abstract class AdBase
{
    protected ?string $id = null;
    protected ?string $dateBegin = null;
    protected ?string $dateEnd = null;
    protected ?string $managerName = null;
    protected ?string $contactPhone = null;
    protected ?string $category = null;
    protected ?string $adType = null;
    protected ?string $condition = null;
    protected ?string $title = null;
    protected ?string $description = null;
    protected ?string $price = null;
    protected array $images;
    protected ?string $videoURL = null;
    protected ?string $address = null;
    protected ?string $avitoId = null;
    protected ?string $adStatus = null;
    protected ?string $placementType = null;
    protected ?string $messages = null;

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

        if(isset($row[$propertyColumns->dateEnd]))
        {
            if(strpos($row[$propertyColumns->dateEnd], ":"))
            {
                $date = DateTime::createFromFormat(
                    'd.m.Y H:i', $row[$propertyColumns->dateEnd], new DateTimeZone("Europe/Moscow"));
            }
            else
            {
                $date = DateTime::createFromFormat(
                    'd.m.Y', $row[$propertyColumns->dateEnd], new DateTimeZone("Europe/Moscow"));
            }
            if($date !== false)
            {
                $this->dateEnd = $date->format('Y-m-d\TH:i:sP');
            }
        }
        else
        {
            $this->dateEnd = null;
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
            $this->description = str_replace("\n\r", "\n", $row[$propertyColumns->description]);
            $this->description = str_replace("\n", "<br/>", $this->description);
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

        if(isset($row[$propertyColumns->placementType]))
        {
            switch ($row[$propertyColumns->placementType])
            {
                case "Пакет":
                    $this->placementType = "Package";
                    break;
                case "Пакет или кошелек":
                    $this->placementType = "PackageSingle";
                    break;
                case "Кошелек":
                    $this->placementType = "Single";
                    break;
                default:
                    $this->placementType = null;
            }
        }

        $this->messages = isset($row[$propertyColumns->messages])
            ? htmlspecialchars($row[$propertyColumns->messages])
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

        $resultXml = $this->addTagIfPropertySet($this->id, "Id");
        $resultXml.= $this->addTagIfPropertySet($this->dateBegin, "DateBegin");
        $resultXml.= $this->addTagIfPropertySet($this->dateEnd, "DateEnd");
        $resultXml.= $this->addTagIfPropertySet($this->managerName, "ManagerName");
        $resultXml.= $this->addTagIfPropertySet($this->contactPhone, "ContactPhone");
        $resultXml.= $this->addTagIfPropertySet($this->address, "Address");
        $resultXml.= $this->addTagIfPropertySet($this->category, "Category");
        $resultXml.= $this->addTagIfPropertySet($this->adType, "AdType");
        $resultXml.= $this->addTagIfPropertySet($this->condition, "Condition");
        $resultXml.= $this->addTagIfPropertySet($this->title, "Title");
        $resultXml.= $this->addTagIfPropertySet("<![CDATA[$this->description]]>", "Description");
        $resultXml.= $this->addTagIfPropertySet($this->price, "Price");
        $resultXml.= $this->addTagIfPropertySet($imageTags, "Images");
        $resultXml.= $this->addTagIfPropertySet($this->videoURL, "VideoURL");
        $resultXml.= $this->addTagIfPropertySet($this->avitoId, "AvitoId");
        $resultXml.= $this->addTagIfPropertySet($this->adStatus, "AdStatus");
        $resultXml.= $this->addTagIfPropertySet($this->placementType, "ListingFee");
        $resultXml.= $this->addTagIfPropertySet($this->messages, "AllowEmail");

        return $resultXml;
    }

    /**
     * Create tag if property is not null or empty.
     *
     * @param $property
     * @param string $tagName
     * @return string
     */
    protected function addTagIfPropertySet($property, string $tagName) : string
    {
        if($property == null || trim($property) == "")
        {
            return "";
        }

        return "
        <$tagName>$property</$tagName>";
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
