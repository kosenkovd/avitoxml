<?php

namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\DTOs\ErrorResponse;
use App\Helpers\LinkHelper;
use App\Http\Resources\TableCollection;
use App\Http\Resources\TableDetailResource;
use App\Http\Resources\TableResource;
use App\Models\GeneratorLaravel;
use App\Models\TableLaravel;
use App\Models\UserLaravel;
use App\Services\Interfaces\IMailService;
use App\Services\Interfaces\ISpreadsheetClientService;
use DateTimeZone;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use App\Enums\Roles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Guid\Guid;

/**
 * Class TableController
 * Base route /api/tables
 *
 * @package App\Http\Controllers
 */
class TableController extends BaseController
{
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
        ISpreadsheetClientService $spreadsheetClientService,
        IMailService $mailService,
        SheetNames $sheetNames
    )
    {
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
            case $this->roles->Service:
                $tables = TableLaravel::query()
                    ->with('generators:id,tableId,generatorGuid,targetPlatform,maxAds,subscribedMaxAds,subscribed')
                    ->get();
                break;
            case $this->roles->Customer:
                $tables = $currentUser->tables()
                    ->with('generators:id,tableId,generatorGuid,targetPlatform,maxAds,subscribedMaxAds,subscribed')
                    ->get();
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
            $hasZeroTables = $currentUser->tables()
                ->whereHas('generators', function (Builder $q) {
                    $q->where('maxAds', '<', 500);
                })
                ->exists();
            if (
                !$currentUser->tables->isEmpty() &&
                $hasZeroTables
            ) {
                return response()->json(new ErrorResponse(Response::$statusTexts[403], 'TOO_MANY_TABLES'),
                    403
                );
            }
            
            // Only first created table have max ads and days
            if (!$currentUser->tables->isEmpty()) {
                $maxAds = 0;
                $days = 0;
            }
    
            $table = $this->createTable($currentUser->id, $maxAds, $days);
            
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
    
        $table = $this->createTable($user->id, $maxAds, $days);

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
    
        Log::channel('apilog')->info('PUT /tables/'.$tableGuid.' - user '.$currentUser->id);
        
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
        
        if ($request->input('dateExpired') < time()) {
            $existingTable->generators()->update([
                'subscribed' => 0
            ]);
        }
        
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
    
        Log::channel('apilog')->info('PUT /tables/'.$tableGuid.'/tokens - user '.$currentUser->id);
        
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
        ]))
            ->filter()
            ->map(function (string $value): string {
                return preg_replace('/\s/i', "", trim($value));
            })
            ->all();
        
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
    
        Log::channel('apilog')->info('DELETE /tables/'.$tableGuid.' - user '.$currentUser->id);
        
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
     * @return TableLaravel
     */
    private function createTable(int $userId, int $maxAds, int $days): TableLaravel
    {
        $googleTableId = $this->spreadsheetClientService->copyTable();
    
        $dateExpired = Carbon::now(new DateTimeZone("Europe/Moscow"))
            ->setTime(0, 0)
            ->addDays($days)
            ->getTimestamp();
        
        /** @var TableLaravel $table */
        $table = TableLaravel::query()->make();
        $table->userId = $userId;
        $table->googleSheetId = $googleTableId;
        $table->dateExpired = $dateExpired;
        $table->tableGuid = Guid::uuid4()->toString();
        $table->dateLastModified = 0;
    
        $table->save();
    
        $newTableId = $table->id;
        
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
        
        foreach ($targetsToAdd as $target) {
            /** @var GeneratorLaravel $generator */
            $generator = GeneratorLaravel::query()->make();
            $generator->tableId = $newTableId;
            $generator->generatorGuid = Guid::uuid4()->toString();
            $generator->targetPlatform = $target['target'];
            $generator->maxAds = $maxAds;
            $generator->dateLastGenerated = 0;
    
            $generator->save();
            
            $this->spreadsheetClientService->updateCellContent(
                $googleTableId,
                $this->sheetNamesConfig->getInformation(),
                $target["cell"],
                LinkHelper::getXmlGeneratorLink($table->tableGuid, $generator->generatorGuid)
            );
        }
    
        $this->mailService->sendEmailWithTableDataLaravel($table);
        
        return $table;
    }
}
