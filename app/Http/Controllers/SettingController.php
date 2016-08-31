<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Setting;

class SettingController extends Controller {

  public function getSetting() {
    $setting = getSetting();

    return response()->json($setting);
  }

  public function update(Request $request) {
    $setting = getSetting();

    $inputData = $request->all();
    
    if($setting->update($inputData)) {
        return response()->json($setting);
    } else {
        return response()->validation_error($setting->errors());
    }
  }
}