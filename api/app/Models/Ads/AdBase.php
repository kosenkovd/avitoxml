<?
    
    namespace App\Models\Ads;
    
    use App\Models\TableHeader;
    use DateTime;
    use DateTimeZone;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Log;
    
    abstract class AdBase {
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
        protected ?DateTimeZone $timezone = null;
        protected ?string $contactsType;
        
        public function __construct(array $row, TableHeader $propertyColumns)
        {
            $this->id = htmlspecialchars($row[$propertyColumns->ID]);
            
            $this->setTimezone($row, $propertyColumns);
            
            if (isset($row[$propertyColumns->dateCreated])) {
                $dateRaw = $row[$propertyColumns->dateCreated];
                if (!strpos($dateRaw, ":")) {
                    $dateRaw .= ' 12:00';
                }
                
                try {
                    $date = Carbon::createFromTimeString($dateRaw, $this->timezone);
                    $this->dateBegin = $date->format('Y-m-d\TH:i:sP');
                } catch (\Exception $exception) {
                    Log::error("Error on '".$dateRaw."'");
                }
            } else {
                $this->dateBegin = null;
            }
            
            if (isset($row[$propertyColumns->dateEnd])) {
                $dateRaw = $row[$propertyColumns->dateEnd];
                if (!strpos($dateRaw, ":")) {
                    $dateRaw .= ' 12:00';
                }
                
                try {
                    $date = Carbon::createFromTimeString($dateRaw, $this->timezone);
                    $this->dateEnd = $date->format('Y-m-d\TH:i:sP');
                } catch (\Exception $exception) {
                    Log::error("Error on '".$dateRaw."'");
                }
            } else {
                $this->dateEnd = null;
            }
            
            $this->managerName = isset($row[$propertyColumns->manager])
                ? htmlspecialchars($row[$propertyColumns->manager])
                : null;
            $this->contactPhone = isset($row[$propertyColumns->phone])
                ? htmlspecialchars($row[$propertyColumns->phone])
                : null;
            $this->contactsType = isset($row[$propertyColumns->contactsType])
                ? htmlspecialchars($row[$propertyColumns->contactsType])
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
            if (isset($row[$propertyColumns->description])) {
                $this->description = str_replace("\n\r", "\n", $row[$propertyColumns->description]);
                $this->description = str_replace("\n", "<br/>", $this->description);
            } else {
                $this->description = null;
            }
            $this->price = isset($row[$propertyColumns->price])
                ? htmlspecialchars($row[$propertyColumns->price])
                : null;
            $this->images = isset($row[$propertyColumns->imagesRaw])
                ? explode("\n", $row[$propertyColumns->imagesRaw]) // TODO change to linux version
                : [];
            $this->videoURL = isset($row[$propertyColumns->videoURL])
                ? htmlspecialchars($row[$propertyColumns->videoURL])
                : null;
            
            $this->address = $this->getFullAddress($row, $propertyColumns);
            
            $this->avitoId = isset($row[$propertyColumns->avitoManualID])
                ? htmlspecialchars($row[$propertyColumns->avitoManualID])
                : null;
            $this->adStatus = isset($row[$propertyColumns->paidControl])
                ? htmlspecialchars($row[$propertyColumns->paidControl])
                : null;
            
            if (isset($row[$propertyColumns->placementType])) {
                switch ($row[$propertyColumns->placementType]) {
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
        
        public abstract function toAvitoXml(): string;
        
        protected function getFullAddress($row, TableHeader $propertyColumns, int $number = null): string
        {
            $addressKey = 'address'.$number;
            $regionKey = 'region'.$number;
            $cityKey = 'city'.$number;
            $areaKey = 'area'.$number;
            $streetKey = 'street'.$number;
            $houseKey = 'house'.$number;
            
            $result = [];
            if (isset($row[$propertyColumns->$addressKey]) && $row[$propertyColumns->$addressKey] != '') {
                $result[] = htmlspecialchars($row[$propertyColumns->$addressKey]);
            }
            if (isset($row[$propertyColumns->$regionKey]) && $row[$propertyColumns->$regionKey] != '') {
                $result[] = htmlspecialchars($row[$propertyColumns->$regionKey]);
            }
            if (isset($row[$propertyColumns->$cityKey]) && $row[$propertyColumns->$cityKey] != '') {
                $result[] = htmlspecialchars($row[$propertyColumns->$cityKey]);
            }
            if (isset($row[$propertyColumns->$areaKey]) && $row[$propertyColumns->$areaKey] != '') {
                $result[] = htmlspecialchars($row[$propertyColumns->$areaKey]);
            }
            if (isset($row[$propertyColumns->$streetKey]) && $row[$propertyColumns->$streetKey] != '') {
                $result[] = htmlspecialchars($row[$propertyColumns->$streetKey]);
            }
            if (isset($row[$propertyColumns->$houseKey]) && $row[$propertyColumns->$houseKey] != '') {
                $result[] = htmlspecialchars($row[$propertyColumns->$houseKey]);
            }
            return join(', ', $result);
        }
        
        protected function isExistsInRow(array $row, ?int $column): bool
        {
            return !is_null($column) &&
                isset($row[$column]) &&
                (trim($row[$column]) != '');
        }
        
        /**
         * Create tag if property is not null or empty.
         *
         * @param $property
         * @param string $tagName
         * @return string
         */
        protected function addTagIfPropertySet(?string $property, string $tagName): string
        {
            if ($property == null || trim($property) == "") {
                return "";
            }
            
            return "
        <$tagName>$property</$tagName>";
        }
        
        protected function setTimezone(array $row, TableHeader $propertyColumns): void
        {
            if (!$this->isExistsInRow($row, $propertyColumns->timezone)) {
                $this->timezone = new DateTimeZone("Europe/Moscow");
                return;
            }
            
            $timezone = $row[$propertyColumns->timezone];
            
            // Lowering case and removing space characters to avoid errors in case of line break, etc.
            $timezone = mb_strtolower(preg_replace('/\s/i', "", $timezone));
            switch ($timezone) {
                case "калининградскаяобласть(-1мск)":
                    $this->timezone = new DateTimeZone("+0200");
                    break;
                case "московскоевремя":
                    $this->timezone = new DateTimeZone("Europe/Moscow");
                    break;
                case "самарскоевремя(+1чмск)":
                    $this->timezone = new DateTimeZone("+0400");
                    break;
                case "екатеринбургскоевремя(+2чмск)":
                    $this->timezone = new DateTimeZone("+0500");
                    break;
                case "омскоевремя(+3чмск)":
                    $this->timezone = new DateTimeZone("+0600");
                    break;
                case "красноярскоевремя(+4чмск)":
                    $this->timezone = new DateTimeZone("+0700");
                    break;
                case "иркутскоевремя(+5чмск)":
                    $this->timezone = new DateTimeZone("+0800");
                    break;
                case "якутскоевремя(+6чмск)":
                    $this->timezone = new DateTimeZone("+0900");
                    break;
                case "владивостокскоевремя(+7чмск)":
                    $this->timezone = new DateTimeZone("+1000");
                    break;
                case "магаданскоевремя(+8чмск)":
                    $this->timezone = new DateTimeZone("+1100");
                    break;
                case "камчатскоевремя(+9чмск)":
                    $this->timezone = new DateTimeZone("+1200");
                    break;
                default:
                    $this->timezone = new DateTimeZone("Europe/Moscow");
            }
        }
        
        protected function generateImageAvitoTags(array $images)
        {
            if (count($images) == 0 || (count($images) == 1 && $images[0] == "")) {
                return "";
            }
            $imageTags = PHP_EOL;
            foreach ($images as $image) {
                $image = trim($image);
                $imageTags .= "\t\t\t<Image url=\"".str_replace('&', '&amp;', $image).'"/>'.PHP_EOL;
            }
            return $imageTags."\t\t";
        }
        
        /**
         * Generates default XML content.
         *
         * @return string default XML tags.
         */
        protected function generateDefaultXML(): string
        {
            $imageTags = $this->generateImageAvitoTags($this->images);
            
            if (strcmp(strtolower($this->condition), "неприменимо") === 0) {
                $this->condition = "inapplicable";
            }
            
            $resultXml = $this->addTagIfPropertySet($this->id, "Id");
            $resultXml .= $this->addTagIfPropertySet($this->dateBegin, "DateBegin");
            $resultXml .= $this->addTagIfPropertySet($this->dateEnd, "DateEnd");
            $resultXml .= $this->addTagIfPropertySet($this->managerName, "ManagerName");
            $resultXml .= $this->addTagIfPropertySet($this->contactPhone, "ContactPhone");
            $resultXml .= $this->addTagIfPropertySet($this->address, "Address");
            $resultXml .= $this->addTagIfPropertySet($this->category, "Category");
            $resultXml .= $this->addTagIfPropertySet($this->adType, "AdType");
            $resultXml .= $this->addTagIfPropertySet($this->condition, "Condition");
            $resultXml .= $this->addTagIfPropertySet($this->title, "Title");
            $resultXml .= $this->addTagIfPropertySet("<![CDATA[$this->description]]>", "Description");
            $resultXml .= $this->addTagIfPropertySet($this->price, "Price");
            $resultXml .= $this->addTagIfPropertySet($imageTags, "Images");
            $resultXml .= $this->addTagIfPropertySet($this->videoURL, "VideoURL");
            $resultXml .= $this->addTagIfPropertySet($this->avitoId, "AvitoId");
            $resultXml .= $this->addTagIfPropertySet($this->adStatus, "AdStatus");
            $resultXml .= $this->addTagIfPropertySet($this->placementType, "ListingFee");
            $resultXml .= $this->addTagIfPropertySet($this->messages, "AllowEmail");
            
            return $resultXml;
        }
    }
