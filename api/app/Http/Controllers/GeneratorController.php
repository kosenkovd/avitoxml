<?php


namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\DTOs\ErrorResponse;
use App\DTOs\GeneratorDTO;
use App\Enums\Roles;
use App\Models\User;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IXmlGenerationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

use App\Models;
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
    private IXmlGenerationService $xmlGenerator;

    /**
     * @var ISpreadsheetClientService Google Spreadsheet client.
     */
    private ISpreadsheetClientService $spreadsheetClientService;

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
        Interfaces\IGeneratorRepository  $generatorsRepository,
		Interfaces\IUserRepository  $userRepository,
        IXmlGenerationService $xmlGenerator,
        ISpreadsheetClientService $spreadsheetClientService,
        SheetNames $sheetNames,
        JsonMapper $jsonMapper
    )
    {
        $this->tableRepository = $tableRepository;
        $this->generatorsRepository = $generatorsRepository;
        $this->userRepository = $userRepository;
        $this->xmlGenerator = $xmlGenerator;
        $this->spreadsheetClientService = $spreadsheetClientService;
        $this->sheetNamesConfig = $sheetNames;
        $this->jsonMapper = $jsonMapper;
        $this->roles = new Roles();
    }

    /**
     * GET /
     *
     * Get all generators instances for table.
     * @param string $tableId table guid.
     * @return JsonResponse all generators for table.
     */
    public function index(string $tableId) : JsonResponse
    {
        $tables = [];
        $table = new Models\Table();
        $table->setTableId($tableId);
        $tables[] = $table;
        return response()->json($tables, 200);
    }

    /**
     * GET /tables/$tableGuid/generators/$generatorGuid
     *
     * Get generated XML file.
     * @param $tableGuid string table guid.
     * @param $generatorGuid string generator guid.
     * @return Response generated XML.
     */
    public function show(string $tableGuid, string $generatorGuid) : Response
    {
        $table = $this->tableRepository->get($tableGuid);
        if(is_null($table))
        {
            return response(Response::$statusTexts[404], 404);
        }

		$user = $this->userRepository->getUserById($table->getUserId());
		if (is_null($user)) {
			Log::channel('fatal')
				->error("Error on '".$table->getGoogleSheetId()."' table have no user!");
			return response(Response::$statusTexts[500], 500);
		}

		if ($user->isBlocked()) {
			return response('User is blocked', 403);
		}

        $generator = null;
        foreach ($table->getGenerators() as $curGenerator)
        {
            if(strcmp($curGenerator->getGeneratorGuid(), $generatorGuid) == 0)
            {
                $generator = $curGenerator;
                break;
            }
        }
        if(is_null($generator))
        {
            return response(Response::$statusTexts[404], 404);
        }
    
        $content = $this->generatorsRepository->getLastGeneration($generator->getGeneratorId());
    
        return response($content, 200)
            ->header("Content-Type", "application/xml");
    }
    
    /**
     * PUT /tables/$tableGuid/generators/$generatorGuid
     *
     * Update table.
     * @param $request Request update request.
     * @param string $tableGuid table guid.
     * @param string $generatorGuid
     * @return JsonResponse updated table resource.
     */
    public function update(Request $request, string $tableGuid, string $generatorGuid) : JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->input("currentUser");
    
        if ($currentUser->getRoleId() !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
    
        $existingTable = $this->tableRepository->get($tableGuid);
        if (is_null($existingTable)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
    
        $existingGenerator = $this->generatorsRepository->get($generatorGuid);
        if (is_null($existingGenerator)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
    
        /** @var GeneratorDTO $generatorDTO */
        try {
            $generatorDTO = $this->jsonMapper->map($request->json(), new GeneratorDTO());
        } catch (\Exception $e) {
            return response()->json(new ErrorResponse(Response::$statusTexts[400]), 400);
        }

        $existingGenerator->setMaxAds($generatorDTO->maxAds);
        $this->generatorsRepository->update($existingGenerator);
        
        return response()->json(null, 200);
    }

    /**
     * POST /
     *
     * Create new generator for table.
     * @param string $tableId table guid.
     * @param $request Request create request.
     * @return JsonResponse created generator resource.
     */
    public function store(string $tableId, Request $request) : JsonResponse
    {
        return response()->json($request, 201);
    }
}
