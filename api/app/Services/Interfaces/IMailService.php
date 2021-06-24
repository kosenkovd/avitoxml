<?php
    
    
    namespace App\Services\Interfaces;
    use App\Models\Table;
    use App\Models\TableLaravel;
    use App\Models\TableMarketplace;

    interface IMailService
    {
        public function sendEmailWithTableData(Table $dataForEmail): void;
        public function sendEmailWithTableDataLaravel(TableLaravel $table): void;
        public function sendEmailWithTableDataMarketplace(TableMarketplace $table): void;
    }
