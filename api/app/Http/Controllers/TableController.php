<?php

namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Console\Jobs\FillAmountJob;
use App\Console\Jobs\FillImagesJobYandex;
use App\Console\Jobs\RandomizeTextJob;
use App\DTOs\ErrorResponse;
use App\DTOs\TableDTO;
use App\DTOs\UserDTO;
use App\Helpers\LinkHelper;
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
use App\Services\YandexDiskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

use App\Mappers\TableDtoMapper;
use App\Repositories\Interfaces;
use App\Enums\Roles;
use JsonMapper;
use Ramsey\Uuid\Guid\Guid;

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

	/**
	 * @var User authenticated user through query hash
	 */
	private User $currentUser;

	private JsonMapper $jsonMapper;

	public function __construct(
        Interfaces\ITableRepository $tableRepository,
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
        $this->generatorRepository = $generatorRepository;
        $this->googleDriveClientService = $googleDriveClientService;
        $this->spreadsheetClientService = $spreadsheetClientService;
        $this->yandexDiskService = $yandexDiskService;
        $this->mailService = $mailService;
        $this->sheetNamesConfig = $sheetNames;
        $this->jsonMapper = $jsonMapper;
        $this->roles = new Roles();

        $this->currentUser = request()->input("currentUser");
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
                    $target["target"]);

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
        $table = $this->tableRepository->get($id);

        $yaService = new FillImagesJobYandex(
            new SpreadsheetClientService(),
            new YandexDiskService(),
            new TableRepository(),
            new XmlGeneration());
        $yaService->start($table);

//        $service = new FillImagesJob(
//            new SpreadsheetClientService(),
//            new GoogleDriveClientService()
//        );
//
        /*$spintaxService = new RandomizeTextJob(
            new SpintaxService(),
            new SpreadsheetClientService(),
            new XmlGeneration());
        $spintaxService->start($table);*/

//        $triggerService = new TriggerSpreadsheetJob(
//            new SpreadsheetClientService(),
//            new Spreadsheet()
//        );

//        $triggerService->start();

//        if(is_null($table))
//        {
//            echo $id;
//        }
//        else
//        {
//            //$service->start($table);
//            $yaService->start($table);
//            //$spintaxService->start($table);
//        }

//        $client = new YandexDiskService();
//        $client->init("AgAAAAAMMp_iAAbO9-TN2FLhf0a7kQr5Ju2mlII");
//        $resp = $client->listFolderImages("Виджеты");
//        dd($resp);
        return response("", 200);
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
        $this->createTable($this->currentUser->getUserId());
        // work in progress
        die();
        
    	if ($this->currentUser->getRoleId() !== $this->roles->Admin) {
    		return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
		}

        /** @var UserDTO[] $userDTOs */
    	$userDTOs = [];
    	foreach ($request->json('users') as $user) {
    		try {
				$userDTOs[] = $this->jsonMapper->map($request->json(), new UserDTO());
			} catch (\Exception $exception) {
    			return response()->json(new ErrorResponse(Response::$statusTexts[400]), 400);
			}
        }
    
    	/** @var TableDTO[] $tableDTOs */
        $tableDTOs = [];
    	foreach ($userDTOs as $userDTO) {
            $table = $this->createTable($userDTO->userId);
            $tableDTOs[] = TableDtoMapper::mapModelToDTO($table, $user);
        }
    
        return response()->json($tableDTOs, 201);
    }

    /**
     * PUT /tables/{$id}
     *
     * Update table.
     * @param string $id table guid.
     * @param $request Request update request.
     * @return JsonResponse updated table resource.
     */
    public function update(string $id, Request $request) : JsonResponse
    {
        return response()->json($request, 200);
    }
    
    /**
     * DELETE /tables/{$id}
     *
     * Delete table from google and BD and delete Generators with content
     * @param string $id table guid.
     * @param Request $request delete request.
     * @return JsonResponse deleted table resource.
     */
    public function destroy(string $id, Request $request): JsonResponse
	{
	    return response()->json($request, 200);
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

		$table = new Table(
			null,
			$userId,
			$googleTableId,
			null,
			null,
			null,
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
				$target["target"]);

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
