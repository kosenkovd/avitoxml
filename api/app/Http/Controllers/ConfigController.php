<?php


namespace App\Http\Controllers;

use App\Configuration\Config;
use App\Configuration\Spreadsheet\SheetNames;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class ConfigController extends BaseController
{
    private SheetNames $sheetNames;
    private Config $config;
    
    public function __construct(
        SheetNames $sheetNames,
        Config $config
    )
    {
        $this->sheetNames = $sheetNames;
        $this->config = $config;
    }
    
    /**
     * GET /config/maxAds ?limit=5000
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function maxAds(Request $request): JsonResponse
    {
        $limit = (int)$request->get('limit') ?: $this->config->getMaxAdsLimit();
        $maxAds = DB::table('discount')->get()->map(function ($item) use ($limit) {
            if ($item->ads > $limit) {
                return null;
            }
            
            return [
                'value' => $item->ads,
                'text' => $item->ads,
                'discount' => $item->discount
            ];
        })
            ->filter();
        
        $prices = DB::table('prices')
            ->get();
        
        return response()->json([
            'prices' => $prices,
            $this->sheetNames->getAvito() => $maxAds,
            $this->sheetNames->getYandex() => $maxAds,
            $this->sheetNames->getYoula() => $maxAds,
            $this->sheetNames->getOzon() => $maxAds,
        ]);
    }
}
