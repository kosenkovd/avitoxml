<?php

namespace App\Http\Controllers;

use App\Configuration\Config;
use App\Configuration\Spreadsheet\SheetNames;
use App\DTOs\ErrorResponse;
use App\Helpers\LinkHelper;
use App\Http\Resources\TableMarketplaceCollection;
use App\Http\Resources\TableMarketplaceResource;
use App\Models\GeneratorLaravel;
use App\Models\TableMarketplace;
use App\Models\UserLaravel;
use App\Services\Interfaces\IMailService;
use App\Services\Interfaces\ISpreadsheetClientService;
use DateTimeZone;
use Exception;
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
class TableMarketplaceController extends BaseController
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
    private Config $config;
    
    public function __construct(
        ISpreadsheetClientService       $spreadsheetClientService,
        IMailService                    $mailService,
        SheetNames                      $sheetNames,
        Config                          $config
    )
    {
        $this->spreadsheetClientService = $spreadsheetClientService;
        $this->mailService = $mailService;
        $this->sheetNamesConfig = $sheetNames;
        $this->roles = new Roles();
        $this->config = $config;
    }
    
    /**
     * GET /marketplaces
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
                $tables = TableMarketplace::query()
                    ->with('generators:id,tableMarketplaceId,generatorGuid,targetPlatform,maxAds,subscribedMaxAds,subscribed')
                    ->get();
                break;
            case $this->roles->Customer:
                $tables = $currentUser
                    ->tablesMarketplace()
                    ->with('generators:id,tableMarketplaceId,generatorGuid,targetPlatform,maxAds,subscribedMaxAds,subscribed')
                    ->get();
        }
        
        return response()->json(new TableMarketplaceCollection($tables), 200);
    }
    
    /**
     * GET /marketplaces/{$tableGuid}
     *
     * @param string $tableGuid table guid.
     *
     * @return JsonResponse json table resource.
     */
    public function show(string $tableGuid, Request $request): JsonResponse
    {
        return response()->json();
    }
    
    /**
     * POST /marketplaces?userId=int|null
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
        
        // No userId - Client creates Table for himself
        if (is_null($userId)) {
            if ($currentUser->tablesMarketplace->count() >= 1) {
                return response()->json(new ErrorResponse(Response::$statusTexts[403], 'TOO_MANY_MARKETPLACES'),
                    403
                );
            }
            
            $table = $this->createTable($currentUser->id);
            
            return response()->json(new TableMarketplaceResource($table), 201);
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
        
        $table = $this->createTable($user->id);
        
        return response()->json(new TableMarketplaceResource($table), 201);
    }
    
    /**
     * PUT /marketplaces/{$tableGuid}
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
        
        Log::channel('apilog')->info('PUT /marketplaces/'.$tableGuid.' - user '.$currentUser->id);
        
        /** @var TableMarketplace|null $existingTable */
        $existingTable = TableMarketplace::query()->firstWhere('tableGuid', $tableGuid);
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
     * DELETE /marketplaces/{$tableGuid}
     *
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
        
        Log::channel('apilog')->info('DELETE /marketplaces/'.$tableGuid.' - user '.$currentUser->id);
        
        if ($currentUser->roleId !== $this->roles->Admin) {
            return response()->json(new ErrorResponse(Response::$statusTexts[403]), 403);
        }
        
        /** @var TableMarketplace|null $table */
        $table = TableMarketplace::query()->where('tableGuid', $tableGuid)->first();
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
     *
     * @return TableMarketplace
     */
    private function createTable(int $userId): TableMarketplace
    {
        $googleTableId = $this->spreadsheetClientService->copyTableMarketplace();
    
        $dateExpired = Carbon::now(new DateTimeZone("Europe/Moscow"))
            ->setTime(0, 0)
            ->addDays(3)
            ->getTimestamp();
        
        /** @var TableMarketplace $table */
        $table = TableMarketplace::query()->make();
        $table->userId = $userId;
        $table->googleSheetId = $googleTableId;
        $table->dateExpired = $dateExpired;
        $table->tableGuid = Guid::uuid4()->toString();
        $table->dateLastModified = 0;
        
        $table->save();
        
        $newTableId = $table->id;
        
        $targetsToAdd = [
            [
                "cell" => "C4",
                "target" => $this->sheetNamesConfig->getOzon()
            ]
        ];
        
        foreach ($targetsToAdd as $target) {
            /** @var GeneratorLaravel $generator */
            $generator = GeneratorLaravel::query()->make();
            $generator->tableMarketplaceId = $newTableId;
            $generator->generatorGuid = Guid::uuid4()->toString();
            $generator->targetPlatform = $target['target'];
            $generator->maxAds = $this->config->getMaxAdsLimit();
            
            $generator->save();
            
            $this->spreadsheetClientService->updateCellContent(
                $googleTableId,
                $this->sheetNamesConfig->getInformation(),
                $target["cell"],
                LinkHelper::getXmlGeneratorLink($table->tableGuid, $generator->generatorGuid)
            );
        }
        
        $this->mailService->sendEmailWithTableDataMarketplace($table);
        
        return $table;
    }
}
