<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\resources\UserController;
use App\Http\Controllers\AuthController;
//　↓方法cで使います。
// use Illuminate\Support\Facades\View; 


Route::get('/', function () {
    return view('welcome');
});
// userリソースに対するルーティングを自動生成
Route::resource('/users', UserController::class);


Route::prefix('authentication')->name('authentication')->group(function () {
    Route::post('/userRegister', [AuthController::class, 'userRegister']);
    Route::post('/userLogin', [AuthController::class, 'userLogin']);
});

Route::get('/user/register',function(){
    return view('user.register');
});

Route::view('dashboard','dashboard')->middleware('auth');

Route::resource('/tasks', TaskController::class)->middleware('auth');

//方法A
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