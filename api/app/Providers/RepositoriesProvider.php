<?php


namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories;
use App\Repositories\Interfaces;

class RepositoriesProvider extends ServiceProvider
{
    /**
     * Register repositories.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Interfaces\IUserRepository::class, function () {
            return new Repositories\UserRepository();
        });
        $this->app->bind(Interfaces\ITableRepository::class, function () {
            return new Repositories\TableRepository();
        });
        $this->app->bind(Interfaces\IGeneratorRepository::class, function () {
            return new Repositories\GeneratorRepository();
        });
        $this->app->bind(Interfaces\ITableUpdateLockRepository::class, function () {
            return new Repositories\TableUpdateLockRepository();
        });
    }
}
