<?php
namespace App\Authentication;
use Auth;
use App\Authentication\UserProvider;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Auth::provider('app_auth_provider', function($app, array $config) {
            $connection = $this->app['db']->connection();

            return new UserProvider($connection, $this->app['hash'], $config['table']);
        });
    }
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}