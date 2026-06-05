<div>
    <p>名前: {{ Auth::user()->name }}</p>
    <p>メール: {{ Auth::user()->email }}</p>
        <form method="POST">
    @csrf
    <button type="submit" formaction="{{ route('authentication.userLogout') }}">ログアウト</button>
</form>
</div>
