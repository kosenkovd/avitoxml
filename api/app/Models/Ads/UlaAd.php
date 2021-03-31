<?php
    
    namespace App\Models\Ads;
    
    use App\Models\Dict\UlaCategory;
    use App\Models\TableHeader;
    
    class UlaAd extends AdBase {
        protected ?UlaCategory $ulaCategory;
        
        /** @var UlaCategory[] */
        protected array $ulaCategories;
        
        protected ?string $subCategory;
        protected ?string $autoPart;
        protected ?string $urlAd;
        
        public function __construct(array $row, TableHeader $propertyColumns, $ulaCategories)
        {
            parent::__construct($row, $propertyColumns);
            
            $this->ulaCategories = $ulaCategories;
            
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
                $this->description = str_replace("\n\r", "\n", $row[$propertyColumns->description]);
                $this->description = str_replace("\n", "\r\n", $this->description);
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
            $ulaCategory = $this->generateUlaCategory();
            $ulaSubCategory = $this->generateUlaSubCategory();
            $ulaAutoPartCategory = $this->generateUlaAutoPartCategory();
            $condition = $this->generateCondition();
            
            $imageTags = $this->generateImageUlaTags($this->images);
            
            $resultXml = $this->addTagIfPropertySet($this->urlAd, "url");
            $resultXml .= $this->addTagIfPropertySet($ulaCategory, "youlaCategoryId");
            $resultXml .= $this->addTagIfPropertySet($ulaSubCategory, "youlaSubcategoryId");
            $resultXml .= $this->addTagIfPropertySet($ulaAutoPartCategory, "avtozapchasti_tip");
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
        
        protected function generateUlaCategory(): string
        {
            if (!$this->category) {
                return "";
            }
            $name = mb_strtolower(preg_replace('/\s/i', "", $this->category));
            
            foreach ($this->ulaCategories as $ulaCategory) {
                if ($ulaCategory->getName() === $name) {
                    return $ulaCategory->getId();
                }
            }
            return "";
        }
        
        protected function generateUlaSubCategory(): string
        {
            if (!$this->subCategory) {
                return "";
            }
            $name = mb_strtolower(preg_replace('/\s/i', "", $this->subCategory));
            
            foreach ($this->ulaCategories as $ulaCategory) {
                if ($ulaCategory->getName() === $name) {
                    return $ulaCategory->getId();
                }
            }
            return "";
        }
        
        protected function generateUlaAutoPartCategory(): string
        {
            if (!$this->autoPart) {
                return "";
            }
            $name = mb_strtolower(preg_replace('/\s/i', "", $this->autoPart));
            
            foreach ($this->ulaCategories as $ulaCategory) {
                if ($ulaCategory->getName() === $name) {
                    return $ulaCategory->getId();
                }
            }
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
            }

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