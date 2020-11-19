<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

use App\Models;
use App\Repositories\Interfaces;
use App\Enums\Roles;

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
     * @var Roles Enum of roles.
     */
    private Roles $roles;

    public function __construct(Interfaces\ITableRepository $tableRepository)
    {
        $this->tableRepository = $tableRepository;
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

        return response()->json($tables, 200);
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
        $table = new Models\Table();
        $table->ti = "pidor";
        return response()->json($table, 200);
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
        return response()->json($request, 201);
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
