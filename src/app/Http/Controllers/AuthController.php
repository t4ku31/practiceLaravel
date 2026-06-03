<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function userRegister(StoreUserRequest $request)
    {
        $validatedData = $request->validated();
        $hashedPassword = Hash::make($validatedData['password']);

        $user = new User();
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->password = $hashedPassword;

        $isSuccess = $user->save();
       
        if($isSuccess){
            Auth::login($user);
            return redirect('/dashboard');
        }

        return redirect()->back()->withInput();
    }


    public function userLogin(){}
}
