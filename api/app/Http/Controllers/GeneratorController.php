<?php


namespace App\Http\Controllers;

use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IXmlGenerationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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
    private Interfaces\ITableRepository $tablesRepository;

    /**
     * @var Interfaces\IGeneratorRepository Models\Generator repository.
     */
    private Interfaces\IGeneratorRepository $generatorsRepository;

    /**
     * @var IXmlGenerationService XML generator for spreadsheet.
     */
    private IXmlGenerationService $xmlGenerator;

    /**
     * @var ISpreadsheetClientService Google Spreadsheet client.
     */
    private ISpreadsheetClientService $spreadsheetClientService;
    
    public function __construct(
        Interfaces\ITableRepository $tablesRepository,
        Interfaces\IGeneratorRepository  $generatorsRepository,
        IXmlGenerationService $xmlGenerator,
        ISpreadsheetClientService $spreadsheetClientService
    )
    {
        $this->tablesRepository = $tablesRepository;
        $this->generatorsRepository = $generatorsRepository;
        $this->xmlGenerator = $xmlGenerator;
        $this->spreadsheetClientService = $spreadsheetClientService;
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
        $table->setTableId($tableId);
        $tables[] = $table;
        return response()->json($tables, 200);
    }

    /**
     * GET /$id
     *
     * Get generated XML file.
     * @param $tableGuid string table guid.
     * @param $generatorGuid string generator guid.
     * @return Response generated XML.
     */
    public function show(string $tableGuid, string $generatorGuid) : Response
    {
        $table = $this->tablesRepository->get($tableGuid);
        if(is_null($table))
        {
            return response("Table not found", 404);
        }
        $generator = null;
        foreach ($table->getGenerators() as $curGenerator)
        {
            if(strcmp($curGenerator->getGeneratorGuid(), $generatorGuid) == 0)
            {
                $generator = $curGenerator;
                break;
            }
        }
        if(is_null($generator))
        {
            return response("File not found", 404);
        }

        $content = "";

        try
        {
            $timeModified = $this->spreadsheetClientService->getFileModifiedTime($table->getGoogleSheetId());
        }
        catch (Exception $e)
        {
            $content = $this->generatorsRepository->getLastGeneration($generator->getGeneratorId());

            return response($content, 200)
                ->header("Content-Type", "application/xml");
        }

        $toLoadLastGeneration = $table->isDeleted() ||
            is_null($timeModified) ||
            (!is_null($table->getDateExpired()) && $table->getDateExpired() < time()) ||
            ($generator->getLastGenerated() > $timeModified->getTimestamp());
        if($toLoadLastGeneration)
        {
            $content = $this->generatorsRepository->getLastGeneration($generator->getGeneratorId());
        }
        else
        {
            try
            {
                $content = $this->xmlGenerator->generateAvitoXML(
                    $table->getGoogleSheetId(), $generator->getTargetPlatform());
                $generator->setLastGenerated(time());
                $this->generatorsRepository->update($generator);
                $this->generatorsRepository->setLastGeneration($generator->getGeneratorId(), $content);
            }
            catch(Exception $e)
            {
                $content = $this->generatorsRepository->getLastGeneration($generator->getGeneratorId());
            }
        }

        return response($content, 200)
            ->header("Content-Type", "application/xml");
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
