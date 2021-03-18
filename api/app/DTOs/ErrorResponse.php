<?php
    
    
    namespace App\DTOs;
    
    
    class ErrorResponse {
        public array $error;
        private string $message;
        
        public function __construct(string $message)
        {
            $this->message = $message;
            
            $this->error = [
                'message' => $message
            ];
        }
    }
