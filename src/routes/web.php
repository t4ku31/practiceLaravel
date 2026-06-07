<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\resources\TaskController;
use App\Http\Controllers\resources\UserController;
use Illuminate\Support\Facades\Route;

//　↓方法cで使います。
// use Illuminate\Support\Facades\View;

Route::get('/', function () {
    return view('welcome');
});
// userリソースに対するルーティングを自動生成
Route::resource('/users', UserController::class);

// 認証関連のルーティングをグループ化
Route::prefix('authentication')->name('authentication')->group(function () {
    Route::post('/userRegister', [AuthController::class, 'userRegister'])->name('.userRegister');
    Route::post('/userLogin', [AuthController::class, 'userLogin'])->name('.userLogin');
    Route::post('/userLogout', [AuthController::class, 'userLogout'])->name('.userLogout')->middleware('auth');
});

Route::get('/user/register', function () {
    return view('user.register');
});
Route::get('/user/login', function () {
    return view('user.login');
})->name('user.login');

Route::get('/dashboard', [TaskController::class, 'index'])->name('dashboard');

Route::resource('/tasks', TaskController::class)->middleware('auth');

// 方法A
// Route::get('/',function(){
//     return view('greeting.blade.php', ['name' => 'Takumi']);
// });

// 方法B
Route::view('/hello', 'greeting.blade.hello', ['nameWithHtml' => '<h1>Hello!,Takumi with html</h1>']);
Route::view('/good-night', 'greeting.blade.goodNight');

// 方法C　条件分岐
// Route::get('/greeting',function(){
//     return view::first(['greeting.blade.hello','greeting.blade.goodNight']);
// });
