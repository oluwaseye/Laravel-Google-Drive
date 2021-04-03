<?php

namespace App\Providers;

use Exception;
use Google_Client;
use Google_Service_Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;


class GoogleClientProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton("Google_Client", function ($app) {
            $client = new Google_Client();
            $client->setApplicationName(config("app.name"));
            // $client->setDeveloperKey(config("google.AppKey"));
            $client->setClientId(config("services.google.client_id"));
            $client->setClientSecret(config("services.google.client_secret"));
            $client->setRedirectUri(config("services.google.redirect"));
            $client->setScopes(Google_Service_Drive::DRIVE);
            $client->setRedirectUri(URL::current());
            $client->setAccessType('offline');
            $client->setIncludeGrantedScopes(true);
            return $client;
        });
    }
}
