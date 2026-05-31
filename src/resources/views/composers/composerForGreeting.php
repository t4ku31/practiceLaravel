<?php

namespace App\View\Composers;

use App\Repositories\UserRepository;
use Illuminate\View\View;

class composerForGreeting
{
    // サービスコンテナによって、必要な依存クラス（UserRepository）が自動で注入されます
    public function __construct(
        protected UserRepository $users,
    ) {}

    /**
     * ビューにデータを結合する
     */
    public function compose(View $view): void
    {
        // ビューに 'count' という変数名でデータを渡す
        $view->with('count', $this->users->count());
    }
}
