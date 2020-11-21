<?php


namespace App\Providers;

use App\Services\GoogleServicesClient;
use App\Services\Interfaces\IGoogleServicesClient;
use App\Services\Interfaces\IXmlGenerationService;
use App\Services\XmlGenerationService;
use Illuminate\Support\ServiceProvider;

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
            return new XmlGenerationService(new GoogleServicesClient());
        });
    }
}
