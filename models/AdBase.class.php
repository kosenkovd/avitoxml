<?

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
    
    public function __construct(array $row, TableHeader $propertyColumns)
    {
        $this->id = htmlspecialchars($row[$propertyColumns->ID]);
        $dateRaw = date_create_from_format('d.m.Y H:i', $row[$propertyColumns->dateCreated]);
        $this->dateBegin = date_format($dateRaw, 'Y-m-d\TH:i:sP');
        $this->managerName = htmlspecialchars($row[$propertyColumns->manager]);
        $this->contactPhone = htmlspecialchars($row[$propertyColumns->phone]);
        $this->category = htmlspecialchars($row[$propertyColumns->category]);
        $this->adType = htmlspecialchars($row[$propertyColumns->adType]);
        $this->condition = htmlspecialchars($row[$propertyColumns->condition]);
        $this->title = htmlspecialchars($row[$propertyColumns->title]);
        $this->description = nl2br($row[$propertyColumns->description], true);
        $this->price = htmlspecialchars($row[$propertyColumns->price]);
        $this->images = explode(PHP_EOL, $row[$propertyColumns->imagesRaw]);
        $this->videoURL = htmlspecialchars($row[$propertyColumns->videoURL]);
                    
        $address = [];
        if ($row[$propertyColumns->city] != '') $address[] = htmlspecialchars($row[$propertyColumns->city]);
        if ($row[$propertyColumns->area] != '') $address[] = htmlspecialchars($row[$propertyColumns->area]);
        if ($row[$propertyColumns->street] != '') $address[] = htmlspecialchars($row[$propertyColumns->street]);
        if ($row[$propertyColumns->house] != '') $address[] = htmlspecialchars($row[$propertyColumns->house]);
        $this->address = join(', ', $address);
    }
    
    abstract public function toAvitoXml() : string;
    
    protected function generateImageAvitoTags(array $images)
    {
        if(count($images) == 0)
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
}