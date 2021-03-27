<?php
    
    namespace App\Models\Ads;
    
    use App\Models\TableHeader;
    
    class YandexAd extends AdBase {
        protected ?string $address2 = null;
        protected ?string $address3 = null;
        protected ?string $address4 = null;
        protected ?string $address5 = null;
        protected ?string $address6 = null;
        protected ?string $address7 = null;
        protected ?string $address8 = null;
        protected ?string $address9 = null;
        protected ?string $address10 = null;
        
        public function __construct(array $row, TableHeader $propertyColumns)
        {
            parent::__construct($row, $propertyColumns);
            
            $this->address2 = $this->getFullAddress($row, $propertyColumns, 2);
            $this->address3 = $this->getFullAddress($row, $propertyColumns, 3);
            $this->address4 = $this->getFullAddress($row, $propertyColumns, 4);
            $this->address5 = $this->getFullAddress($row, $propertyColumns, 5);
            $this->address6 = $this->getFullAddress($row, $propertyColumns, 6);
            $this->address7 = $this->getFullAddress($row, $propertyColumns, 7);
            $this->address8 = $this->getFullAddress($row, $propertyColumns, 8);
            $this->address9 = $this->getFullAddress($row, $propertyColumns, 9);
            $this->address9 = $this->getFullAddress($row, $propertyColumns, 10);
        }
        
        public function toAvitoXml(): string
        {
            return '';
        }
        
        public function toYandexXml(): string
        {
            $defaultTags = $this->generateYandexXML();
            
            return <<<YANDEXXML
    <offer>
$defaultTags
    </offer>
YANDEXXML;
        }
        
        protected function generateYandexXML(): string
        {
            $imageTags = $this->generateImageYandexTags($this->images);
            $condition = $this->generateCondition();
            $seller = $this->generateSeller();
            
            $resultXml = $this->addTagIfPropertySet($this->id, "id");
            $resultXml .= $this->addTagIfPropertySet($seller, "seller");
            $resultXml .= $this->addTagIfPropertySet($this->title, "title");
            $resultXml .= $this->addTagIfPropertySet($condition, 'condition');
            $resultXml .= $this->addTagIfPropertySet("<![CDATA[$this->description]]>", "description");
            $resultXml .= $this->addTagIfPropertySet($this->category, "category");
            $resultXml .= $this->addTagIfPropertySet($imageTags, "images");
            $resultXml .= $this->addTagIfPropertySet($this->price, "price");
            
            return $resultXml;
        }
        
        protected function generateImageYandexTags(array $images): string
        {
            if (count($images) == 0 || (count($images) == 1 && $images[0] == "")) {
                return "";
            }
            $imageTags = PHP_EOL;
            foreach ($images as $image) {
                $image = trim($image);
                $imageTags .= "\t\t\t<image>".str_replace('&', '&amp;', $image)."</image>".PHP_EOL;
            }
            return $imageTags."\t\t";
        }
        
        protected function generateCondition(): string
        {
            switch ($this->condition) {
                case "новый":
                case "new":
                    $result = "new";
                    break;
                case "б/у":
                case "used":
                    $result = "used";
                    break;
                case "неприменимо":
                case "inapplicable":
                    $result = "inapplicable";
                    break;
                default:
                    return "";
            }
            
            return $result;
        }
        
        protected function generateSeller(): string
        {
            $phone = $this->addTagIfPropertySet($this->contactPhone, 'phone');
            $contactMethod = $this->generateContactMethod();
            $contacts = $this->addTagIfPropertySet($phone.$contactMethod.PHP_EOL, 'contacts');
            
            $locations = $this->generateLocations();
            
            return $contacts.$locations;
        }
        
        protected function generateContactMethod(): string
        {
            switch ($this->contactsType) {
                case "только звонки":
                    $result = "only-phone";
                    break;
                case "только сообщения":
                    $result = "only-chat";
                    break;
                case "звонки и сообщения":
                    $result = "any";
                    break;
                default:
                    return "";
            }
            
            return $this->addTagIfPropertySet($result, 'contact-method');
        }
        
        protected function generateLocations(): string
        {
            $locations = '';
            $locations .= $this->generateLocation($this->address);
            $locations .= $this->generateLocation($this->address2);
            $locations .= $this->generateLocation($this->address3);
            $locations .= $this->generateLocation($this->address4);
            $locations .= $this->generateLocation($this->address5);
            $locations .= $this->generateLocation($this->address6);
            $locations .= $this->generateLocation($this->address7);
            $locations .= $this->generateLocation($this->address8);
            $locations .= $this->generateLocation($this->address9);
            $locations .= $this->generateLocation($this->address10);
            
            return $this->addTagIfPropertySet($locations, 'locations');
        }
        
        protected function generateLocation(?string $address)
        {
            $addressTagged = $this->addTagIfPropertySet($address, 'address');
            if ($addressTagged === '') {
                return '';
            }
            
            return $this->addTagIfPropertySet($addressTagged, 'location');
        }
    }
