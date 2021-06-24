<?php

namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Console\Jobs\FillImagesJobYandex;
use App\Console\Jobs\GenerateXMLJob;
use App\Console\Jobs\RandomizeTextJob;
use App\DTOs\ErrorResponse;
use App\DTOs\TableDTO;
use App\DTOs\UserDTO;
use App\Helpers\LinkHelper;
use App\Http\Resources\TableCollection;
use App\Http\Resources\TableDetailResource;
use App\Http\Resources\TableResource;
use App\Mappers\UserDTOMapper;
use App\Models\Generator;
use App\Models\GeneratorLaravel;
use App\Models\Table;
use App\Models\TableLaravel;
use App\Models\User;
use App\Models\UserLaravel;
use App\Repositories\TableRepository;
use App\Services\Interfaces\IGoogleDriveClientService;
use App\Services\Interfaces\IMailService;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IYandexDiskService;
use App\Services\SpintaxService;
use App\Services\SpreadsheetClientService;
use App\Services\XmlGenerationService;
use App\Services\YandexDiskService;
use DateTimeZone;
use Exception;
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
use Illuminate\Support\Collection;
use JsonMapper;
use Ramsey\Uuid\Guid\Guid;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class TableController
 * Base route /api/tables
 *
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
     * @var ISpreadsheetClientService Google Spreadsheet services client.
     */
    private ISpreadsheetClientService $spreadsheetClientService;
    
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
        ISpreadsheetClientService $spreadsheetClientService,
        IMailService $mailService,
        SheetNames $sheetNames
    )
    {
        $this->tableRepository = $tableRepository;
        $this->generatorRepository = $generatorRepository;
        $this->spreadsheetClientService = $spreadsheetClientService;
        $this->mailService = $mailService;
        $this->sheetNamesConfig = $sheetNames;
        $this->roles = new Roles();
    }
    
    /**
     * GET /tables
     * Get all table instances.
     *
     * @param Request $request
     *
     * @return JsonResponse all tables.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
    
        $tables = new Collection;
        switch ($currentUser->roleId) {
            case $this->roles->Admin:
                $tables = TableLaravel::query()
                    ->with('generators:id,tableId,generatorGuid,targetPlatform,maxAds')
                    ->get();
                break;
            case $this->roles->Customer:
                $tables = $currentUser->tables;
        }
        
        return response()->json(new TableCollection($tables), 200);
    }
    
    /**
     * GET /tables/{$tableGuid}
     *
     * @param string $tableGuid table guid.
     *
     * @return JsonResponse json table resource.
     */
    public function show(string $tableGuid, Request $request): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
        
        /** @var TableLaravel $table */
        $table = TableLaravel::query()->where('tableGuid', $tableGuid)->first();
        if (is_null($table)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        if (
            ($currentUser->roleId !== $this->roles->Admin) &&
            ($currentUser->id !== $table->userId)
        ) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        return response()->json(new TableDetailResource($table), 200);
    }
    
    /**
     * POST /tables
     * Create new table.
     *
     * @param $request Request create request.
     *
     * @return JsonResponse created table resource.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
        
        $request->validate(['userId' => 'int|nullable']);
        
        $userId = $request->input('userId');
    
        $maxAds = 10;
        $days = 3;
    
        // No userId - Client creates Table for himself
        if (is_null($userId)) {
            if ($currentUser->tables->count() >= 2) {
                return response()->json(new ErrorResponse(Response::$statusTexts[403], 'TOO_MANY_TABLES'),
                    403
                );
            }
            
            if (!$currentUser->tables->isEmpty()) {
                $maxAds = 0;
                $days = 0;
            }
            
            $tableLegacy = $this->createTable($currentUser->id, $maxAds, $days);
            /** @var TableLaravel $table */
            $table = TableLaravel::query()->find($tableLegacy->getTableId());
            
            return response()->json(new TableResource($table), 201);
        }
    
        if ($currentUser->roleId !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        // Admin send userId to create Table for Client
        /** @var UserLaravel|null $user */
        $user = UserLaravel::query()->find($userId);
        if (is_null($user)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        $tableLegacy = $this->createTable($user->id, $maxAds, $days);
        /** @var TableLaravel $table */
        $table = TableLaravel::query()->find($tableLegacy->getTableId());
        
        return response()->json(new TableResource($table), 201);
    }
    
    /**
     * PUT /tables/{$tableGuid}
     *
     * Update table.
     *
     * @param string  $tableGuid table guid.
     * @param Request $request   update request.
     *
     * @return JsonResponse updated table resource.
     */
    public function update(string $tableGuid, Request $request): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
    
        /** @var TableLaravel $existingTable */
        $existingTable = TableLaravel::query()->firstWhere('tableGuid', $tableGuid);
        if (is_null($existingTable)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        if ($currentUser->roleId !== $this->roles->Admin) {
            if ($currentUser->id !== $existingTable->userId) {
                return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
            }
    
            $request->validate(['tableNotes' => 'string|nullable']);
            $existingTable->update(['notes' => $request->input('tableNotes')]);
    
            return response()->json(null, 200);
        }
        
        $request->validate([
            'dateExpired' => 'int|required',
            'tableNotes' => 'string|nullable'
        ]);
        $existingTable->update([
            'notes' => $request->input('tableNotes'),
            'dateExpired' => $request->input('dateExpired')
        ]);
        
        return response()->json(null, 200);
    }
    
    /**
     * Put /tables/{tableGuid}/tokens
     *
     * @param string  $tableGuid
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateTokens(string $tableGuid, Request $request): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
        
        /** @var TableLaravel $table */
        $table = TableLaravel::query()->where('tableGuid', $tableGuid)->first();
        if (is_null($table)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        if (
            ($currentUser->roleId !== $this->roles->Admin) &&
            ($currentUser->id !== $table->userId)
        ) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        $request->validate([
            'yandexToken' => 'string|nullable',
            'avitoClientId' => 'string|nullable',
            'avitoClientSecret' => 'string|nullable',
            'avitoUserId' => 'string|nullable',
        ]);
        
        $input = collect($request->only([
            'yandexToken',
            'avitoClientId',
            'avitoClientSecret',
            'avitoUserId',
        ]))->filter()->all();
        
        if (isset($input['avitoUserId']) && $input['avitoUserId'] !== '') {
            $input['avitoUserId'] = preg_replace('/\s/i', "", $input['avitoUserId']);
        }
        
        $table->update($input);
        
        return response()->json(null, 200);
    }
    
    /**
     * DELETE /tables/{$tableGuid}
     * Delete table from BD and delete Generators with content
     *
     * @param string  $tableGuid table guid.
     * @param Request $request   delete request.
     *
     * @return JsonResponse deleted table resource.
     * @throws Exception
     */
    public function destroy(string $tableGuid, Request $request): JsonResponse
    {
        /** @var UserLaravel $currentUser */
        $currentUser = auth()->user();
        
        if ($currentUser->roleId !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        /** @var TableLaravel|null $table */
        $table = TableLaravel::query()->where('tableGuid', $tableGuid)->first();
        if (is_null($table)) {
            return response()->json(new ErrorResponse(Response::$statusTexts[404]), 404);
        }
        
        $table->generators->each(function (GeneratorLaravel $generator) {
            $generator->delete();
        });
        $table->delete();
        
        return response()->json(null, 200);
    }
    
    /**
     * Create table for specified user
     *
     * @param int $userId
     * @param int $maxAds
     * @param int $days
     *
     * @return Table
     */
    private function createTable(int $userId, int $maxAds, int $days): Table
    {
        $googleTableId = $this->spreadsheetClientService->copyTable();
    
        $dateExpired = Carbon::createFromTime(
            0,
            0,
            0,
            new DateTimeZone("Europe/Moscow")
        )
            ->addDays($days)
            ->getTimestamp();
        
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
                $maxAds
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
