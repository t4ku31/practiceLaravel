<form action="{{ route('authentication.userLogin') }}" method="POST">
    @csrf
    <label for="email">メールアドレス:</label>
    <input type="email" id="email" name="email" value="{{ old('email') }}">
    <label for="password">パスワード:</label>
    <input type="password" id="password" name="password">
    <button type="submit">ログイン</button>
</form>