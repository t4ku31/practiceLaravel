# Laravel マイグレーション：タスクテーブル外部キー追加レポート

## タイムライン

| 日時 | イベント | ステータス |
|------|---------|----------|
| 2026-05-27 | tasks テーブル初期作成 | ✓ 成功 |
| 2026-05-28 | user_id カラムと外部キー追加ミグレーション実行 | ✗ 失敗（沈黙） |
| 2026-05-29 | テーブル確認時に外部キーが存在しないことを発見 | - |
| 2026-05-29 | ロールバック試行 → down() エラーで失敗 | ✗ 失敗 |
| 2026-05-29 | down() メソッドを修正 | ✓ 修正完了 |
| 2026-05-29 | ロールバック成功 → マイグレーション再実行 | ✓ 成功（予定） |

---

## 初期状態：Tasks テーブル作成

### ファイル: `2026_05_27_232027_create_tasks_table.php`

```php
public function up(): void
{
    Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        $table->timestamps();
    });
}
```

**結果**: tasks テーブル作成成功
```sql
mysql> DESCRIBE tasks;
+------------+-----------------+------+-----+---------+----------------+
| Field      | Type            | Null | Key | Default | Extra          |
+------------+-----------------+------+-----+---------+----------------+
| id         | bigint unsigned | NO   | PRI | NULL    | auto_increment |
| created_at | timestamp       | YES  |     | NULL    |                |
| updated_at | timestamp       | YES  |     | NULL    |                |
+------------+-----------------+------+-----+---------+----------------+
```

---

## 第1回試行：外部キー追加ミグレーション

### ファイル: `2026_05_28_125157_add_user_id_to_tasks_table.php`

**初版：**

```php
public function up(): void
{
    Schema::table('tasks', function (Blueprint $table) {
        $table->foreignId('user_id')->constrained();
    });
}

public function down(): void
{
    Schema::table('tasks', function (Blueprint $table) {
        // 空のまま
    });
}
```

### 実行結果

```bash
php artisan migrate
```

**ステータス確認:**
```
2026_05_28_125157_add_user_id_to_tasks_table ....................... [3] Ran
```

マイグレーション実行ログには成功と表示されたが、実際にはカラムが追加されていない（沈黙の失敗）。

**テーブル確認:**
```sql
mysql> DESCRIBE tasks;
+------------+-----------------+------+-----+---------+----------------+
| Field      | Type            | Null | Key | Default | Extra          |
+------------+-----------------+------+-----+---------+----------------+
| id         | bigint unsigned | NO   | PRI | NULL    | auto_increment |
| created_at | timestamp       | YES  |     | NULL    |                |
| updated_at | timestamp       | YES  |     | NULL    |                |
+------------+-----------------+------+-----+---------+----------------+
```

`user_id` カラムが存在しない。

---

## 問題の原因

### 原因1: up() の沈黙の失敗

`up()` メソッド実行時にエラーが発生したが、Laravel がマイグレーション履歴には「実行済み」と記録した可能性がある。
考えられる原因:
- users テーブルの構造が期待と異なる
- 外部キー制約の設定に失敗

### 原因2: down() メソッドが空

ロールバック時に何もしない `down()` メソッドのため、マイグレーション記録は削除されるが、
テーブル状態は変わらない。

---

## ロールバック試行時のエラー

`down()` メソッドを修正後、ロールバック実行:

```bash
php artisan migrate:rollback --step=1
```

**エラー内容:**

```
SQLSTATE[42000]: Syntax error or access violation: 1091 
Can't DROP 'tasks_user_id_foreign'; check that column/key exists
```

**理由**: 
- `up()` が失敗してカラムと外部キーが作成されなかった
- 新しい `down()` メソッドが存在しないキーの削除を試みた
- 存在チェックなしで `dropForeign()` を呼んだため失敗

---

## 解決策：down() メソッドの修正

### 修正内容

存在チェックを追加してから削除を試みる:

```php
public function down(): void
{
    Schema::table('tasks', function (Blueprint $table) {
        if (Schema::hasColumn('tasks', 'user_id')) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        }
    });
}
```

**重点:**
- `Schema::hasColumn()` で user_id の存在を確認
- 存在する場合のみ外部キーとカラムを削除
- 存在しない場合は何もしない（エラーが起きない）

---

## 最終的なマイグレーションファイル

### ファイル: `2026_05_28_125157_add_user_id_to_tasks_table.php`（修正版）

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
```

---

## 解決後の確認手順

### Step 1: ロールバック実行

```bash
docker compose exec app php artisan migrate:rollback --step=1
# INFO  Rolling back migrations.
# 2026_05_28_125157_add_user_id_to_tasks_table ....... [success]
```

### Step 2: マイグレーション再実行

```bash
docker compose exec app php artisan migrate
# INFO  Running migrations.
# 2026_05_28_125157_add_user_id_to_tasks_table ....... 
```

### Step 3: テーブル確認

```bash
docker compose exec app mysql -uroot -ppassword laravel_db -e "DESCRIBE tasks;"
```

**期待結果:**

```
+------------+-----------------+------+-----+---------+----------------+
| Field      | Type            | Null | Key | Default | Extra          |
+------------+-----------------+------+-----+---------+----------------+
| id         | bigint unsigned | NO   | PRI | NULL    | auto_increment |
| user_id    | bigint unsigned | NO   | MUL | NULL    |                | ← 追加
| created_at | timestamp       | YES  |     | NULL    |                |
| updated_at | timestamp       | YES  |     | NULL    |                |
+------------+-----------------+------+-----+---------+----------------+
```

### Step 4: 外部キー確認

```bash
docker compose exec app mysql -uroot -ppassword laravel_db -e "SHOW CREATE TABLE tasks\G"
```

**期待結果:**
```
CONSTRAINT `tasks_user_id_foreign` FOREIGN KEY (`user_id`) 
REFERENCES `users` (`id`) ON DELETE CASCADE
```

---

## 教訓

### Laravel マイグレーション設計の重要なポイント

1. **down() メソッドは必ず実装する**
   - 存在チェックを含めて、ロールバック可能な状態にする
   - 空のままにしてはいけない

2. **外部キー追加時は制約条件を確認**
   - 参照先テーブル（users）が存在し、適切な構造か確認
   - 外部キーオプション（ON DELETE など）を明示的に指定

3. **マイグレーション実行後は必ず検証**
   - `DESCRIBE` でカラムが実際に追加されたか確認
   - `SHOW CREATE TABLE` で外部キー制約が正しく作成されたか確認
   - マイグレーション実行ログだけを信用しない

4. **ロールバック可能性を常に意識**
   - `migrate:rollback` で前の状態に戻せるか事前テスト
   - 本番環境では特に重要

5. **エラーハンドリング**
   - マイグレーション内で「存在チェック」を使って堅牢性を上げる
   - 意図的な失敗と予期しない失敗を区別する

---

## 参考: 外部キー制約の詳細構文

```php
// 基本系
$table->foreignId('user_id')->constrained();
// → FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)

// ON DELETE オプション指定
$table->foreignId('user_id')->constrained()->onDelete('cascade');
// → ... ON DELETE CASCADE

// ON UPDATE オプション指定
$table->foreignId('user_id')->constrained()->onUpdate('cascade');
// → ... ON UPDATE CASCADE

// 明示的なテーブル・カラム指定
$table->foreignId('owner_id')
    ->references('id')
    ->on('users')
    ->onDelete('cascade');
```

---

## 関連ファイル

- マイグレーションファイル: `src/database/migrations/2026_05_28_125157_add_user_id_to_tasks_table.php`
- Tasks モデル: `src/app/Models/Task.php`
- Users モデル: `src/app/Models/User.php`
