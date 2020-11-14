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
 * Class GeneratorController
 * Base route /api/tables/$tableId/generators
 *
 * @package App\Http\Controllers
 */
class GeneratorController extends BaseController
{
    /**
     * @var Interfaces\ITableRepository Models\Table repository.
     */
    private Interfaces\ITableRepository $tables;

    /**
     * @var Interfaces\IGeneratorRepository Models\Generator repository.
     */
    private Interfaces\IGeneratorRepository $generators;

    public function __construct(
        Interfaces\ITableRepository $tables,
        Interfaces\IGeneratorRepository  $generators)
    {
        parent::__construct();
        $this->tables = $tables;
        $this->generators = $generators;
    }

    /**
     * GET /
     *
     * Get all generators instances for table.
     * @param string $tableId table guid.
     * @return JsonResponse all generators for table.
     */
    public function index(string $tableId) : JsonResponse
    {
        $tables = [];
        $table = new Models\Table();
        $table->ti = $tableId;
        $tables[] = $table;
        return response()->json($tables, 200);
    }

    /**
     * GET /$id
     *
     * Get generated XML file.
     * @param string $tableId table guid.
     * @param $id string generator guid.
     * @return generated XML.
     */
    public function show(string $tableId, string $id)
    {
        return response("$tableId, $id, XML", 200);
    }

    /**
     * POST /
     *
     * Create new generator for table.
     * @param string $tableId table guid.
     * @param $request Request create request.
     * @return JsonResponse created generator resource.
     */
    public function store(string $tableId, Request $request) : JsonResponse
    {
        return response()->json($request, 201);
    }
}
