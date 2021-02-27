<?php


namespace App\Services;


use App\Services\Interfaces\IYandexFileDownloader;
use \Leonied7\Yandex\Disk;

class YandexFileDownloader implements IYandexFileDownloader
{
    private Disk $disk;

    /**
     * @inheritDoc
     */
    public function init(string $token): void
    {
        $this->disk = new Disk($token);
    }

    /**
     * @inheritDoc
     */
    public function downloadFile(string $fileID)
    {
        $file = $this->disk->file($fileID);
        $file->download(); //bool
        // получение последнего результата запроса
        $result = Disk\Collection\ResultList::getInstance()->getLast();
        return $result->getActualResult();
    }
}
