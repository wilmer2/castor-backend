<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Move;


class MoveController extends Controller {

  public function index() {
    $moves = Move::amountMove()->get();

    return $moves;
  } 
}