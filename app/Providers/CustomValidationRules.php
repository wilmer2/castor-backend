<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Validators\RentalValidator;
use Validator;

class CustomValidationRules extends ServiceProvider {

   /**
    * Bootstrap the application services.
    *
    * @return void
    */
   public function boot() {
      Validator::extend('date_hour', function ($attribute, $value, $paramaters, $validator) {
        $arrivalDate = array_get($validator->getData(), 'arrival_date', null);
        $reservation = array_get($validator->getData(), 'reservation', 0);
        $rentalValidator = new RentalValidator();
         
        if($reservation) {
            return $rentalValidator->isValidTime($arrivalDate, $value);
        } else {
           return true;
        }
        
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