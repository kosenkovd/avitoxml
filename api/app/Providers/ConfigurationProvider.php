<?php


namespace App\Providers;

use App\Configuration\Config;
use App\Configuration\Spreadsheet;
use App\Configuration\Spreadsheet\SheetNames;
use App\Configuration\XmlGeneration;
use Illuminate\Support\ServiceProvider;

class ConfigurationProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Config::class, function () {
            return new Config();
        });
        $this->app->bind(SheetNames::class, function () {
            return new SheetNames();
        });
        $this->app->bind(Spreadsheet::class, function () {
            return new Spreadsheet();
        });
        $this->app->bind(XmlGeneration::class, function () {
            return new XmlGeneration();
        });
    }
}
