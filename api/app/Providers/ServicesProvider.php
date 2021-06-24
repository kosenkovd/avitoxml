<?php


namespace App\Providers;

use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use App\Repositories\DictRepository;
use App\Services\AvitoService;
use App\Services\CronLockService;
use App\Services\GoogleDriveClientService;
use App\Services\Interfaces\IAvitoService;
use App\Services\Interfaces\IGoogleDriveClientService;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IYandexDiskService;
use App\Services\Interfaces\IYandexFileDownloader;
use App\Services\SpreadsheetClientService;
use App\Services\YandexDiskService;
use App\Services\YandexFileDownloader;
use Illuminate\Support\ServiceProvider;
use App\Services\Interfaces\IXmlGenerationService;
use App\Services\Interfaces\ISpintaxService;
use App\Services\Interfaces\IMailService;
use App\Services\XmlGenerationService;
use App\Services\SpintaxService;
use App\Services\MailService;
use JsonMapper;

class ServicesProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(IGoogleDriveClientService::class, function () {
            return new GoogleDriveClientService();
        });
        $this->app->bind(IYandexDiskService::class, function () {
            return new YandexDiskService();
        });
        $this->app->bind(ISpreadsheetClientService::class, function () {
            return new SpreadsheetClientService();
        });
        $this->app->bind(IXmlGenerationService::class, function () {
            return new XmlGenerationService(
                new SpreadsheetClientService(),
                new SheetNames(),
                new XmlGeneration(),
                new DictRepository()
            );
        });
        $this->app->bind(ISpintaxService::class, function () {
            return new SpintaxService();
        });
        $this->app->bind(IMailService::class, function () {
            return new MailService();
        });
        $this->app->bind(IYandexFileDownloader::class, function () {
            return new YandexFileDownloader();
        });
        $this->app->bind(JsonMapper::class, function () {
            return new JsonMapper();
        });
        $this->app->bind(IAvitoService::class, function () {
            return new AvitoService();
        });
        $this->app->singleton(CronLockService::class, function () {
            return new CronLockService();
        });
    }
}
