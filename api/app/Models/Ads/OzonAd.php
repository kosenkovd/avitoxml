<?php
    
    namespace App\Models\Ads;
    
    use App\Models\TableHeader;

    class OzonAd extends AdBase {
        private ?string $ozonOfferId;
        private ?string $ozonPrice = null;
        private ?string $ozonOldPrice = null;
        private ?string $ozonPremiumPrice = null;
        private ?string $ozonWarehouseName = null;
        private ?string $ozonInstock = null;
        private ?string $ozonWarehouseName2 = null;
        private ?string $ozonInstock2 = null;
        private ?string $ozonWarehouseName3 = null;
        private ?string $ozonInstock3 = null;
        private ?string $ozonWarehouseName4 = null;
        private ?string $ozonInstock4 = null;
        private ?string $ozonWarehouseName5 = null;
        private ?string $ozonInstock5 = null;
        private ?string $ozonWarehouseName6 = null;
        private ?string $ozonInstock6 = null;
        private ?string $ozonWarehouseName7 = null;
        private ?string $ozonInstock7 = null;
        private ?string $ozonWarehouseName8 = null;
        private ?string $ozonInstock8 = null;
        private ?string $ozonWarehouseName9 = null;
        private ?string $ozonInstock9 = null;
        private ?string $ozonWarehouseName10 = null;
        private ?string $ozonInstock10 = null;
        
        public function __construct(array $row, TableHeader $propertyColumns)
        {
            parent::__construct($row, $propertyColumns);
            
            $this->checkAndSetProp($row, $propertyColumns, 'ozonOfferId');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonPrice');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonOldPrice');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonPremiumPrice');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonWarehouseName');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonInstock');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonWarehouseName2');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonInstock2');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonWarehouseName3');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonInstock3');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonWarehouseName4');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonInstock4');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonWarehouseName5');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonInstock5');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonWarehouseName6');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonInstock6');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonWarehouseName7');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonInstock7');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonWarehouseName8');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonInstock8');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonWarehouseName9');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonInstock9');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonWarehouseName10');
            $this->checkAndSetProp($row, $propertyColumns, 'ozonInstock10');
        }
    
        /**
         * @param array       $row
         * @param TableHeader $propertyColumns
         * @param string      $prop
         */
        private function checkAndSetProp(array $row, TableHeader $propertyColumns, string $prop): void
        {
            $this->$prop = isset($row[$propertyColumns->$prop])
                ? htmlspecialchars(trim($row[$propertyColumns->$prop]))
                : null;
        }
        
        public function toAvitoXml(): string
        {
            return '';
        }
        
        public function toOzonXml(): string
        {
            $id = $this->ozonOfferId;
            $defaultTags = $this->generateOzonXML();
            
            return <<<ULAXML
    <offer id="$id">
$defaultTags
    </offer>
ULAXML;
        }
        
        protected function generateOzonXML(): string
        {
            $resultXml = $this->addTagIfPropertySet($this->ozonPrice, 'price');
            $resultXml .= $this->addTagIfPropertySet($this->ozonOldPrice, 'oldprice');
            $resultXml .= $this->addTagIfPropertySet($this->ozonPremiumPrice, 'premium_price');
            $resultXml .= $this->addTagIfPropertySet($this->generateOutlets(), 'outlets');
            
            return $resultXml;
        }
        
        private function generateOutlets(): string
        {
            return $this->generateOutlet().PHP_EOL.
                $this->generateOutlet(2).PHP_EOL.
                $this->generateOutlet(3).PHP_EOL.
                $this->generateOutlet(4).PHP_EOL.
                $this->generateOutlet(5).PHP_EOL.
                $this->generateOutlet(6).PHP_EOL.
                $this->generateOutlet(7).PHP_EOL.
                $this->generateOutlet(8).PHP_EOL.
                $this->generateOutlet(9).PHP_EOL.
                $this->generateOutlet(10);
        }
        
        private function generateOutlet(string $num = ''): string
        {
            $ozonInstock = 'ozonInstock'.$num;
            $ozonWarehouseName = 'ozonWarehouseName'.$num;
            
            if ($this->$ozonWarehouseName == '') {
                return '';
            }
            
            return '<outlet instock="'.$this->$ozonInstock.'" warehouse_name="'
                .$this->$ozonWarehouseName.'"></outlet>';
        }
    }
