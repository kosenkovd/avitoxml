<?php

namespace App\Http\Controllers;

use App\Configuration\Spreadsheet;
use App\Configuration\Spreadsheet\SheetNames;
use App\Console\Jobs\FillImagesJob;
use App\Console\Jobs\RandomizeTextJob;
use App\Console\Jobs\TriggerSpreadsheetJob;
use App\Helpers\LinkHelper;
use App\Repositories\TableRepository;
use App\Services\GoogleServicesClient;
use App\Services\Interfaces\IGoogleServicesClient;
use App\Services\Interfaces\IMailService;
use App\Services\SpintaxService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

use App\Models;
use App\Mappers\TableDtoMapper;
use App\Repositories\Interfaces;
use App\Enums\Roles;
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
     * @var IGoogleServicesClient Google services client.
     */
    private IGoogleServicesClient $googleServicesClient;

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

    public function __construct(
        Interfaces\ITableRepository $tableRepository,
        Interfaces\IGeneratorRepository $generatorRepository,
        IGoogleServicesClient $googleServicesClient,
        IMailService $mailService,
        SheetNames $sheetNames)
    {
        $this->tableRepository = $tableRepository;
        $this->generatorRepository = $generatorRepository;
        $this->googleServicesClient = $googleServicesClient;
        $this->mailService = $mailService;
        $this->sheetNamesConfig = $sheetNames;
        $this->roles = new Roles();
    }

    /**
     * GET /
     *
     * Get all table instances.
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
            $tableDtos[] = TableDtoMapper::MapTableDTO($table, $user);
        }
        return response()->json($tableDtos, 200);
    }

    /**
     * GET /$id
     *
     * Read table.
     * @param $id string table guid.
     * @return JsonResponse json table resource.
     */
    public function show(string $id)
    {
        $table = $this->tableRepository->get($id);
        $service = new FillImagesJob(
            new GoogleServicesClient(),
            new TableRepository()
        );

        $spintaxService = new RandomizeTextJob(
            new SpintaxService(),
            new GoogleServicesClient(),
            new TableRepository());

        $triggerService = new TriggerSpreadsheetJob(
            new GoogleServicesClient(),
            new Spreadsheet());

        $triggerService->start();

        if(is_null($table))
        {
            echo $id;
        }
        else
        {
            //$service->start($table);
            //$spintaxService->start($table);
        }

        return response("", 200);
    }

    /**
     * POST /
     *
     * Create new table.
     * @param $request Request create request.
     * @return JsonResponse created table resource.
     */
    public function store(Request $request) : JsonResponse
    {
        $user = $request->input("currentUser");

        [$googleTableId, $googleFolderId] = $this->googleServicesClient->createTableInfrastructure();
        $table = new Models\Table(
            null,
            $user->getUserId(),
            $googleTableId,
            $googleFolderId,
            null,
            false,
            null,
            null,
            Guid::uuid4()->toString(),
            []
        );

        $newTableId = $this->tableRepository->insert($table);

        $targetsToAdd = [
            [
                "cell" => "C3",
                "target" =>$this->sheetNamesConfig->getAvito()
            ],
            [
                "cell" => "C4",
                "target" =>$this->sheetNamesConfig->getYandex()
            ],
            [
                "cell" => "C5",
                "target" =>$this->sheetNamesConfig->getYoula()
            ]
        ];

        $table->setTableId($newTableId);

        foreach ($targetsToAdd as $target)
        {
            $generator = new Models\Generator(
                null,
                $newTableId,
                Guid::uuid4()->toString(),
                0,
                $target["target"]);

            $newGeneratorId = $this->generatorRepository->insert($generator);

            $table->addGenerator($generator->setGeneratorId($newGeneratorId));

            $this->googleServicesClient->updateSpreadsheetCellsRange(
                $googleTableId,
                $this->sheetNamesConfig->getInformation()."!".$target["cell"].":".$target["cell"],
                [[LinkHelper::getXmlGeneratorLink(
                    $table->getTableGuid(), $generator->getGeneratorGuid())]],
                [ 'valueInputOption' => 'RAW' ]
            );
        }

        $this->googleServicesClient->updateSpreadsheetCellsRange(
            $googleTableId,
            $this->sheetNamesConfig->getInformation()."!F7:F7",
            [[LinkHelper::getGoogleDriveFolderLink($table->getGoogleDriveId())]],
            [ 'valueInputOption' => 'RAW' ]
        );

        $this->mailService->sendEmailWithTableData($table);

        return response()->json(TableDtoMapper::MapTableDTO($table, $user), 201);
    }

    /**
     * PUT /$id
     *
     * Update table.
     * @param $id table guid.
     * @param $request Request update request.
     * @return JsonResponse updated table resource.
     */
    public function update(string $id, Request $request) : JsonResponse
    {
        return response()->json($request, 200);
    }
}
