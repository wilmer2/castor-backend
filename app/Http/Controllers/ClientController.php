<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Client;

class ClientController extends Controller {

  public function store(Request $request) {
    $inputData = $request->all();

    $newClient = new Client($inputData);

    if($newClient->save()) {
        return response()->json($newClient);
    } else {
        return response()->validation_error($newClient->errors());
    }
  }

}
