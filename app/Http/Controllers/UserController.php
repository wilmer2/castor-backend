<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\User;
use App\Models\Role;
use Validator;


class UserController extends Controller {
  private $validatorMessages = [
    'name.required' => 'El nombre es obligatorio',
    'email.required' => 'El correo es obligatorio',
    'email.email' => 'Debe ingresar un correo valido',
    'email.unique' => 'Ya existe un usuario registrado con ese correo',
    'password.required' => 'La contraseña es obligatoria'
  ];

  public function store(Request $request) {
    $data = $request->only('name', 'email', 'password');
    $role = $request->input('role');

    $validatorRules = [
      'name' => 'required',
      'email' => 'required|email|unique:users',
      'password' => 'required'
    ];

    $validator = Validator::make($data, $validatorRules, $this->validatorMessages);

    if($validator->fails()) {
        $errorsMessages = $validator->errors()->all();

        return response()->validation_error($errorsMessages);
    } else {
        $newUser = User::create([
          'name' => $data['name'],
          'email' => $data['email'],
          'password' => bcrypt($data['password'])
        ]);

        if($role == 1) {
           $newUser->roles()->attach(1);
           $newUser->roles()->attach(2);
        } else {
           $newUser->roles()->attach(2);
        }

        return response()->json($newUser);
    }
  }

  public function show(Request $request, $userId) {
    $user = User::findOrFail($userId);
    
    $user->loadRole();

    return response()->json($user);
  }

  public function update(Request $request, $userId) {
    $user = User::findOrFail($userId);

    $data = $request->only('name', 'email');
    $role = $request->input('role');
    $newPassword = $request->input('password');
    $newRole = $request->input('role');

    $validatorRules = [
      'name' => 'required',
      'email' => 'required|email|unique:users,email,'. $userId,
    ];

    $validator = Validator::make($data, $validatorRules, $this->validatorMessages);

     if($validator->fails()) {
        $errorsMessages = $validator->errors()->all();

        return response()->validation_error($errorsMessages);
    } else {
        $user->update($data);

        if(strlen($newPassword) >= 4) {
            $user->password = bcrypt($newPassword);
            $user->save();
        }

        if($newRole == 1 and !$user->hasRole('admin')) {
            $user->roles()->attach(1);
        } else if($newRole == 2) {
            $user->roles()->detach(1);
        }

        $user->loadRole();
        
        return response()->json($user);
    }
  }

  
}