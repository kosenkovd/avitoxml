<?php


namespace App\Providers;

use App\Configuration\Spreadsheet\SheetNames;
use Illuminate\Support\ServiceProvider;
use App\Services\Interfaces\IGoogleServicesClient;
use App\Services\Interfaces\IXmlGenerationService;
use App\Services\Interfaces\ISpintaxService;
use App\Services\Interfaces\IMailService;
use App\Services\GoogleServicesClient;
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
        $this->app->bind(IGoogleServicesClient::class, function () {
            return new GoogleServicesClient();
        });
        $this->app->bind(IXmlGenerationService::class, function ($app) {
            return new XmlGenerationService(new GoogleServicesClient(), new SheetNames());
        });
        $this->app->bind(ISpintaxService::class, function () {
            return new SpintaxService();
        });
        $this->app->bind(IMailService::class, function () {
            return new MailService();
        });
    }
}
