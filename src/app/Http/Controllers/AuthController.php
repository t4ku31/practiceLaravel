<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function userRegister(StoreUserRequest $request)
    {
        $validatedData = $request->validated();
        $hashedPassword = Hash::make($validatedData['password']);

        $user = new User;
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->password = $hashedPassword;

        $isSuccess = $user->save();

        if ($isSuccess) {
            Auth::login($user);

            return redirect('/dashboard');
        }

        return redirect()->back()->withInput();
    }

    public function userLogin(LoginRequest $request)
    {
        $validatedData = $request->validated();
        Log::info('Login attempt', ['email' => $validatedData['email']]);
        $user = User::where('email', $validatedData['email'])->first();

        if (Auth::attempt(['email' => $validatedData['email'], 'password' => $validatedData['password']])) {
            Log::info('User logged in successfully.', ['user_id' => Auth::id()]);

            return redirect('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function userLogout(Request $request)
    {

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/user/login');

    }
}
