<?php

namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Console\Jobs\FillAmountJob;
use App\Console\Jobs\FillImagesJobYandex;
use App\Console\Jobs\RandomizeTextJob;
use App\Helpers\LinkHelper;
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

    public function __construct(
        Interfaces\ITableRepository $tableRepository,
        Interfaces\IGeneratorRepository $generatorRepository,
        IGoogleDriveClientService $googleDriveClientService,
        ISpreadsheetClientService $spreadsheetClientService,
        IYandexDiskService $yandexDiskService,
        IMailService $mailService,
        SheetNames $sheetNames)
    {
        $this->tableRepository = $tableRepository;
        $this->generatorRepository = $generatorRepository;
        $this->googleDriveClientService = $googleDriveClientService;
        $this->spreadsheetClientService = $spreadsheetClientService;
        $this->yandexDiskService = $yandexDiskService;
        $this->mailService = $mailService;
        $this->sheetNamesConfig = $sheetNames;
        $this->roles = new Roles();
    }

    /**
     * GET /
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
            $tableDtos[] = TableDtoMapper::MapTableDTO($table, $user);
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
                $generator = new Models\Generator(
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
     * GET /$id
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
     * POST /
     *
     * Create new table.
     * @param $request Request create request.
     * @return JsonResponse created table resource.
     */
    public function store(Request $request) : JsonResponse
    {
        $user = $request->input("currentUser");

        $googleTableId = $this->spreadsheetClientService->copyTable();

        $table = new Models\Table(
            null,
            $user->getUserId(),
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

            $this->spreadsheetClientService->updateCellContent(
                $googleTableId,
                $this->sheetNamesConfig->getInformation(),
                $target["cell"],
                LinkHelper::getXmlGeneratorLink(
                    $table->getTableGuid(), $generator->getGeneratorGuid()),
                $table->getTableGuid()."sgen");
        }

        $this->mailService->sendEmailWithTableData($table);

        return response()->json(TableDtoMapper::MapTableDTO($table, $user), 201);
    }

    /**
     * PUT /$id
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
    
//    public function fillTable(Request $request)
//    {
//        $table = new Models\Table(
//            null,
//            1,
//            '1VJdo7mkIHk2I8D_fCol21sOSrVi6wuVmRz3NEvvLQe0',
//            null,
//            null,
//            null,
//            0,
//            null,
//            null,
//            'null',
//            time()
//        );
//
//        $fillAmountJob = new FillAmountJob(
//            new SpreadsheetClientService(),
//            new TableRepository(),
//            new XmlGeneration()
//        );
//        $fillAmountJob->start($table);
//
//        return response('', 200);
//    }
}
