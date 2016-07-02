<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Routing\ResponseFactory;

class CustomResponsesProvider extends ServiceProvider {
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(ResponseFactory $factory) {
      $factory->macro('validation_error', function ($messageBag) use ($factory) {
        $errors = $messageBag->all();
        $jsonResponse = [
          'message' => $errors
        ];

        return $factory->make($jsonResponse, 400);
      });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
      //
    }

}