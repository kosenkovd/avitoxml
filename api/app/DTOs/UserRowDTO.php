<?php
    
    
    namespace App\DTOs;
    
    
    class UserRowDTO extends UserBaseDTO {
        public ?string $token;
        
        /**
         * UserRowDTO constructor.
         * @inheritDoc
         * @param
         */
        public function __construct(
            int $userId,
            int $roleId,
            string $dateCreated,
            ?string $phoneNumber,
            ?string $socialNetworkUrl,
            bool $isBlocked,
            ?string $notes,
            ?string $name,
            ?string $token
        )
        {
            parent::__construct(
                $userId,
                $roleId,
                $dateCreated,
                $phoneNumber,
                $socialNetworkUrl,
                $isBlocked,
                $notes,
                $name
            );
            $this->token = $token;
        }
    }
