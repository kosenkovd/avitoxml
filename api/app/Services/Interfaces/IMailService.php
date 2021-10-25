<?php
    
    
    namespace App\Services\Interfaces;
    use App\Models\TableLaravel;
    use App\Models\TableMarketplace;

    interface IMailService
    {
        public function sendEmailWithTableDataLaravel(TableLaravel $table): void;
        public function sendEmailWithTableDataMarketplace(TableMarketplace $table): void;
    }
