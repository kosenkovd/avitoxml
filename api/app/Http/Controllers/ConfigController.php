<?php


namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\Console\Jobs\FillAvitoReportJob;
use App\DTOs\ErrorResponse;
use App\DTOs\UserDTO;
use App\Enums\Roles;
use App\Helpers\LinkHelper;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Mappers\UserDTOMapper;
use App\Models\Generator;
use App\Models\GeneratorLaravel;
use App\Models\Table;
use App\Models\TableLaravel;
use App\Models\User;
use App\Models\UserLaravel;
use App\Repositories\GeneratorRepository;
use App\Repositories\Interfaces\IGeneratorRepository;
use App\Repositories\Interfaces\ITableRepository;
use App\Repositories\Interfaces\IUserRepository;
use App\Repositories\TableRepository;
use App\Services\AvitoService;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IXmlGenerationService;
use App\Services\MailService;
use App\Services\SpreadsheetClientService;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use JsonMapper;
use Ramsey\Uuid\Guid\Guid;

class ConfigController extends BaseController
{
    private SheetNames $sheetNames;
    
    public function __construct(
        SheetNames $sheetNames
    ) {
        $this->sheetNames = $sheetNames;
    }
    
    /**
     * GET /config/maxAds
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function maxAds(Request $request): JsonResponse
    {
        $maxAds = DB::table('discount')->get()->map(function ($item) {
            if ($item->ads > 5000) {
                return null;
            }
            
            return [
                'value' => $item->ads,
                'text' => $item->ads
            ];
        })
            ->filter();
        
        return response()->json([
            $this->sheetNames->getAvito() => $maxAds,
            $this->sheetNames->getYandex() => $maxAds,
            $this->sheetNames->getYoula() => $maxAds,
            $this->sheetNames->getOzon() => $maxAds,
        ]);
    }
}
