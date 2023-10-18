<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class CustomValidationMessagesProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Cargar los mensajes personalizados
        Validator::replacer('custom_messages', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, $message);
        });
    }
}