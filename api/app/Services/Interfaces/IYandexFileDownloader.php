<?php


namespace App\Services\Interfaces;


interface IYandexFileDownloader
{
    /**
     * Init Yandex Disk
     *
     * @param string $token      yandex disk token
     */
    public function init(string $token): void;

    /**
     * Download file by its identifier.
     *
     * @param string $fileID file identifier.
     * @return mixed|null file content.
     */
    public function downloadFile(string $fileID);
}
