<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Move;


class MoveController extends Controller {

  public function moves($startDate, $endDate) {
    $moves = Move::amountMove()
    ->whereBetween('date', array($startDate, $endDate))
    ->get();

    return response()->json($moves);
  }
}