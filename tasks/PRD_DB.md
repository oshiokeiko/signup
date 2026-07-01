# PRD：メール認証付き会員登録システム（DB版）

**作成日：** 2026年6月30日
**ステータス：** 確定
**対象：** PHP基礎課題（DB/SQL回） / 卒制の土台
**ベース：** `PRD.md`（CSV版）からのDB移行

---

## 1. 概要

CSV版で作成した「メール認証付き会員登録システム」のデータ保存先をCSVからMySQL（DB）に置き換える。
画面構成・処理フロー・バリデーション・セッション管理はCSV版を踏襲し、データ操作部分のみPDOによるSQL実行に変更する。

---

## 2. 目的

- PHPとMySQLを連携させる基礎（PDO・SQL）を実践的に習得する
- CSVでの実装をDBに置き換えることで、データ保存方式の違いを体感する
- 卒業制作プロトタイプとして提出できる完成度に引き上げる

---

## 3. 技術スタック

| 技術 | 内容 |
|------|------|
| バックエンド | PHP（XAMPPのApache） |
| データ保存 | **MySQL（phpMyAdminで作成・PDOで接続）** |
| セッション | PHPの`$_SESSION`（ページ間のデータ引き回しに使用） |
| メール送信テスト | Mailtrap（ダミーSMTPサーバー） |
| CSSフレームワーク | Tailwind CSS（CDN版） |
| JavaScript | jQuery |

---

## 4. ユーザーと画面構成

### ユーザー側（会員登録フロー）

| No | ファイル名 | 画面名 | 役割 |
|----|-----------|--------|------|
| 1 | `index.php` | 入力画面 | 名前・メールアドレス・パスワードを入力 |
| 2 | `confirm.php` | 確認画面 | 入力内容を表示し、送信 or 戻るを選択 |
| 3 | `send_mail.php` | 送信処理 | 認証メールを送信＋仮登録をDBに保存 |
| 4 | `verify.php` | 認証画面 | メール内URLをクリックして本登録完了 |
| 5 | `complete.php` | 登録完了画面 | 登録完了メッセージを表示 |

### 管理者側（開発・確認用）

| No | ファイル名 | 画面名 | 役割 |
|----|-----------|--------|------|
| 6 | `list.php` | ユーザー一覧 | DB内の登録済みユーザーを一覧表示（開発確認用） |

CSV版からの変更点：**ファイル構成・画面遷移は変更なし。内部のデータ操作のみ変更。**

---

## 5. 処理フロー

```
[index.php]
ユーザーが名前・メール・パスワードを入力してPOST送信
        ↓
[confirm.php]
① session_start()でセッション開始
② 入力値を$_SESSIONに保存
③ セッションの値を画面に表示
「登録する」ボタンでsend_mail.phpへ / 「戻る」で index.php へ
        ↓
[send_mail.php]
① session_start()でセッションから値を取り出す
② 認証トークン（ランダム文字列）を生成
③ DBに接続（PDO）
④ INSERT文で仮登録データを保存（名前・メール・パスワード・トークン・status='pending'）
⑤ 認証URL付きのメールをユーザーに送信
⑥ セッションを破棄する
⑦ 「メールを送信しました」画面を表示
        ↓
[verify.php]（メール内のURLをクリック）
① DBに接続（PDO）
② SELECT文でトークンが一致するレコードを検索
③ 一致すればUPDATE文でstatusを「active」に更新
④ complete.php へリダイレクト
        ↓
[complete.php]
「登録完了！」メッセージを表示
```

---

## 6. 入力項目とバリデーション

| 項目 | 入力タイプ | バリデーション |
|------|-----------|---------------|
| 名前 | テキスト | 必須 |
| メールアドレス | email | 必須・形式チェック |
| パスワード | password | 必須・半角英数8文字以上 |

CSV版から変更なし。

---

## 7. DB / テーブル設計

### DB

| 項目 | 内容 |
|------|------|
| DB名 | `member_system_db`（任意で変更可） |
| 文字コード | `utf8mb4_bin` |

### テーブル：`users`

| カラム名 | データ型 | 長さ | その他設定 |
|---------|---------|------|-----------|
| id | INT | 11 | PRIMARY / A_I（Auto Increment） |
| name | VARCHAR | 50 | |
| email | VARCHAR | 255 | |
| password | VARCHAR | 255 | ※今回は平文保存、本来はハッシュ化 |
| token | VARCHAR | 255 | |
| status | VARCHAR | 20 | `pending`（仮）/ `active`（本登録済み） |
| created_at | DATETIME | - | |
| updated_at | DATETIME | - | |

CSV版の`name, email, password, token, status, created_at`をベースに、`id`（主キー）と`updated_at`（更新日時）を追加。

---

## 8. PDO接続情報

```php
$dbn ='mysql:dbname=member_system_db;charset=utf8mb4;port=3306;host=localhost';
$user = 'root';
$pwd = '';

try {
  $pdo = new PDO($dbn, $user, $pwd);
} catch (PDOException $e) {
  echo json_encode(["db error" => "{$e->getMessage()}"]);
  exit();
}
```

各ファイルでDBを使う処理（`send_mail.php`、`verify.php`、`list.php`）の冒頭で同じ接続処理を行う。

---

## 9. SQL設計

### 仮登録（INSERT） — `send_mail.php`

```sql
INSERT INTO users (id, name, email, password, token, status, created_at, updated_at)
VALUES (NULL, :name, :email, :password, :token, 'pending', now(), now());
```

- バインド変数（`:name`, `:email`, `:password`, `:token`）を必ず使用し、SQLインジェクション対策をする

### トークン照合（SELECT） — `verify.php`

```sql
SELECT * FROM users WHERE token = :token;
```

### 本登録への更新（UPDATE） — `verify.php`

```sql
UPDATE users SET status = 'active', updated_at = now() WHERE token = :token;
```

- 【重要】`WHERE`を必ず指定する（忘れると全レコードが更新される）

### 一覧取得（SELECT） — `list.php`

```sql
SELECT * FROM users ORDER BY created_at DESC;
```

---

## 10. セッションの使い方

CSV版から変更なし。

| タイミング | 処理 |
|-----------|------|
| `confirm.php`に遷移したとき | `session_start()`して入力値を`$_SESSION`に保存 |
| `send_mail.php`で処理するとき | `session_start()`して`$_SESSION`から値を取り出す |
| DB保存・メール送信完了後 | `session_destroy()`でセッションを破棄 |

---

## 11. 認証メールの仕様

CSV版から変更なし。

| 項目 | 内容 |
|------|------|
| 件名 | 【会員登録】メールアドレスの確認 |
| 本文 | 認証URLを記載（例：`http://localhost/verify.php?token=xxxx`） |
| 送信方法 | PHPの `mail()` 関数（Mailtrap経由） |
| トークン有効期限 | 今回は設定なし（卒制で実装） |

---

## 12. ファイル構成

```
プロジェクトフォルダ/
├── index.php        # 入力画面
├── confirm.php      # 確認画面
├── send_mail.php    # メール送信＋DB仮登録処理
├── verify.php       # トークン照合・本登録処理
├── complete.php     # 登録完了画面
├── list.php         # ユーザー一覧（管理・確認用）
├── css/
│   └── style.css    # 全ページ共通スタイル（個別調整用）
└── js/
    └── main.js      # バリデーションなどのJS・jQuery処理
```

**変更点：** `data/`フォルダ（CSV保存用）は不要になったため削除。データはMySQLが保持する。

---

## 13. 今回のスコープ外（卒制で追加予定）

- ログイン・ログアウト機能（セッションの応用）
- パスワードのハッシュ化（`password_hash()`）
- トークンの有効期限管理
- 重複メールアドレスのチェック
- SMS認証への切り替え（Twilio連携）

---

## 14. 学習ポイント（授業との対応）

| 実装内容 | 対応する授業内容 |
|---------|---------------|
| DB・テーブル作成 | phpMyAdminでのDB/テーブル作成 |
| データ保存 | `PDO` + `INSERT`文 + バインド変数 |
| トークン照合 | `PDO` + `SELECT`文 + `WHERE` |
| 本登録への更新 | `PDO` + `UPDATE`文 + `WHERE` |
| 一覧表示 | `PDO` + `SELECT`文 + `fetchAll()` |
| フォーム送信 | POST方式のデータ送受信（CSV版から継続） |
| セッションで値を引き回す | `$_SESSION`（CSV版から継続） |
| XSS対策 | `htmlspecialchars()`（CSV版から継続） |
| メール送信 | `mail()`関数（CSV版から継続） |
| トークン生成 | `bin2hex(random_bytes())`（CSV版から継続） |
| SQLインジェクション対策 | バインド変数の使用（新規） |
