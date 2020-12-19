<?php
    
    
    namespace App\Configuration\Log;
    
    
    class JobLog {
        private bool $enabled = false;
    
        /**
         * @return bool
         */
        public function getEnabled(): bool
        {
            return $this->enabled;
        }
    }
