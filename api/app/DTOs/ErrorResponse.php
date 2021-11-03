<?php
    
    
    namespace App\DTOs;
    
    
    class ErrorResponse {
        public array $error;
        private string $message;
        private string $code;
        
        public function __construct(string $message, string $code = '')
        {
            $this->message = $message;
            $this->code = $code;
            
            $this->error = [
                'message' => $message,
                'code' => $code
            ];
        }
    }
