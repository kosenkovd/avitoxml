<?php


namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\DTOs\ErrorResponse;
use App\Enums\Roles;
use App\Models\GeneratorLaravel;
use App\Models\TableLaravel;
use App\Models\TableMarketplace;
use App\Models\UserLaravel;
use App\Services\Interfaces\IXmlGenerationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use App\Repositories\Interfaces;
use Illuminate\Support\Facades\Log;
use JsonMapper;

/**
 * Class GeneratorController
 * Base route /api/tables/$tableId/generators
 *
 * @package App\Http\Controllers
 */
class GeneratorController extends BaseController
{
    /**
     * @var Interfaces\ITableRepository Models\Table repository.
     */
    private Interfaces\ITableRepository $tableRepository;
    
    /**
     * @var Interfaces\IGeneratorRepository Models\Generator repository.
     */
    private Interfaces\IGeneratorRepository $generatorsRepository;
    
    /**
     * @var IXmlGenerationService XML generator for spreadsheet.
     */
    private IXmlGenerationService $xmlGenerationService;
    
    /**
     * @var SheetNames configuration with sheet names.
     */
    private SheetNames $sheetNamesConfig;
    
    private JsonMapper $jsonMapper;
    
    private Interfaces\IUserRepository $userRepository;
    
    /**
     * @var Roles Enum of roles.
     */
    private Roles $roles;
    
    public function __construct(
        Interfaces\ITableRepository $tableRepository,
        Interfaces\IGeneratorRepository $generatorsRepository,
        Interfaces\IUserRepository $userRepository,
        IXmlGenerationService $xmlGenerationService,
        SheetNames $sheetNames,
        JsonMapper $jsonMapper
    )
    {
        $this->tableRepository = $tableRepository;
        $this->generatorsRepository = $generatorsRepository;
        $this->userRepository = $userRepository;
        $this->xmlGenerationService = $xmlGenerationService;
        $this->sheetNamesConfig = $sheetNames;
        $this->jsonMapper = $jsonMapper;
        $this->roles = new Roles();
    }
    
    /**
     * GET /
     *
     * Get all generators instances for table.
     *
     * @param string $tableId table guid.
     *
     * @return JsonResponse all generators for table.
     */
    public function index(string $tableId): JsonResponse
    {
        return response()->json(null, 200);
    }
    
    /**
     * GET /tables/{$tableGuid}/generators/{$generatorGuid}
     *
     * Get generated XML file.
     *
     * @param $tableGuid     string table guid.
     * @param $generatorGuid string generator guid.
     *
     * @return Response generated XML.
     */
    public function show(string $tableGuid, string $generatorGuid): Response
    {
        return response('')
            ->header("Content-Type", "application/xml");
    }
    
    /**
     * PUT /tables/$tableGuid/generators/$generatorGuid
     *
     * Update table.
     *
     * @param        $request   Request update request.
     * @param string $tableGuid table guid.
     * @param string $generatorGuid
     *
     * @return JsonResponse updated table resource.
     */
    public function update(Request $request, string $tableGuid, string $generatorGuid): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
    
        Log::channel('apilog')->info('PUT /tables/'.$tableGuid.'/generators/'
            .$generatorGuid.' - user '.$currentUser->id);
        
        if ($currentUser->roleId !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
    
        /** @var TableLaravel|null $table */
        $table = TableLaravel::query()->where('tableGuid', $tableGuid)->first();
        if(is_null($table)) {
            $table = TableMarketplace::query()->where('tableGuid', $tableGuid)->first();
        
            if(is_null($table)) {
                return response()->json(Response::$statusTexts[404], 404);
            }
        }
    
        /** @var GeneratorLaravel|null $generator */
        $generator = $table->generator($generatorGuid);
        if (is_null($generator)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        $request->validate(['maxAds' => 'int|required']);
        
        $generator->update($request->only(['maxAds']));
        
        return response()->json(null, 200);
    }
    
    /**
     * POST /
     *
     * Create new generator for table.
     *
     * @param string $tableId table guid.
     * @param        $request Request create request.
     *
     * @return JsonResponse created generator resource.
     */
    public function store(string $tableId, Request $request): JsonResponse
    {
        return response()->json($request, 201);
    }
}
