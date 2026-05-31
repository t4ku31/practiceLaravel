<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Http\Requests\StoreUserRequest;

class UserController extends Controller

{
    //コンストラクタインジェクション
    public function __construct( protected UserRepository $users)
    {

    }

    /**
     * Display a listing of the resource.
     */
    public function index(StoreUserRequest $request, /*int $id* urlパラメータはRequestの後ろに記述*/ )
    {
        //①リクエストされたURIを取得
        //$uri = $request->path();

        //②URIが 'users/*' にマッチするかを確認
        if($request->is(    'users/*')){ 
            return "ユーザーの詳細ページです。";
        }
       

        // $request->routeIs('users/*')も同様にURIが 'users/*' にマッチするかを確認できます。

        /* 
        いま、ユーザーが以下のURLにアクセスしているとします。
        [http://example.com/products?category=shoes&page=2](http://example.com/products?category=shoes&page=2)
            $request->url()は、クエリパラメータを除いたURLを返します。
            $request->fullUrl()
        */
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $validatedData = $request->validated();
        $hashedPassword = Hash::make($validatedData->password);
        
        $user = new User();
        $user->name = $validatedData->name;
        $user->email = $validatedData->email;
        $user->password = $hashedPassword;

        $isSuccess = $user->save();
       
        if($isSuccess){
            Auth::login($user);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
