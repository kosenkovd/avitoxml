<?
    
    namespace App\Models\Ads;
    
    use App\Models\TableHeader;
    use DateTime;
    use DateTimeZone;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Log;
    
    abstract class AdBase {
        protected string $noticeChannel = 'notice';
        
        protected ?string $id = null;
        protected ?string $dateBegin = null;
        protected ?string $dateEnd = null;
        protected ?string $managerName = null;
        protected ?string $contactPhone = null;
        protected ?string $category = null;
        protected ?string $adType = null;
    
        protected array $workTypes = [];
        protected ?string $extraBox = null;
        protected array $bodyRepair = [];
        protected ?string $workExperience = null;
        protected ?string $guarantee = null;
        protected array $selfService = [];
        protected array $diagnostics = [];
        protected array $wheelService = [];
        protected array $additionalEquipment = [];
        protected array $tuning = [];
        protected array $maintenance = [];
        protected array $transmissionRepair = [];
        protected array $brakeRepair = [];
        protected array $steeringRepair = [];
        protected array $suspensionRepair = [];
        protected array $conditionerRepair = [];
        protected array $lockRepair = [];
        protected array $engineRepair = [];
        protected array $exhaustRepair = [];
        protected array $buyingHelp = [];
        protected array $roadsideHelp = [];
        protected array $painting = [];
        protected array $reEquipment = [];
        protected array $windowTinting = [];
        protected array $electricalRepair = [];
        protected array $glassRepair = [];
        protected array $washAndCare = [];
    
        protected ?string $transportType = null;
        protected ?string $purpose = null;
        protected ?string $rentType = null;
        protected ?string $minimumRentalPeriod = null;
        protected ?string $trailerType = null;
        protected ?string $height = null;
        protected ?string $width = null;
        protected ?string $length = null;
        protected ?string $carryingCapacity = null;
        protected ?string $maximumPermittedWeight = null;
        protected ?string $pledge = null;
        protected ?string $pledgeAmount = null;
        protected ?string $commission = null;
        protected ?string $commissionAmount = null;
        protected ?string $buyout = null;
        protected ?string $delivery = null;
        protected array $rentPurpose = [];
        protected array $extraTaxi = [];
        protected array $extraSelf = [];
        
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
        protected ?string $apparelType = null;
        protected ?string $messages = null;
        protected ?DateTimeZone $timezone = null;
        protected ?string $contactsType;
        
        public function __construct(array $row, TableHeader $propertyColumns)
        {
            $this->id = isset($row[$propertyColumns->ID])
                ? htmlspecialchars($row[$propertyColumns->ID])
                : null;
            
            $this->setTimezone($row, $propertyColumns);
            
            if (isset($row[$propertyColumns->dateCreated]) && trim($row[$propertyColumns->dateCreated]) != '') {
                $dateRaw = $row[$propertyColumns->dateCreated];
                
                if ($dateRaw === 'сразу') {
                    $this->dateBegin = Carbon::now($this->timezone)->format('Y-m-d\TH:i:sP');
                } else {
                    $dateRawFixed = $dateRaw;
                    if(!strpos($dateRawFixed, ":")) {
                        $dateRawFixed .= ' 12:00';
                    }
                    $dateRawFixed = preg_replace('/\./', '-', $dateRawFixed);
    
                    try {
                        $date = Carbon::createFromTimeString($dateRawFixed, $this->timezone);
                        $this->dateBegin = $date->format('Y-m-d\TH:i:sP');
                    } catch (\Exception $exception) {
                        Log::channel($this->noticeChannel)->notice("Notice on 'dateBegin' ".$dateRaw);
                    }
                }
            } else {
                $this->dateBegin = null;
            }
            
            if (isset($row[$propertyColumns->dateEnd]) && $row[$propertyColumns->dateEnd] != '') {
                $dateRaw = $row[$propertyColumns->dateEnd];
                $dateRawFixed = $dateRaw;
                if(!strpos($dateRawFixed, ":")) {
                    $dateRawFixed .= ' 12:00';
                }
                $dateRawFixed = preg_replace('/\./', '-', $dateRawFixed);
                
                try {
                    $date = Carbon::createFromTimeString($dateRawFixed, $this->timezone);
                    $this->dateEnd = $date->format('Y-m-d\TH:i:sP');
                } catch (\Exception $exception) {
                    Log::channel($this->noticeChannel)->notice("Notice on 'dateEnd' ".$dateRaw);
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
            $this->category = isset($row[$propertyColumns->category])
                ? htmlspecialchars($row[$propertyColumns->category])
                : null;
            $this->adType = isset($row[$propertyColumns->adType])
                ? htmlspecialchars($row[$propertyColumns->adType])
                : null;
    
            $this->workTypes = isset($row[$propertyColumns->workTypes])
                ? explode(PHP_EOL, $row[$propertyColumns->workTypes])
                : [];
            $this->extraBox = isset($row[$propertyColumns->extraBox])
                ? htmlspecialchars($row[$propertyColumns->extraBox])
                : null;
            $this->bodyRepair = isset($row[$propertyColumns->bodyRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->bodyRepair])
                : [];
            $this->workExperience = isset($row[$propertyColumns->workExperience])
                ? htmlspecialchars($row[$propertyColumns->workExperience])
                : null;
            $this->guarantee = isset($row[$propertyColumns->guarantee])
                ? htmlspecialchars($row[$propertyColumns->guarantee])
                : null;
            $this->selfService = isset($row[$propertyColumns->selfService])
                ? explode(PHP_EOL, $row[$propertyColumns->selfService])
                : [];
            $this->diagnostics = isset($row[$propertyColumns->diagnostics])
                ? explode(PHP_EOL, $row[$propertyColumns->diagnostics])
                : [];
            $this->wheelService = isset($row[$propertyColumns->wheelService])
                ? explode(PHP_EOL, $row[$propertyColumns->wheelService])
                : [];
            $this->additionalEquipment = isset($row[$propertyColumns->additionalEquipment])
                ? explode(PHP_EOL, $row[$propertyColumns->additionalEquipment])
                : [];
            $this->tuning = isset($row[$propertyColumns->tuning])
                ? explode(PHP_EOL, $row[$propertyColumns->tuning])
                : [];
            $this->maintenance = isset($row[$propertyColumns->maintenance])
                ? explode(PHP_EOL, $row[$propertyColumns->maintenance])
                : [];
            $this->transmissionRepair = isset($row[$propertyColumns->transmissionRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->transmissionRepair])
                : [];
            $this->brakeRepair = isset($row[$propertyColumns->brakeRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->brakeRepair])
                : [];
            $this->steeringRepair = isset($row[$propertyColumns->steeringRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->steeringRepair])
                : [];
            $this->suspensionRepair = isset($row[$propertyColumns->suspensionRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->suspensionRepair])
                : [];
            $this->conditionerRepair = isset($row[$propertyColumns->conditionerRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->conditionerRepair])
                : [];
            $this->lockRepair = isset($row[$propertyColumns->lockRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->lockRepair])
                : [];
            $this->engineRepair = isset($row[$propertyColumns->engineRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->engineRepair])
                : [];
            $this->exhaustRepair = isset($row[$propertyColumns->exhaustRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->exhaustRepair])
                : [];
            $this->buyingHelp = isset($row[$propertyColumns->buyingHelp])
                ? explode(PHP_EOL, $row[$propertyColumns->buyingHelp])
                : [];
            $this->roadsideHelp = isset($row[$propertyColumns->roadsideHelp])
                ? explode(PHP_EOL, $row[$propertyColumns->roadsideHelp])
                : [];
            $this->painting = isset($row[$propertyColumns->painting])
                ? explode(PHP_EOL, $row[$propertyColumns->painting])
                : [];
            $this->reEquipment = isset($row[$propertyColumns->reEquipment])
                ? explode(PHP_EOL, $row[$propertyColumns->reEquipment])
                : [];
            $this->windowTinting = isset($row[$propertyColumns->windowTinting])
                ? explode(PHP_EOL, $row[$propertyColumns->windowTinting])
                : [];
            $this->electricalRepair = isset($row[$propertyColumns->electricalRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->electricalRepair])
                : [];
            $this->glassRepair = isset($row[$propertyColumns->glassRepair])
                ? explode(PHP_EOL, $row[$propertyColumns->glassRepair])
                : [];
            $this->washAndCare = isset($row[$propertyColumns->washAndCare])
                ? explode(PHP_EOL, $row[$propertyColumns->washAndCare])
                : [];
    
    
            $this->transportType = isset($row[$propertyColumns->transportType])
                ? htmlspecialchars($row[$propertyColumns->transportType])
                : null;
            $this->purpose = isset($row[$propertyColumns->purpose])
                ? htmlspecialchars($row[$propertyColumns->purpose])
                : null;
            $this->rentType = isset($row[$propertyColumns->rentType])
                ? htmlspecialchars($row[$propertyColumns->rentType])
                : null;
            $this->minimumRentalPeriod = isset($row[$propertyColumns->minimumRentalPeriod])
                ? htmlspecialchars($row[$propertyColumns->minimumRentalPeriod])
                : null;
            $this->trailerType = isset($row[$propertyColumns->trailerType])
                ? htmlspecialchars($row[$propertyColumns->trailerType])
                : null;
            $this->height = isset($row[$propertyColumns->height])
                ? htmlspecialchars($row[$propertyColumns->height])
                : null;
            $this->width = isset($row[$propertyColumns->width])
                ? htmlspecialchars($row[$propertyColumns->width])
                : null;
            $this->length = isset($row[$propertyColumns->length])
                ? htmlspecialchars($row[$propertyColumns->length])
                : null;
            $this->carryingCapacity = isset($row[$propertyColumns->carryingCapacity])
                ? htmlspecialchars($row[$propertyColumns->carryingCapacity])
                : null;
            $this->maximumPermittedWeight = isset($row[$propertyColumns->maximumPermittedWeight])
                ? htmlspecialchars($row[$propertyColumns->maximumPermittedWeight])
                : null;
            $this->pledge = isset($row[$propertyColumns->pledge])
                ? htmlspecialchars($row[$propertyColumns->pledge])
                : null;
            $this->pledgeAmount = isset($row[$propertyColumns->pledgeAmount])
                ? htmlspecialchars($row[$propertyColumns->pledgeAmount])
                : null;
            $this->commission = isset($row[$propertyColumns->commission])
                ? htmlspecialchars($row[$propertyColumns->commission])
                : null;
            $this->commissionAmount = isset($row[$propertyColumns->commissionAmount])
                ? htmlspecialchars($row[$propertyColumns->commissionAmount])
                : null;
            $this->buyout = isset($row[$propertyColumns->buyout])
                ? htmlspecialchars($row[$propertyColumns->buyout])
                : null;
            $this->delivery = isset($row[$propertyColumns->delivery])
                ? htmlspecialchars($row[$propertyColumns->delivery])
                : null;
    
            $this->rentPurpose = isset($row[$propertyColumns->rentPurpose])
                ? explode(PHP_EOL, $row[$propertyColumns->rentPurpose])
                : [];
            $this->extraTaxi = isset($row[$propertyColumns->extraTaxi])
                ? explode(PHP_EOL, $row[$propertyColumns->extraTaxi])
                : [];
            $this->extraSelf = isset($row[$propertyColumns->extraSelf])
                ? explode(PHP_EOL, $row[$propertyColumns->extraSelf])
                : [];
            
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
                ? explode(PHP_EOL, $row[$propertyColumns->imagesRaw])
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
            
            $this->apparelType = isset($row[$propertyColumns->apparelType])
                ? htmlspecialchars($row[$propertyColumns->apparelType])
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
         * @param string|null $property
         * @param string      $tagName
         *
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
            
            if ($this->condition === 'Новое') {
                switch ($this->category) {
                    case 'Детская одежда и обувь':
                    case 'Товары для детей и игрушки':
                        $this->condition = 'Новый';
                }
            }
    
            $extra = $this->formatArray($this->extraTaxi)
                .PHP_EOL.$this->formatArray($this->extraSelf)
                .(($this->extraBox === 'да') ? PHP_EOL.'<Option>Теплый бокс</Option>' : '');
            
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

            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->workTypes), "WorkTypes");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->bodyRepair), "BodyRepair");
            $resultXml .= $this->addTagIfPropertySet($this->workExperience, "WorkExperience");
            $resultXml .= $this->addTagIfPropertySet($this->guarantee, "Guarantee");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->selfService), "SelfService");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->diagnostics), "Diagnostics");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->wheelService), "WheelService");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->additionalEquipment), "AdditionalEquipment");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->tuning), "Tuning");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->maintenance), "Maintenance");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->transmissionRepair), "TransmissionRepair");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->brakeRepair), "BrakeRepair");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->steeringRepair), "SteeringRepair");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->suspensionRepair), "SuspensionRepair");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->conditionerRepair), "ConditionerRepair");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->lockRepair), "LockRepair");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->engineRepair), "EngineRepair");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->exhaustRepair), "ExhaustRepair");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->buyingHelp), "BuyingHelp");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->roadsideHelp), "RoadsideHelp");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->painting), "Painting");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->reEquipment), "ReEquipment");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->windowTinting), "WindowTinting");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->electricalRepair), "ElectricalRepair");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->glassRepair), "GlassRepair");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->washAndCare), "WashAndCare");
    
            $resultXml .= $this->addTagIfPropertySet($this->transportType, "TransportType");
            $resultXml .= $this->addTagIfPropertySet($this->purpose, "Purpose");
            $resultXml .= $this->addTagIfPropertySet($this->rentType, "RentType");
            $resultXml .= $this->addTagIfPropertySet($this->minimumRentalPeriod, "MinimumRentalPeriod");
            $resultXml .= $this->addTagIfPropertySet($this->trailerType, "TrailerType");
            $resultXml .= $this->addTagIfPropertySet($this->height, "Height");
            $resultXml .= $this->addTagIfPropertySet($this->width, "Width");
            $resultXml .= $this->addTagIfPropertySet($this->length, "Length");
            $resultXml .= $this->addTagIfPropertySet($this->carryingCapacity, "CarryingCapacity");
            $resultXml .= $this->addTagIfPropertySet($this->maximumPermittedWeight, "MaximumPermittedWeight");
            $resultXml .= $this->addTagIfPropertySet($this->pledge, "Pledge");
            $resultXml .= $this->addTagIfPropertySet($this->pledgeAmount, "PledgeAmount");
            $resultXml .= $this->addTagIfPropertySet($this->commission, "Commission");
            $resultXml .= $this->addTagIfPropertySet($this->commissionAmount, "CommissionAmount");
            $resultXml .= $this->addTagIfPropertySet($this->buyout, "Buyout");
            $resultXml .= $this->addTagIfPropertySet($this->delivery, "Delivery");
            $resultXml .= $this->addTagIfPropertySet($this->formatArray($this->rentPurpose), "RentPurpose");;
            $resultXml .= $this->addTagIfPropertySet($extra, "Extra");;
            
            $resultXml .= $this->addTagIfPropertySet($this->videoURL, "VideoURL");
            $resultXml .= $this->addTagIfPropertySet($this->avitoId, "AvitoId");
            $resultXml .= $this->addTagIfPropertySet($this->adStatus, "AdStatus");
            $resultXml .= $this->addTagIfPropertySet($this->placementType, "ListingFee");
            $resultXml .= $this->addTagIfPropertySet($this->messages, "AllowEmail");
            $resultXml .= $this->addTagIfPropertySet($this->apparelType, "ApparelType");
            
            return $resultXml;
        }
        
        protected function formatArray(array $array): string
        {
            return join(PHP_EOL, array_map(function (string $option): string {
                return $this->addTagIfPropertySet($option, 'Option');
            }, $array));
        }
    }
