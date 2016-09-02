<?php

namespace App\Validators;

class ValidationException extends \Exception {
  protected $errors;
  protected $message;

  public function __construct($message, $errors) {
    $this->errors = $errors;
    
    parent::__construct($message);
  }


  public function getErrors() {
    return $this->errors;
  }
}