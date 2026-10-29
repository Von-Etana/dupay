<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Laravel\Passport\Console\ClientCommand;
use Laravel\Passport\Console\InstallCommand;
use Laravel\Passport\Console\KeysCommand;
use Laravel\Passport\Passport;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Passport::routes();
        /*ADD THIS LINES*/
        $this->commands([
            InstallCommand::class,
            ClientCommand::class,
            KeysCommand::class,
        ]);

        // ==== BEGIN DeWallet admin hostname switch ====
        $dewalletHost = env('DEWALLET_ADMIN_HOST', 'newadmin.mydupay.com');

        // Load the dark (DeWallet) admin UI when on the dewallet host,
        // UNLESS the admin has switched to light mode via the dupay_theme cookie.
        if (! app()->runningInConsole()
            && request()->getHost() === $dewalletHost) {

            $theme = request()->cookie('dupay_theme', 'dark');
            View::share('dupayTheme', $theme);          // available in all views
            View::getFinder()->prependLocation(resource_path('views_dewallet'));
        }
        // ==== END DeWallet admin hostname switch ====
    }
}