<?php


namespace App\Http\Controllers;

use App\DTOs\ErrorResponse;
use App\Enums\Roles;
use App\Models\GeneratorLaravel;
use App\Models\UserLaravel;
use App\Services\PriceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class PricesController extends BaseController
{
    private PriceService $priceService;
    private Roles $roles;
    
    public function __construct(PriceService $priceService)
    {
        $this->priceService = $priceService;
        $this->roles = new Roles();
    }
    
    /**
     * POST /prices
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'targetPlatform' => 'string|required',
            'maxAds' => 'integer|required',
            'generatorGuid' => 'string|required'
        ]);
    
        /** @var GeneratorLaravel|null $generator */
        $generator = GeneratorLaravel::query()
            ->where('generatorGuid', $request->input('generatorGuid'))
            ->first();
        if (is_null($generator)) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[404], 'Can\'t find user\'s generator'),
                404
            );
        }
        /** @var UserLaravel $user */
        $user = auth()->user();
        if (
            (
                $generator->table &&
                ($user->id !== $generator->table->user->id)
            ) ||
            (
                $generator->tableMarketplace &&
                ($user->id !== $generator->tableMarketplace->user->id)
            )
        ) {
            if ($user->roleId !== $this->roles->Admin) {
                return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
            }
        }
    
        $targetPlatform = $request->input('targetPlatform');
        $maxAds = $request->input('maxAds');
    
        $discount = DB::table('discount')
            ->where('ads', $maxAds)
            ->first();
        if (is_null($discount)) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[400], 'Invalid max ads amount'),
                400
            );
        }
        $discount = $discount->discount;
    
        $priceTargetPlatform = DB::table('prices')
            ->where('targetPlatform', $targetPlatform)
            ->first();
        if (is_null($priceTargetPlatform)) {
            return response()->json(
                new ErrorResponse(Response::$statusTexts[400], 'Can\'t find target platform'),
                400
            );
        }
        $priceForAd = $priceTargetPlatform->price;
    
        $price = $this->priceService->getMaxAdsPrice(
            $user,
            $priceForAd,
            $maxAds,
            $discount,
            $generator,
        );
        
        return response()->json(round($price, 2));
    }
}
