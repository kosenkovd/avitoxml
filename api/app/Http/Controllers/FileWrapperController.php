<?php


namespace App\Http\Controllers;

use App\Configuration\Spreadsheet\SheetNames;
use App\Services\Interfaces\IYandexDiskService;
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
     * @var IYandexDiskService Yandex Disk Service.
     */
    private IYandexDiskService $yandexDiskService;

    public function __construct(
        IYandexDiskService $yandexDiskService)
    {
        $this->yandexDiskService = $yandexDiskService;
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
        $fileInfo = $request->query("fileInfo");
        $decodedFileInfo = base64_decode($fileInfo);
        if(strpos($decodedFileInfo, "&&&") === false)
        {
            $decodedFileInfo = base64_decode(urldecode($fileInfo));
        }
        [$fileID, $token] = explode("&&&", $decodedFileInfo);
        $this->yandexDiskService->init($token);

        $explodedFileName = explode(".", $fileID);
        $fileExtension = $explodedFileName[count($explodedFileName) - 1];

        return response($this->yandexDiskService->downloadFile($fileID), 200)
            ->header("Content-Type", "image/".$fileExtension);
    }
}
