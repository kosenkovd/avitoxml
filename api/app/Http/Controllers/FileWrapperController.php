<?php


namespace App\Http\Controllers;

use App\Repositories\Interfaces\ITableRepository;
use App\Services\Interfaces\IYandexFileDownloader;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;


/**
 * Class UserController
 *
 * Base route /api/tables/$tableId/
 *
 * @package App\Http\Controllers
 */
class FileWrapperController extends BaseController
{
    /**
     * @var IYandexFileDownloader Yandex Disk Service.
     */
    private IYandexFileDownloader $yandexDiskService;

    private ITableRepository $tableRespository;

    public function __construct(
        IYandexFileDownloader $yandexDiskService,
        ITableRepository $tableRepository)
    {
        $this->yandexDiskService = $yandexDiskService;
        $this->tableRespository = $tableRepository;
    }

    /**
     * yandexFile
     *
     * Get current user info.
     *
     * @param string $tableId table guid.
     * @param Request $request request.
     * @return Response current user information.
     */
    public function yandexFile(string $tableId, Request $request) : Response
    {
        $table = $this->tableRespository->get($tableId);
        if($table == null)
        {
            return response("Not found", 404);
        }

        $fileInfo = $request->query("fileInfo");
        $decodedFileInfo = base64_decode($fileInfo);

        $token = "";
        if(strpos($decodedFileInfo, "&&&") === false)
        {
            $fileID = $decodedFileInfo;
        }
        else
        {
            [$fileID, $token] = explode("&&&", $decodedFileInfo);
        }

        /*$fileID = mb_convert_encoding($fileID, "UTF8");
        var_dump($fileID);*/
        $token = $table->getYandexToken() != null
            ? $table->getYandexToken()
            : $token;

        $this->yandexDiskService->init($token);

        $explodedFileName = explode(".", $fileID);
        /*var_dump($explodedFileName);
        return response("asdf")->header("Content-Type", "text/html; charset=utf-8");*/
        $fileExtension = $explodedFileName[count($explodedFileName) - 1];

        return response($this->yandexDiskService->downloadFile($fileID), 200)
            ->header("Content-Type", "image/".$fileExtension);
    }
}
