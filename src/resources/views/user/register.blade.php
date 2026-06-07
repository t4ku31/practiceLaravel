<form action="{{ route('authentication.userRegister') }}" method="POST">
    @csrf
    <x-forms.input label="名前:" name="name" value="Takumi Yamada" />
    <x-forms.input label="メールアドレス:" type="email" name="email" value="yonenaka@example.com" />
    <x-forms.input label="パスワード:" type="password" name="password" value="password123" />
    <x-forms.input label="確認:" type="password" name="password_confirmation" value="password123" />

    <button type="submit">送信</button>
</form>
