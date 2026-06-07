<?php

namespace App\Providers;

use App\View\Composers\composerForGreeting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('userName', 'Takumi');

        // ① クラスベース：'profile' ビューが描画される際、ProfileComposer クラスを実行
        // View::composer('profile', ProfileComposer::class);

        // ② クラスベース: 複数ビューに適用する
        View::composer(['greeting.blade.hello', 'greeting.blade.goodNight'], composerForGreeting::class);

        // ② クロージャベース：'welcome' ビューが描画される際、この関数を直接実行
        View::composer('welcome', function ($view) {
            // $view->with(...);
        });
    }
}
