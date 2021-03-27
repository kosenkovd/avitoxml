<?php
    
    namespace App\Models\Ads;
    
    use App\Models\Dict\UlaCategory;
    use App\Models\TableHeader;

    class UlaAd extends AdBase {
        protected ?UlaCategory $ulaCategory;
        
        /** @var UlaCategory[]  */
        protected array $ulaCategories;
        
        protected ?string $subCategory;
        protected ?string $urlAd;
        
        public function __construct(array $row, TableHeader $propertyColumns, $ulaCategories)
        {
            parent::__construct($row, $propertyColumns);
            
            $this->ulaCategories = $ulaCategories;
            
            $this->urlAd = isset($row[$propertyColumns->urlAd])
                ? htmlspecialchars($row[$propertyColumns->urlAd])
                : null;
            $this->subCategory = isset($row[$propertyColumns->goodsType])
                ? htmlspecialchars($row[$propertyColumns->goodsType])
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
    
            $imageTags = $this->generateImageUlaTags($this->images);
            
            $resultXml = $this->addTagIfPropertySet($this->urlAd, "url");
            $resultXml .= $this->addTagIfPropertySet($ulaCategory, "youlaCategoryId");
            $resultXml .= $this->addTagIfPropertySet($ulaSubCategory, "youlaSubcategoryId");
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
            $category = mb_strtolower(preg_replace('/\s/i', "", $this->category));
            
            foreach ($this->ulaCategories as $ulaCategory) {
                if ($ulaCategory->getName() === $category) {
                    return $ulaCategory->getId();
                }
            }
            return "";
        }
        
        protected function generateUlaSubCategory(): string
        {
            $category = mb_strtolower(preg_replace('/\s/i', "", $this->subCategory));
    
            foreach ($this->ulaCategories as $ulaCategory) {
                if ($ulaCategory->getName() === $category) {
                    return $ulaCategory->getId();
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
