<?php
    
    namespace App\Repositories\Interfaces;
    
    
    use App\Models\Dict\UlaCategory;
    
    interface IDictRepository
    {
        /**
         * Get all Ula categories.
         *
         * @return UlaCategory[]
         */
        public function getUlaCategories(): array;
    }
