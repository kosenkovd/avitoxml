<?php
    
    
    namespace App\Services\Interfaces;
    use App\Models\Table;
    
    interface IMailService
    {
        public function sendEmailWithTableData(Table $dataForEmail): void;
    }
