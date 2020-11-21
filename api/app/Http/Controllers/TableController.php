<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\IGoogleServicesClient;
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
     * @var Roles Enum of roles.
     */
    private Roles $roles;

    public function __construct(
        Interfaces\ITableRepository $tableRepository,
        Interfaces\IGeneratorRepository $generatorRepository,
        IGoogleServicesClient $googleServicesClient)
    {
        $this->tableRepository = $tableRepository;
        $this->generatorRepository = $generatorRepository;
        $this->googleServicesClient = $googleServicesClient;
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
    public function show(string $id) : JsonResponse
    {
        return response()->json([$id], 200);
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


        $generator = new Models\Generator(
            null,
            $newTableId,
            Guid::uuid4()->toString(),
            0
        );

        $newGeneratorId = $this->generatorRepository->insert($generator);

        $table->setTableId($newTableId)->addGenerator($generator->setGeneratorId($newGeneratorId));

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
