<?php


namespace App\Services;


use App\Services\Interfaces\IAvitoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AvitoService implements IAvitoService {
    /**
     * @inheritDoc
     */
    public function authorize(string $clientId, string $clientSecret): array
    {
        $res = Http::get(
            'https://api.avito.ru/token/',
            [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret
            ]
        );
        
        $json = $res->json();
        
        if ($res->status() !== 200) {
            throw new \Exception('Avito error on authorization');
        }
        
        return $json;
    }
    
    /**
     * @inheritDoc
     */
    public function getLastReport(string $userId, array $avitoAuthResponse): array
    {
        $token = $this->getToken($avitoAuthResponse);
        $res = Http::withHeaders([
            'Authorization' => $token
        ])
            ->get('https://api.avito.ru/autoload/v1/accounts/'.$userId.'/reports/last_report/');
        
        if ($res->status() !== 200) {
            $message = 'Avito error on getting report'.PHP_EOL.
                $res->json()['error']['message'];
            Log::channel('avitoReport')->error($message);
            
            return [];
        }
        
        return $res->json()['report'];
    }
    
    /**
     * @inheritDoc
     */
    public function getStatistics(
        string $userId,
        array $avitoAuthResponse,
        array $itemIds,
        array $fields
    ): Collection
    {
        $token = $this->getToken($avitoAuthResponse);
        $res = Http::withHeaders([
            'Authorization' => $token
        ])
            ->post(
                'https://api.avito.ru/stats/v1/accounts/'.$userId.'/items',
                [
                    'itemIds' => $itemIds,
                    'fields' => $fields
                ]
            );
        
        if ($res->status() !== 200) {
            Log::channel('avitoStatistics')->error('Avito error on getting statistics'.PHP_EOL.
                $res->json()['error']['message']);
            
            return new Collection;
        }
        
        return collect($res->json()['result']['items']);
    }
    
    /**
     * @param array $avitoAuthResponse
     *
     * @return string
     */
    private function getToken(array $avitoAuthResponse): string
    {
        return $avitoAuthResponse['token_type'].' '.$avitoAuthResponse['access_token'];
    }
}
