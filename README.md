# メール認証付き 会員登録システム

PHP で作った、**メール認証つきの会員登録フォーム**です。
「入力 → 確認 → メール送信 → 認証リンクをクリック → 本登録完了」という、会員登録の基本的な流れを一通り実装しています。

> PHP の基礎（フォーム送信・セッション・**データベース(MySQL)への保存**・メール送信）を学ぶための課題として作成しました。
> 当初は CSV ファイルに保存していましたが、**PDO を使って MySQL に保存する方式へ移行**しました。

---

## 主な機能

- 📝 **入力フォーム**（名前・メールアドレス・パスワード）
- ✅ **入力チェック（バリデーション）** … jQuery でリアルタイムにエラー表示
- 👀 **確認画面** … 送信前に入力内容をチェック（パスワードは●で伏字表示）
- 📧 **認証メールの送信** … PHPMailer + Mailtrap 経由で確認メールを送る
- 🔑 **メール認証** … メール内のリンクをクリックすると本登録が完了
- 📋 **ユーザー一覧画面**（管理・確認用）
- ✏️ **会員情報の更新（UPDATE）** … 詳細画面で名前を編集して保存
- 🗑️ **会員の削除（DELETE／論理削除）** … 確認モーダルの後、削除済みフラグを立てて一覧から非表示（データ自体は残す）

---

## 登録の流れ

```
[index.php]  名前・メール・パスワードを入力
      ↓
[confirm.php]  入力内容を確認（セッションに保存）
      ↓
[send_mail.php]  仮登録(pending)としてDBにINSERT → 認証メールを送信
      ↓
（メール内の認証リンクをクリック）
      ↓
[verify.php]  リンクの合言葉(token)をSELECTで照合 → UPDATEで本登録(active)に更新
      ↓
[complete.php]  「登録完了！」を表示
```

`list.php`（`http://localhost/gs/07_signup/list.php` に直接アクセス）で、各ユーザーの状態が `pending`（仮登録）→ `active`（本登録）に変わるのを確認できます。

---

## 会員管理（更新・削除）の流れ

```
[list.php]  一覧（削除済みは表示されない）
      │  各行の「詳細」リンク（?id=●）
      ↓
[member_detail.php]  詳細・編集画面
      ├─ 名前を編集して「保存」 ── [member_update.php] → 一覧へ戻る（UPDATE）
      └─ 「削除」→ 確認モーダル →「削除する」── [member_delete.php] → 一覧へ戻る（論理削除）
```

- **更新**：編集できるのは `name`（名前）のみ。`UPDATE users SET name=... WHERE id=...` を実行。
- **削除**：物理削除はせず、`deleted_at` に日時をセットする**論理削除**（`UPDATE users SET deleted_at=now() WHERE id=...`）。一覧・詳細では `WHERE deleted_at IS NULL` で削除済みを除外。
- 削除前に、自作の**確認モーダル**（HTML/CSS/JS）でワンクッション置く。

---

## 使用技術

| 種類 | 内容 |
|------|------|
| バックエンド | PHP（XAMPP の Apache） |
| データ保存 | **MySQL**（phpMyAdmin で作成・**PDO** で接続） |
| SQL 実行 | PDO のプリペアドステートメント（**バインド変数**でSQLインジェクション対策） |
| ページ間のデータ受け渡し | PHP のセッション（`$_SESSION`） |
| メール送信 | PHPMailer + [Mailtrap](https://mailtrap.io)（テスト用SMTP） |
| 見た目 | Tailwind CSS（CDN版） |
| 入力チェック | jQuery |

---

## ファイル構成

```
07_signup/
├── index.php        # 入力画面
├── confirm.php      # 確認画面
├── send_mail.php    # 認証メール送信 ＋ 仮登録(DBにINSERT)
├── verify.php       # 認証リンクの照合(SELECT) → 本登録(UPDATE)
├── complete.php     # 登録完了画面
├── list.php         # ユーザー一覧（管理・確認用／削除済みは非表示）
├── member_detail.php # 会員の詳細・編集画面（名前編集＋削除フォーム＋モーダル）
├── member_update.php # 更新処理（name を UPDATE）
├── member_delete.php # 削除処理（論理削除：deleted_at に日時をセット）
├── db_config.php     # DB接続情報（※Git管理外。見本は db_config.php.example）
├── mail_config.php  # メール接続情報（※Git管理外。自分で作成が必要）
├── css/style.css    # 共通スタイル（補足調整）
├── js/main.js       # 入力チェック（バリデーション）
└── PHPMailer/       # メール送信ライブラリ
```

> ※ 登録データは MySQL の `member_system_db` データベースに保存されます。
> CSV版で使っていた `data/` フォルダは不要になりました。

---

## データベース設計

**DB名：** `member_system_db` ／ **テーブル：** `users`

| カラム | データ型 | 内容 |
|--------|---------|------|
| id | INT（主キー・自動採番） | 通し番号 |
| name | VARCHAR(50) | 名前 |
| email | VARCHAR(255) | メールアドレス |
| password | VARCHAR(255) | パスワード（※学習用のため平文保存） |
| token | VARCHAR(255) | 認証用トークン（合言葉） |
| status | VARCHAR(20) | `pending`（仮登録）/ `active`（本登録済み） |
| created_at | DATETIME | 登録日時 |
| updated_at | DATETIME | 更新日時（本登録・名前更新時に更新） |
| deleted_at | DATETIME（NULL許可） | 論理削除フラグ。未削除時は NULL、削除時に日時をセット |

**接続情報（PDO）:**

接続情報は `db_config.php`（Git管理外）にまとめ、各ページは `require` で読み込んで `$pdo` を使う。
`db_config.php` はアクセス元が `localhost` かどうかで、**ローカル用と本番用の接続先を自動で切り替える**（書き換え不要）。

```php
require __DIR__ . '/db_config.php';   // これで $pdo が使える
```

> ※ 事前に phpMyAdmin で `users` テーブル（上記カラム）を作成しておく必要があります。
> `db_config.php` は公開しないため、`db_config.php.example` を見本にして各自で作成する。

---

## このプロジェクトに含めないもの（今回のスコープ外）

学習課題のため、以下は実装していません（卒業制作で追加予定）：

- ログイン・ログアウト
- パスワードのハッシュ化（現状は平文で保存）
- トークンの有効期限
- 重複メールアドレスのチェック

> ⚠️ **本番運用には向きません。** パスワードを暗号化せず保存しているため、あくまで学習用としてご利用ください。

---

## セキュリティ上のメモ

- `db_config.php`（DB接続情報）・`mail_config.php`（メール接続情報）・`debug.log`（サーバーのパス情報を含むログ）は **`.gitignore` で除外**しており、GitHub には公開していません。
- SQL は PDO の **バインド変数** を使って実行し、SQLインジェクションを防いでいます。
- `UPDATE`・削除処理には必ず `WHERE id = :id` を付け、全レコードが誤って更新・削除される事故を防いでいます。
- 削除は**論理削除**（`deleted_at` に日時をセット）で、実データを残しつつ一覧から除外しています。
- URLの `id` は整数チェック（`FILTER_VALIDATE_INT`）してから使用しています。
- 画面表示は `htmlspecialchars()` で XSS 対策をしています。
