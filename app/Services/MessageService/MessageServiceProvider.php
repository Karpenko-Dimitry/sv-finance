<?php

namespace App\Services\MessageService;

use App\Services\Exceptions\AlreadyRegisteredActionException;
use Illuminate\Support\ServiceProvider;

class MessageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('message', function () {
            return new MessageService();
        });
    }

    /**
     * @return void
     * @throws AlreadyRegisteredActionException
     */
    public function boot()
    {
        MessageService::setActions(get_objects_in_directory(
            app_path('Services/MessageService/Actions'),
            'App\\Services\\MessageService\\Actions\\'
        ), false);
    }
}
