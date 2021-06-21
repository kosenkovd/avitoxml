<?php

namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Console\Jobs\FillAmountJob;
use App\Console\Jobs\FillImagesJobYandex;
use App\Console\Jobs\GenerateXMLJob;
use App\Console\Jobs\RandomizeTextJob;
use App\DTOs\ErrorResponse;
use App\DTOs\TableDTO;
use App\DTOs\UserDTO;
use App\Helpers\LinkHelper;
use App\Mappers\UserDTOMapper;
use App\Models\Generator;
use App\Models\Table;
use App\Models\User;
use App\Repositories\TableRepository;
use App\Services\Interfaces\IGoogleDriveClientService;
use App\Services\Interfaces\IMailService;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IYandexDiskService;
use App\Services\SpintaxService;
use App\Services\SpreadsheetClientService;
use App\Services\XmlGenerationService;
use App\Services\YandexDiskService;
use Http\Client\Exception\HttpException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

use App\Models;
use App\Mappers\TableDtoMapper;
use App\Repositories\Interfaces;
use App\Enums\Roles;
use Illuminate\Support\Carbon;
use JsonMapper;
use Ramsey\Uuid\Guid\Guid;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class TableController
 * Base route /api/tables
 * @package App\Http\Controllers
 */
class TableController extends BaseController
{
    /**
     * @var Interfaces\ITableRepository Models\Table repository.
     */
    private Interfaces\ITableRepository $tableRepository;
    
    /**
     * @var Interfaces\IUserRepository Models\User repository.
     */
    private Interfaces\IUserRepository $userRepository;

    /**
     * @var Interfaces\IGeneratorRepository Models\Generator repository.
     */
    private Interfaces\IGeneratorRepository $generatorRepository;

    /**
     * @var IGoogleDriveClientService Google Drive services client.
     */
    private IGoogleDriveClientService $googleDriveClientService;

    /**
     * @var ISpreadsheetClientService Google Spreadsheet services client.
     */
    private ISpreadsheetClientService $spreadsheetClientService;

    /**
     * @var IYandexDiskService Yandex Disk Service.
     */
    private IYandexDiskService $yandexDiskService;

    /**
     * @var IMailService Mail service.
     */
    private IMailService $mailService;

    /**
     * @var Roles Enum of roles.
     */
    private Roles $roles;

    /**
     * @var SheetNames configuration with sheet names.
     */
    private SheetNames $sheetNamesConfig;

	private JsonMapper $jsonMapper;

	public function __construct(
        Interfaces\ITableRepository $tableRepository,
        Interfaces\IUserRepository $userRepository,
        Interfaces\IGeneratorRepository $generatorRepository,
        IGoogleDriveClientService $googleDriveClientService,
        ISpreadsheetClientService $spreadsheetClientService,
        IYandexDiskService $yandexDiskService,
        IMailService $mailService,
        SheetNames $sheetNames,
		JsonMapper $jsonMapper
	)
    {
        $this->tableRepository = $tableRepository;
        $this->userRepository = $userRepository;
        $this->generatorRepository = $generatorRepository;
        $this->googleDriveClientService = $googleDriveClientService;
        $this->spreadsheetClientService = $spreadsheetClientService;
        $this->yandexDiskService = $yandexDiskService;
        $this->mailService = $mailService;
        $this->sheetNamesConfig = $sheetNames;
        $this->jsonMapper = $jsonMapper;
        $this->roles = new Roles();
    }

    /**
     * GET /tables
     *
     * Get all table instances.
     * @param Request $request
     * @return JsonResponse all tables.
     */
    public function index(Request $request) : JsonResponse
    {
        /** @var User $user */
        $user = $request->input("currentUser");
        $tables = [];

        switch ($user->getRoleId())
        {
            case $this->roles->Admin:
                $tables = $this->tableRepository->getTables();
                break;
            case $this->roles->Customer:
                $tables = $this->tableRepository->getTables($user->getUserId());
                break;
        }

        /** @var TableDTO[] $tableDtos */
        $tableDtos = [];
        foreach ($tables as $table)
        {
            $tableDtos[] = TableDtoMapper::mapModelToDTO($table, $user);
        }
        return response()->json($tableDtos, 200);
    }

    /**
     * GET /tables/fixGenerators
     *
     * Adds generators to all tables that miss one. Probably should be deleted, as it serves too specific problem, that should not be repeated.
     */
    public function fixGenerators()
    {
        $tables = $this->tableRepository->getGeneratorlessTables();
        $targetsToAdd = [
            [
                "cell" => "C3",
                "target" =>$this->sheetNamesConfig->getAvito()
            ],
            [
                "cell" => "C4",
                "target" =>$this->sheetNamesConfig->getYoula()
            ],
            [
                "cell" => "C5",
                "target" =>$this->sheetNamesConfig->getYandex()
            ]
        ];

        foreach ($tables as $table)
        {
            foreach ($targetsToAdd as $target)
            {
                $generator = new Generator(
                    null,
                    $table->getTableId(),
                    Guid::uuid4()->toString(),
                    0,
                    $target["target"],
                    30
                );

                $newGeneratorId = $this->generatorRepository->insert($generator);

                $table->addGenerator($generator->setGeneratorId($newGeneratorId));
            }
        }
    }

    /**
     * GET /tablex/{$id}
     *
     * Read table.
     * @param $id string table guid.
     * @return JsonResponse json table resource.
     */
    public function show(string $id, Request $request)
    {
        return response()->json('',200);
    }

    /**
     * POST /tables
     *
     * Create new table.
     * @param $request Request create request.
     * @return JsonResponse created table resource.
     */
    public function store(Request $request) : JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->input("currentUser");
        
    	if ($currentUser->getRoleId() !== $this->roles->Admin) {
    		return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
		}

    	if (is_null($request->query('userId'))) {
			$table = $this->createTable($currentUser->getUserId());

			return response()->json(TableDtoMapper::mapModelToDTO($table, $currentUser), 201);
		}

		$userId = (int)$request->query('userId');
    	$user = $this->userRepository->getUserById($userId);
		if (is_null($user)) {
			return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
		}

		$table = $this->createTable($user->getUserId());

        return response()->json(TableDtoMapper::mapModelToDTO($table, $user), 201);
    }

    /**
     * PUT /tables/{$tableGuid}
     *
     * Update table.
     * @param string $tableGuid table guid.
     * @param $request Request update request.
     * @return JsonResponse updated table resource.
     */
    public function update(string $tableGuid, Request $request) : JsonResponse
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
    
        /** @var TableDTO $tableDTO */
        try {
            $tableDTO = $this->jsonMapper->map($request->json(), new TableDTO());
        } catch (\Exception $e) {
            return response()->json(new ErrorResponse(Response::$statusTexts[400]), 400);
        }
        
        $existingTable->setDateExpired($tableDTO->dateExpired);
        $this->tableRepository->update($existingTable);
        
        return response()->json(null, 200);
    }
    
    /**
     * DELETE /tables/{$tableGuid}
     *
     * Delete table from google and BD and delete Generators with content
     * @param string $tableGuid table guid.
     * @param Request $request delete request.
     * @return JsonResponse deleted table resource.
     */
    public function destroy(string $tableGuid, Request $request): JsonResponse
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

		$this->tableRepository->delete($existingTable);
        
        $this->spreadsheetClientService->markAsDeleted($existingTable->getGoogleSheetId());

		return response()->json(null, 200);
	}
    
    /**
     * Create table for specified user
     *
     * @param int $userId
     * @return Table
     */
    private function createTable(int $userId): Table
	{
		$googleTableId = $this->spreadsheetClientService->copyTable();
        
        $dateExpired = Carbon::now()->addDays(3)->getTimestamp();

		$table = new Table(
			null,
			$userId,
			$googleTableId,
			null,
			null,
			$dateExpired,
			false,
			null,
			null,
			Guid::uuid4()->toString(),
			0,
			[]
		);

		$newTableId = $this->tableRepository->insert($table);

		$targetsToAdd = [
			[
				"cell" => "C3",
				"target" => $this->sheetNamesConfig->getAvito()
			],
			[
				"cell" => "C4",
				"target" => $this->sheetNamesConfig->getYoula()
			],
			[
				"cell" => "C5",
				"target" => $this->sheetNamesConfig->getYandex()
			]
		];

		$table->setTableId($newTableId);

		foreach ($targetsToAdd as $target) {
			$generator = new Generator(
				null,
				$newTableId,
				Guid::uuid4()->toString(),
				0,
				$target["target"],
                30
            );

			$newGeneratorId = $this->generatorRepository->insert($generator);

			$table->addGenerator($generator->setGeneratorId($newGeneratorId));

			$this->spreadsheetClientService->updateCellContent(
				$googleTableId,
				$this->sheetNamesConfig->getInformation(),
				$target["cell"],
				LinkHelper::getXmlGeneratorLink($table->getTableGuid(), $generator->getGeneratorGuid())
			);
		}

		$this->mailService->sendEmailWithTableData($table);

		return $table;
	}
}
