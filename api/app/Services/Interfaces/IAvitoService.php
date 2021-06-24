<?php

namespace App\Services\Interfaces;

use Exception;
use Illuminate\Support\Collection;

interface IAvitoService {
    /**
     * @param string $clientId
     * @param string $clientSecret
     *
     * @return string[] access_token
     * @throws Exception
     *
     */
    public function authorize(string $clientId, string $clientSecret): array;
    
    /**
     * @param string $userId
     * @param array  $avitoAuthResponse
     *
     * @return mixed
     * @throws Exception
     *
     */
    public function getLastReport(string $userId, array $avitoAuthResponse): array;
    
    /**
     * @param string $userId
     * @param array  $avitoAuthResponse
     * @param array  $itemIds
     * @param array  $fields
     *
     * @return Collection
     * @throws Exception
     */
    public function getStatistics(
        string $userId,
        array $avitoAuthResponse,
        array $itemIds,
        array $fields
    ): Collection;
}
