<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Auth;

class LoginController extends Controller {

  public function login(Request $request) {
    $data = $request->all();
    $authenticate = auth()->attempt(['email' => $data['email'], 'password' => $data['password']]);

    if($authenticate) {
        $user = currentUser();

        return response()->json($user);
    } else {
        return response()->validation_error('Credenciales no son válidas');
    }
  }

  public function logout(Request $request) {
    auth()->logout();

    return response()->json(['message' => 'Session Finalizada']);
  }
}
