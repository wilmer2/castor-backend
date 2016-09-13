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

  public function show($recordId) {
    $record = Record::findOrFail($recordId);

    return response()->json($record);
  }

  public function update(Request $request, $recordId) {
    $record = Record::findOrFail($recordId);
    $inputData = $request->all();

    if($record->update($inputData)) {
        return response()->json($record);
    } else {
        return response()->validation_error($record->errors());
    }
  }

}
