# PRD：メール認証付き会員登録システム

**作成日：** 2026年6月22日
**最終更新：** 2026年6月23日
**ステータス：** 確定
**対象：** PHP基礎課題 / 卒制の土台

---

## 1. 概要

ユーザーがフォームに個人情報を入力し、メール認証を経て会員登録が完了するシステム。
将来的にSNS・コミュニティ系の卒制アプリに転用できる汎用的な設計とする。

---

## 2. 目的

- PHP（フォーム送信・ファイル保存・メール送信・セッション管理）の基礎を実践的に習得する
- 「入力 → 確認 → 認証 → 登録完了」という会員登録の基本フローを実装する
- 卒制アプリで使い回せる会員登録の部品を作る

---

## 3. 技術スタック

| 技術 | 内容 |
|------|------|
| バックエンド | PHP（XAMPPのApache） |
| データ保存 | CSV（`data/users.csv`） |
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
| 3 | `send_mail.php` | 送信処理 | 認証メールを送信＋仮登録をCSVに保存 |
| 4 | `verify.php` | 認証画面 | メール内URLをクリックして本登録完了 |
| 5 | `complete.php` | 登録完了画面 | 登録完了メッセージを表示 |

### 管理者側（開発・確認用）

| No | ファイル名 | 画面名 | 役割 |
|----|-----------|--------|------|
| 6 | `list.php` | ユーザー一覧 | CSV内の登録済みユーザーを一覧表示（開発確認用） |

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
③ 仮登録データをCSVに保存（名前・メール・パスワード・トークン・仮登録フラグ）
④ 認証URL付きのメールをユーザーに送信
⑤ セッションを破棄する
⑥ 「メールを送信しました」画面を表示
        ↓
[verify.php]（メール内のURLをクリック）
① URLのトークンをCSVと照合
② 一致すればstatusを「active」に更新
③ complete.php へリダイレクト
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

---

## 7. データ保存（CSVファイル）

**保存先：** `data/users.csv`

| カラム名 | 内容 | 例 |
|---------|------|-----|
| name | 名前 | 山田太郎 |
| email | メールアドレス | taro@example.com |
| password | パスワード（※今回はそのまま保存、本来はハッシュ化） | pass1234 |
| token | 認証トークン | a1b2c3d4e5f6... |
| status | 登録状態 | `pending`（仮）/ `active`（本登録済み） |
| created_at | 登録日時 | 2026-06-22 12:00:00 |

---

## 8. セッションの使い方

| タイミング | 処理 |
|-----------|------|
| `confirm.php`に遷移したとき | `session_start()`して入力値を`$_SESSION`に保存 |
| `send_mail.php`で処理するとき | `session_start()`して`$_SESSION`から値を取り出す |
| CSV保存・メール送信完了後 | `session_destroy()`でセッションを破棄 |

```php
// 保存（confirm.php）
session_start();
$_SESSION['name']     = htmlspecialchars($_POST['name'], ENT_QUOTES);
$_SESSION['email']    = htmlspecialchars($_POST['email'], ENT_QUOTES);
$_SESSION['password'] = $_POST['password'];

// 取り出し（send_mail.php）
session_start();
$name     = $_SESSION['name'];
$email    = $_SESSION['email'];
$password = $_SESSION['password'];

// 破棄（send_mail.php の最後）
session_destroy();
```

---

## 9. 認証メールの仕様

| 項目 | 内容 |
|------|------|
| 件名 | 【会員登録】メールアドレスの確認 |
| 本文 | 認証URLを記載（例：`http://localhost/verify.php?token=xxxx`） |
| 送信方法 | PHPの `mail()` 関数（Mailtrap経由） |
| トークン有効期限 | 今回は設定なし（卒制で実装） |

---

## 10. ファイル構成

```
プロジェクトフォルダ/
├── index.php        # 入力画面
├── confirm.php      # 確認画面
├── send_mail.php    # メール送信＋仮登録処理
├── verify.php       # 認証処理
├── complete.php     # 登録完了画面
├── list.php         # ユーザー一覧（管理・確認用）
├── css/
│   └── style.css    # 全ページ共通スタイル（個別調整用）
├── js/
│   └── main.js      # バリデーションなどのJS・jQuery処理
└── data/
    └── users.csv    # ユーザーデータ（自動生成）
```

**補足：**
- Tailwind CSS はCDN版を各phpファイルの`<head>`内で読み込む
- `data/` フォルダはセキュリティ上アクセス制限をかけるため分離

---

## 11. 今回のスコープ外（卒制で追加予定）

- ログイン・ログアウト機能（セッションの応用）
- パスワードのハッシュ化（`password_hash()`）
- トークンの有効期限管理
- 重複メールアドレスのチェック
- SMS認証への切り替え（Twilio連携）
- データベース（MySQL）への移行

---

## 12. 学習ポイント（授業との対応）

| 実装内容 | 対応する授業内容 |
|---------|---------------|
| フォーム送信 | POST方式のデータ送受信 |
| セッションで値を引き回す | `$_SESSION`（新規） |
| CSVへの保存 | `fopen` / `fwrite` / `fclose` |
| CSVの読み込み | `fgets`での一覧表示 |
| XSS対策 | `htmlspecialchars()` |
| メール送信 | `mail()`関数（新規） |
| トークン生成 | `bin2hex(random_bytes())`（新規） |