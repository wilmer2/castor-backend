<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Rental;
use App\Models\Record;

class RecordController extends Controller {

  public function store(Request $request, $rentalId) {
    $rental = Rental::findOrFail($rentalId);
    $inputData = $request->all();
    $newRecord = new Record($inputData);

    $newRecord->rental_id = $rental->id;

    if($newRecord->save()) {
        return response()->json($newRecord);
    } else {
        return response()->validation_error($newRecord->errors());
    }
  }
}
