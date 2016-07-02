<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Type;

class TypeController extends Controller {

  public function store(Request $request) {
    $inputData = $request->all();

    $newType = new Type($inputData);

    if($newType->save()) {
        return response()->json($newType);
    } else {
        return response()->validation_error($newType->errors());
    }
  }
}
