<form action="{{ route('users.store') }}" method="POST">
    @csrf
    <label for="name">名前:</label>
    <input type="text" id="name" name="name" value="Takumi Yamada">

    <label for="email">メールアドレス:</label>
    <input type="email" id="email" name="email" value="yonenaka@example.com">

    <label for="password">パスワード:</label>
    <input type="password" id="password" name="password" value="password123">

    <label for="password_confirmation">確認:</label>
    <input type="password" id="password_confirmation" name="password_confirmation" value="password123">

    <button type="submit">送信</button>
</form>
