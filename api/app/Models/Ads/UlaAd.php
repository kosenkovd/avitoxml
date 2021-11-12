<?php
    
    namespace App\Models\Ads;
    
    use App\Models\TableHeader;
    use DateTimeZone;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Facades\Log;

    class UlaAd extends AdBase {
        protected Collection $ulaCategories;
        protected Collection $ulaTypes;
        
        protected ?string $datePublication;
        protected ?string $subCategory;
        protected ?string $autoPart;
        protected ?string $urlAd;
        
        public function __construct(
            array $row,
            TableHeader $propertyColumns,
            Collection $ulaCategories,
            Collection $ulaTypes
        )
        {
            parent::__construct($row, $propertyColumns);
            
            $this->ulaCategories = $ulaCategories;
            $this->ulaTypes = $ulaTypes;
            
            $this->datePublication = isset($row[$propertyColumns->dateCreated])
                ? htmlspecialchars(trim($row[$propertyColumns->dateCreated]))
                : null;
            $this->urlAd = isset($row[$propertyColumns->urlAd])
                ? htmlspecialchars(trim($row[$propertyColumns->urlAd]))
                : null;
            $this->subCategory = isset($row[$propertyColumns->goodsType])
                ? htmlspecialchars($row[$propertyColumns->goodsType])
                : null;
            $this->autoPart = isset($row[$propertyColumns->autoPart])
                ? htmlspecialchars($row[$propertyColumns->autoPart])
                : null;
            
            $this->description = null;
            if (isset($row[$propertyColumns->description])) {
                $this->description = str_replace("\r\n", "<br>", $row[$propertyColumns->description]);
                $this->description = str_replace("\n\r", "\n", $this->description);
                $this->description = str_replace("\n", "<br>", $this->description);
            } else {
                $this->description = null;
            }
        }
        
        public function toAvitoXml(): string
        {
            return '';
        }
        
        public function toUlaXml(): string
        {
            $id = $this->id;
            $defaultTags = $this->generateUlaXML();
            
            return <<<ULAXML
    <offer id="$id">
$defaultTags
    </offer>
ULAXML;
        }
        
        protected function generateUlaXML(): string
        {
            $datePublication = $this->generateDatePublication();
            $ulaCategory = $this->generateUlaCategory();
            $ulaSubCategory = $this->generateUlaSubCategory();
            $ulaType = $this->generateUlaType();
            $condition = $this->generateCondition();
            
            $imageTags = $this->generateImageUlaTags($this->images);
            
            $resultXml = $this->addTagIfPropertySet($datePublication, "datePublication");
            $resultXml .= $this->addTagIfPropertySet($this->urlAd, "url");
            $resultXml .= $this->addTagIfPropertySet($ulaCategory, "youlaCategoryId");
            $resultXml .= $this->addTagIfPropertySet($ulaSubCategory, "youlaSubcategoryId");
            $resultXml .= $ulaType;
            $resultXml .= $condition;
            $resultXml .= $this->addTagIfPropertySet($this->address, "address");
            $resultXml .= $this->addTagIfPropertySet($this->price, "price");
            $resultXml .= $this->addTagIfPropertySet($this->contactPhone, "phone");
            $resultXml .= $this->addTagIfPropertySet($this->title, "name");
            $resultXml .= $imageTags;
            $resultXml .= $this->addTagIfPropertySet("<![CDATA[$this->description]]>", "description");
            $resultXml .= $this->addTagIfPropertySet($this->managerName, 'managerName');
            
            return $resultXml;
        }
        
        protected function generateDatePublication(): string
        {
            if (is_null($this->datePublication)) {
                return "";
            }
            
            $dateRaw = mb_strtolower($this->datePublication);
    
            if ($dateRaw === 'сразу') {
                return Carbon::now($this->timezone)->format('d-m-Y');
            } else {
                $dateRawFixed = $dateRaw;
                if (!strpos($dateRawFixed, ":")) {
                    $dateRawFixed .= ' 12:00';
                }
                $dateRawFixed = preg_replace('/\./', '-', $dateRawFixed);
    
                try {
                    $date = Carbon::createFromTimeString($dateRawFixed, $this->timezone);
                    return $date->format('d-m-Y');
                } catch (\Exception $exception) {
                    Log::channel($this->noticeChannel)->notice("Notice on 'generating date of publication' ".$dateRaw);
                    return "";
                }
            }
        }
        
        protected function generateUlaCategory(): string
        {
            if (!$this->category) {
                return "";
            }
            $name = mb_strtolower(preg_replace('/\s/i', "", $this->category));
            
            foreach ($this->ulaCategories as $ulaCategory) {
                if ($ulaCategory->name === $name) {
                    return $ulaCategory->id;
                }
            }
            
            Log::channel($this->noticeChannel)->notice("Notice on category '".$this->category."'");
            
            return "";
        }
        
        protected function generateUlaSubCategory(): string
        {
            if (!$this->subCategory && !$this->category) {
                return "";
            }
            
            $name = mb_strtolower(preg_replace('/\s/i', "", $this->subCategory));
            $category = mb_strtolower(preg_replace('/\s/i', "", $this->category));
            
            foreach ($this->ulaCategories as $ulaCategory) {
                if ($ulaCategory->name === $category . $name) {
                    return $ulaCategory->id;
                }
            }
    
            Log::channel($this->noticeChannel)->notice("Notice on subCategory '".$this->subCategory."'");
            
            return "";
        }
        
        protected function generateUlaType(): string
        {
            if (!$this->autoPart) {
                return "";
            }
            $name = mb_strtolower(preg_replace('/\s/i', "", $this->autoPart));
            
            foreach ($this->ulaTypes as $type) {
                if ($type->name === $name) {
                    return $this->addTagIfPropertySet($type->id, $type->tag);
                }
            }
    
            Log::channel($this->noticeChannel)->notice("Notice on ulaTypes '".$this->autoPart."'");
            
            return "";
        }
        
        protected function generateCondition(): string
        {
            $name = mb_strtolower(preg_replace('/\s/i', "", $this->condition));

            switch ($name) {
                case "выезднадом":
                    $id = 167448;
                    return $this->addTagIfPropertySet($id, "okazanie_uslug");
                case "всалоне":
                    $id = 167449;
                    return $this->addTagIfPropertySet($id, "okazanie_uslug");
                case "домаумастера":
                    $id = 167450;
                    return $this->addTagIfPropertySet($id, "okazanie_uslug");
            }

            $subCategoryName = mb_strtolower(preg_replace('/\s/i', "", $this->subCategory));
            switch ($subCategoryName) {
                case "запчасти":
                case "шиныидиски":
                    switch ($name) {
                        case "б/у":
                        case "used":
                            $id = 165659;
                            return $this->addTagIfPropertySet($id, "zapchast_sostoyanie");
                        case "новое":
                        case "new":
                            $id = 165658;
                            return $this->addTagIfPropertySet($id, "zapchast_sostoyanie");
                    }
                    break;
                case "женскийгардероб":
                case "мужскойгардероб":
                case "детскийгардероб":
                    switch ($name) {
                        case "б/у":
                        case "used":
                            $id = 166110;
                            return $this->addTagIfPropertySet($id, "sostojanie_garderob");
                        case "новое":
                        case "new":
                            $id = 166111;
                            return $this->addTagIfPropertySet($id, "sostojanie_garderob");
                    }
            }
            
            return "";
        }
        
        protected function generateImageUlaTags(array $images): string
        {
            if (count($images) == 0 || (count($images) == 1 && $images[0] == "")) {
                return "";
            }
            $imageTags = PHP_EOL;
            foreach ($images as $image) {
                $image = trim($image);
                $image = str_replace('&', '&amp;', $image);
                $imageTags .= "<picture>".$image."</picture>".PHP_EOL;
            }
            return $imageTags;
        }
    }
