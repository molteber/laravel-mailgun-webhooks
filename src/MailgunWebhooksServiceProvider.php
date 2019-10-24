<?php

namespace Puz\MailgunWebhooks;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MailgunWebhooksServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mailgun-webhooks.php', 'mailgun-webhooks');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/mailgun-webhooks.php' => config_path('mailgun-webhooks.php'),
            ], 'config');
        }

        Route::macro('mailgunWebhooks', function ($url) {
            return Route::post($url, '\Puz\MailgunWebhooks\MailgunWebhooksController');
        });
    }
}
