<form>
    @csrf
    <button type="submit" formaction="{{ route('authentication.userLogout') }}">ログアウト</button>
</form>