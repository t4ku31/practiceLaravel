# ユーザー登録・ログイン機能 実装手順レポート

## 現状確認

| ファイル | 状態 |
|---|---|
| `app/Models/User.php` | `Authenticatable` 継承済み・`fillable` 設定済み |
| `app/Repositories/UserRepository.php` | `count()` / `all()` のみ実装 |
| `app/Http/Controllers/UserController.php` | `store()` はバリデーション済みだがDB保存していない |
| `app/Http/Requests/StoreUserRequest.php` | 要確認・バリデーションルール追加が必要 |
| `routes/web.php` | `Route::resource('/users', ...)` 設定済み |

---

## 実装ステップ

### ステップ1: StoreUserRequest にバリデーションルールを追加

`app/Http/Requests/StoreUserRequest.php` の `rules()` を以下のように設定する。

```php
public function rules(): array
{
    return [
        'name'     => ['required', 'string', 'max:255'],
        'email'    => ['required', 'email', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ];
}

public function authorize(): bool
{
    return true;
}
```

- `confirmed` を使うと `password_confirmation` フィールドと一致チェックを自動で行う
- `unique:users,email` で重複登録を防ぐ

---

### ステップ2: UserRepository に create メソッドを追加

`app/Repositories/UserRepository.php` にユーザー作成メソッドを追加する。

```php
use Illuminate\Support\Facades\Hash;

public function create(array $data): User
{
    return User::create([
        'name'     => $data['name'],
        'email'    => $data['email'],
        'password' => Hash::make($data['password']),
    ]);
}
```

- パスワードのハッシュ化はRepositoryで行い、Controllerには生パスワードを持たせない
- `User` モデルの `casts` に `'password' => 'hashed'` が設定されているため、`Hash::make` は必須ではないが明示的に書くことで意図が明確になる

---

### ステップ3: UserController の store() を完成させる

DB保存 → 自動ログイン → リダイレクトの流れを実装する。

```php
use Illuminate\Support\Facades\Auth;

public function store(StoreUserRequest $request)
{
    $user = $this->users->create($request->validated());

    Auth::login($user);

    return redirect()->route('tasks.index');
}
```

---

### ステップ4: 登録フォームの View を作成

`resources/views/user/register.blade.php` を作成する。

```html
<form method="POST" action="{{ route('users.store') }}">
    @csrf
    <input type="text"     name="name"                  value="{{ old('name') }}">
    <input type="email"    name="email"                  value="{{ old('email') }}">
    <input type="password" name="password">
    <input type="password" name="password_confirmation">
    <button type="submit">登録</button>
</form>

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif
```

- `@csrf` は CSRF 対策トークンの埋め込みで必須
- `old('name')` でバリデーション失敗時に入力値を保持する

---

### ステップ5: ログイン用コントローラを作成

`app/Http/Controllers/AuthController.php` を新規作成する。

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('user.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('tasks.index'));
        }

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
```

- `Auth::attempt()` はパスワードの照合・セッション発行をまとめて行う
- `session()->regenerate()` はセッション固定攻撃の対策で必須
- `redirect()->intended()` はログイン前にアクセスしていたURLに戻す

---

### ステップ6: ログインフォームの View を作成

`resources/views/user/login.blade.php` を作成する。

```html
<form method="POST" action="{{ route('login') }}">
    @csrf
    <input type="email"    name="email"    value="{{ old('email') }}">
    <input type="password" name="password">
    <input type="checkbox" name="remember"> ログイン状態を保持する
    <button type="submit">ログイン</button>
</form>

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif
```

---

### ステップ7: ルーティングを整理する

`routes/web.php` にログイン・ログアウト用のルートを追加する。

```php
use App\Http\Controllers\AuthController;

// 登録（既存の Route::resource から create/store を使う）
Route::get('/register', [UserController::class, 'create'])->name('register');

// ログイン
Route::get('/login',  [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
```

---

## 実装順のまとめ

```
StoreUserRequest (バリデーション)
    ↓
UserRepository::create() (DB保存)
    ↓
UserController::store() (登録処理完成)
    ↓
register.blade.php (登録フォーム)
    ↓
AuthController (ログイン・ログアウト)
    ↓
login.blade.php (ログインフォーム)
    ↓
routes/web.php (ルート追加)
```

---

## 注意事項

- `tasks` ルートに `->middleware('auth')` がすでに設定されているため、未ログインユーザーは自動で `/login` にリダイレクトされる
- `User` モデルはすでに `Authenticatable` を継承しているため、Auth ファサードはそのまま使える
- パスワードは `casts` に `'hashed'` が設定されているため、`User::create()` 時に自動でハッシュ化される（`Hash::make` は二重ハッシュになるため、どちらか一方に統一すること）
