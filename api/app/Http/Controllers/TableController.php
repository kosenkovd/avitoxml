<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

use App\Models;
use App\Repositories\Interfaces;

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
    private Interfaces\ITableRepository $tables;

    public function __construct(Interfaces\ITableRepository $tables)
    {
        parent::__construct();
        $this->tables = $tables;
    }

    /**
     * GET /
     *
     * Get all table instances.
     * @return JsonResponse all tables.
     */
    public function index() : JsonResponse
    {
        $tables = [];
        $table = new Models\Table();
        $table->ti = "pidor";
        $tables[] = $table;
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
