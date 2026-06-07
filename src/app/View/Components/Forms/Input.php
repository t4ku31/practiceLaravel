<?php

namespace App\View\Components\Forms;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\ViewErrorBag;

class Input extends Component
{
    public function __construct(
        public string $label,
        public string $name,
        public string $type = 'text',
        public string $value = '',
    ) {}

    public function inputClass(): string
    {
        $errors = session('errors') ?? new ViewErrorBag;

        //validationエラー発生時に、errorsクラスから対象のpropertyのエラー有無を確認し、エラーがあればinput-errorクラスを付与する
        return $errors->has($this->name)
            ? 'input-base input-error'
            : 'input-base';
    }

    public function render(): View|Closure|string
    {
        return view('components.forms.input');
    }
}
