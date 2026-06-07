<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="{{ asset('css/authForm.css') }}">
</head>
<body>
<div>
    <form action="{{ route('authentication.userLogin') }}" method="POST" class="Card">
        @csrf
        {{-- ※仮※　email: takumi@example.com pass:password --}}
        <x-forms.input label="メールアドレス:" type="email" name="email" />
        <x-forms.input label="パスワード:" type="password" name="password" />

        <button type="submit" @disabled($errors->isNotEmpty())>ログイン</button>
    </form>
</div>
</body>
</html>