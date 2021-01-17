<?php


namespace App\Providers;

use App\Configuration\Spreadsheet\SheetNames;
use App\Services\GoogleDriveClientService;
use App\Services\Interfaces\IGoogleDriveClientService;
use App\Services\Interfaces\ISpreadsheetClientService;
use App\Services\Interfaces\IYandexDiskService;
use App\Services\SpreadsheetClientService;
use App\Services\YandexDiskService;
use Illuminate\Support\ServiceProvider;
use App\Services\Interfaces\IXmlGenerationService;
use App\Services\Interfaces\ISpintaxService;
use App\Services\Interfaces\IMailService;
use App\Services\XmlGenerationService;
use App\Services\SpintaxService;
use App\Services\MailService;

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
            return new XmlGenerationService(new SpreadsheetClientService(), new SheetNames());
        });
        $this->app->bind(ISpintaxService::class, function () {
            return new SpintaxService();
        });
        $this->app->bind(IMailService::class, function () {
            return new MailService();
        });
    }
}
