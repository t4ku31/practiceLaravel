# Docker パーミッション不一致 トラブルレポート

## タイムライン

| 日時 | イベント |
|------|---------|
| 2026-05-28 | ファイルシステムのパーミッションエラーが報告される |
| 2026-05-28 | コンテナとホスト側のUID/GID不一致が原因と特定 |
| 2026-05-28 | グループ共有による解決方針を決定 |
| 2026-05-28 | Dockerfile・docker-compose.yml・ホスト側の設定を実施 |
| 2026-05-28 | 初回検証で GID の不一致を発見し修正 |
| 2026-05-29 | WSL2 再起動後、完全な解決を確認 |

---

## 発生した問題

Docker コンテナ（PHP-FPM）とホスト（WSL2）の間でファイルの所有者・グループが食い違い、
Laravel の `storage/` ディレクトリなど共有マウント領域に対して、どちらか一方のみしか
書き込みができない状態になった。

**具体的な症状:**
- コンテナ側（php-fpm/www-data）が作成したファイル → ホスト側（takumi）から読み取り不可
- ホスト側（takumi）が作成したファイル → コンテナ側（www-data）から読み取り不可
- Laravel の artisan コマンド実行時に `storage/` への書き込み権限エラー

---

## 原因

Linux のファイルパーミッションは UID/GID の数値で管理される。
Docker コンテナとホストはそれぞれ独立した `/etc/passwd` `/etc/group` を持つため、
同じファイルを mount で共有していても、UID/GID の対応が一致しないと権限の食い違いが起きる。

```
コンテナ内: www-data (UID=33, GID=33)
ホスト側:   takumi   (UID=1000, GID=1000)
→ 同じファイルを見ているのに、互いに「他人のファイル」扱いになる
```

---

## 問題の調査プロセス

### Step 1: 問題の認識
- ホスト側でプロジェクトディレクトリを開いたとき、ファイルの所有者がコンテナ側の UID で表示される
- コンテナ内で `whoami` → `www-data`、ホスト側で確認 → 所有者が合わない

### Step 2: 原因の特定
- `ls -la` で詳細を確認：
  ```
  コンテナ内:   root 側で実行したファイル → 所有者が root に見える
  ホスト側:     同じファイル → 所有者が UID 数値で見える
  ```
- Linux のファイルパーミッションは UID/GID の数値で管理されることを再認識
- Docker コンテナとホスト間の `/etc/passwd` `/etc/group` が別々であることを確認

### Step 3: 解決策の検討

**却下した案:** ホストとコンテナの UID を完全に一致させる
- 既にコンテナが `www-data` で動いていて、変更すると PHP-FPM の前提が崩れる
- 環境変数 `${UID}` がシェル依存で、Docker Compose に確実に渡らない懸念

**採用した案:** グループ共有アプローチ
- コンテナの `www-data` とホストの `takumi` を同じグループに所属させる
- ファイルのグループ所有権を共通グループに変更
- setgid により、新規作成ファイルも自動的に共通グループを継承
- 最小限の変更で、両者のアクセスを実現

---

## 実施した対応

### 1. Dockerfile に共有グループを追加

```dockerfile
RUN groupadd -g 1002 appgroup && \
    usermod -aG appgroup www-data
```

`www-data` を `appgroup`（GID=1002）に追加することで、コンテナ側のアクセス権を付与。

### 2. ホスト側でグループを作成・ユーザーを追加

```bash
sudo groupadd -g 1002 appgroup
sudo usermod -aG appgroup takumi
```

ホストの `takumi` を同じ GID=1002 の `appgroup` に追加。

### 3. ファイルのグループ所有権とパーミッションを設定

```bash
sudo chown -R :appgroup src/
sudo chmod -R g+rwX src/
sudo find src/ -type d -exec chmod g+s {} \;
```

| コマンド | 意味 |
|---|---|
| `chown -R :appgroup` | グループ所有者を appgroup に変更 |
| `chmod -R g+rwX` | グループに読み書き権限を付与（ディレクトリのみ実行権限） |
| `chmod g+s`（setgid） | 新規作成ファイルも自動的に appgroup を継承させる |

> `chmod 775` との違い: `g+rwX` は通常ファイルに実行権限をつけない（大文字 `X`）。
> また `others` の権限を変えないため、より安全。

### 4. GID の不一致を修正

Dockerfile で最初 GID=1001 を指定したが、ホスト側が自動で GID=1002 を割り当てていたため不一致が発生。
コンテナ内で `ls -la` したときにグループ名ではなく数字（1002）で表示されることで気づいた。

```bash
getent group appgroup  # ホスト側の実際の GID を確認
# → GID=1002 だったので Dockerfile を 1001 → 1002 に修正
```

---

## 最終的な構成

```
src/ (bind mount)
 ├── 所有者: takumi
 ├── グループ: appgroup (GID=1002)
 └── パーミッション: drwxrwsr-x (setgid あり)

コンテナ内 www-data → appgroup メンバー → グループ経由で読み書き可
ホスト側   takumi   → appgroup メンバー → グループ経由で読み書き可
```

---

## 最終確認と解決

### WSL2 再起動後の検証

2026-05-29 に WSL2 を再起動し、全設定が永続化されているか確認した。

#### 確認コマンド実行結果

```bash
# コンテナ側: www-data のグループ確認
docker compose exec app id www-data
# uid=33(www-data) gid=33(www-data) groups=33(www-data),1002(appgroup)
# ✓ appgroup メンバーシップ確認

# 動作確認: コンテナが作成したファイルのグループ確認
docker compose exec app touch /var/www/html/storage/test.txt
ls -la src/storage/test.txt
# -rw-r--r-- 1 root appgroup 0 May 29 08:08 src/storage/test.txt
# ✓ グループが appgroup に継承されている

# ホスト側: takumi のグループメンバーシップ確認
groups takumi
# takumi : takumi appgroup
# ✓ appgroup メンバーシップ確認
```

#### 解決の確認ポイント

| 項目 | 状態 | 備考 |
|------|------|------|
| www-data が appgroup メンバー | ✓ | groups に appgroup が表示 |
| takumi が appgroup メンバー | ✓ | groups に appgroup が表示 |
| setgid が機能している | ✓ | 新規ファイルが appgroup を継承 |
| ファイル書き込み双方向 | ✓ | コンテナ ↔ ホスト間で読み書き可能 |

### 解決内容のサマリー

**問題**: Docker コンテナ側（www-data, UID=33）とホスト側（takumi, UID=1000）の UID が異なり、
共有マウント領域 `src/` に対して互いにアクセスできない状態。

**解決方法**: グループ共有アプローチを採用
1. 共有グループ `appgroup`（GID=1002）を作成
2. コンテナの www-data をグループに追加
3. ホストの takumi をグループに追加
4. `src/` のグループ所有権を appgroup に変更
5. setgid でディレクトリに新規作成ファイル自動継承を設定

**成果**: 
- コンテナが作成したファイルをホストから読み書き可能
- ホストが作成したファイルをコンテナから読み書き可能
- Laravel の artisan コマンドによる storage/ 書き込みが正常動作
- WSL2 再起動後も設定が永続化

---

```bash
# ホスト側: グループと setgid の確認
ls -la src/
# drwxrwsr-x に s がついていること、グループが appgroup であること

# コンテナ内: www-data のグループ確認
docker compose exec app id www-data
# groups に appgroup が含まれていること

# 動作確認: コンテナで作ったファイルがホストから見えるか
docker compose exec app touch /var/www/html/storage/test.txt
ls -la src/storage/test.txt
# グループが appgroup になっていれば OK
rm src/storage/test.txt
```

---

## 教訓と推奨事項

### Docker パーミッション管理の原則

1. **`chmod 777` は避ける**
   - セキュリティリスクが高い
   - 一時的に見えても、根本解決ではない

2. **UID/GID の対応を明確にする**
   - 手動でマッピングするか（UID統一）
   - グループで共有するか（グループ統一）
   - 必ずどちらかの方法を採用する

3. **GID はホストと Dockerfile で一致させる**
   - ホスト側で `getent group appgroup` で確認
   - Dockerfile では正確な GID を指定
   - 不一致だとコンテナがグループ名を解決できない

4. **setgid を活用する**
   - ディレクトリに `chmod g+s` をつけることで、そのディレクトリ内で作られるファイル・ディレクトリが親のグループを自動継承
   - 一度設定すれば、以降のファイル作成で権限管理の手間が不要

### Laravel でこのアプローチを採用する際の注意点

- `storage/` と `bootstrap/cache/` は write-able である必要がある
- これらのディレクトリにグループ共有を適用することで、
  - コンテナ側の php-fpm が書き込んだファイルをホスト側で確認可能
  - ホスト側から artisan コマンド実行時の書き込み権限がある
- 結果として開発効率が大幅に向上

### WSL2 での Docker を使う際の最適プラクティス

- パーミッション問題が発生した場合、一度目の解決後も **設定の永続化を確認**すること
- `docker compose down` → 再起動 → `docker compose up` で確認
- WSL2 全体再起動の前後で、ユーザーのグループメンバーシップが反映されているか確認

---

## 参考: 設定済みの状態を検証するコマンド集

### ホスト側での確認

```bash
# takumi のグループメンバーシップ確認
groups takumi
# または
id takumi

# appgroup の詳細確認
getent group appgroup

# src/ のパーミッション確認
ls -la src/ | head -10
# グループが appgroup、ディレクトリに s がついていることを確認
```

### コンテナ側での確認

```bash
# www-data のグループメンバーシップ確認
docker compose exec app id www-data

# グループの存在確認
docker compose exec app getent group appgroup

# storage/ への書き込みテスト
docker compose exec app touch /var/www/html/storage/test-write.txt
docker compose exec app ls -la /var/www/html/storage/test-write.txt
```

### 双方向読み書きテスト

```bash
# コンテナが作ったファイルをホストから確認
docker compose exec app touch /var/www/html/storage/from-container.txt
ls -la src/storage/from-container.txt
# グループが appgroup で、takumi から読み書き可能

# ホストが作ったファイルをコンテナから確認
touch src/storage/from-host.txt
docker compose exec app cat /var/www/html/storage/from-host.txt
# www-data から読み取り可能

# 不要なテストファイルを削除
rm src/storage/from-container.txt src/storage/from-host.txt
```

---

## 参考資料

- [Linux ファイルパーミッション（chmod の g+s について）](https://en.wikipedia.org/wiki/Setuid)
- [Docker ボリュームマウントとパーミッション](https://docs.docker.com/storage/volumes/)
- [WSL2 と Docker Desktop の権限マッピング](https://docs.microsoft.com/en-us/windows/wsl/)

---
