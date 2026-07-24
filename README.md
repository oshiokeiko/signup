# 会員限定メディアサイト（会員登録・ログイン付き）

PHP で作った、**会員登録・ログイン機能つきの会員限定メディアサイト**「よみもの」です。
記事は途中まで誰でも読めますが、**続きを読むには会員登録・ログインが必要**（ペイウォール方式）。会員はマイページで自分の情報を編集できます。

もとは「メール認証つきの会員登録フォーム」として作り、そこに**セッションを使ったログイン機能とアクセス制限**を追加して、実際のWebサービスに近い形へ発展させました。

> PHP の基礎（フォーム送信・**セッション／ログイン**・**データベース(MySQL)への保存**・メール送信）を学ぶための課題として作成しました。
> データ保存は CSV → **PDO で MySQL に保存**する方式へ移行済みです。

---

## 主な機能

**会員登録（メール認証）**
- 📝 **入力フォーム**（名前・メールアドレス・パスワード）
- ✅ **入力チェック（バリデーション）** … jQuery でリアルタイムにエラー表示
- 👀 **確認画面** … 送信前に入力内容をチェック（パスワードは●で伏字表示）
- 📧 **認証メールの送信** … PHPMailer + Mailtrap 経由で確認メールを送る
- 🔑 **メール認証** … メール内のリンクをクリックすると本登録が完了

**会員限定メディア（今回追加）**
- 📰 **記事一覧・記事詳細**（記事データはダミー配列）
- 🔒 **ペイウォール** … 記事は冒頭2〜3行だけ表示、続きはフェードで隠し、会員登録／ログインを促す（未ログインには続き本文のHTMLを一切出力しない）
- 🚪 **ログイン・ログアウト** … セッションでログイン状態を管理
- 🛡️ **アクセス制限** … 未ログインで保護ページ（マイページ）を開くとログイン画面へ追い返す
- 👤 **マイページ** … 会員本人が自分の名前を変更（更新後に完了トーストを表示）

**会員管理（管理・確認用）**
- 📋 **ユーザー一覧画面**
- ✏️ **会員情報の更新（UPDATE）** … 詳細画面で名前を編集して保存
- 🗑️ **会員の削除（DELETE／論理削除）** … 確認モーダルの後、削除済みフラグを立てて一覧から非表示（データ自体は残す）

---

## サイトの入口

- `http://localhost/gs/07_signup/` … **TOP（記事一覧）** が開く（`index.php` は `top.php` へ自動転送）
- 会員登録の入力画面は `signup.php`
- ログインは `login.php`（テスト会員でログインすると記事の続きが読める）

---

## 会員登録の流れ

```
[signup.php]  名前・メール・パスワードを入力
      ↓
[confirm.php]  入力内容を確認（セッションに保存）
      ↓
[send_mail.php]  仮登録(pending)としてDBにINSERT → 認証メールを送信
      ↓
（メール内の認証リンクをクリック）
      ↓
[verify.php]  合言葉(token)をSELECTで照合 → UPDATEで本登録(active)に更新
      ↓
[complete.php]  「登録完了！」を表示（ログインへ）
```

---

## メディア閲覧・ログインの流れ

```
[top.php]  記事一覧（誰でも見られる）
      │  記事をクリック（?id=●）
      ↓
[article.php]  記事詳細 … 【誰でも入れる／追い返さない】
      ├─ 未ログイン → 冒頭だけ表示＋フェード →「会員登録／ログイン」へ
      └─ ログイン済 → 続き本文まで全部表示

[login.php] → [login_act.php]  メール・パスワードをDBと照合
      ├─ 成功（記事から来た） → その記事へ戻る
      ├─ 成功（それ以外）     → top.php へ
      └─ 失敗                → login.php へ戻す（?error=1）

[logout.php]  セッションを破棄して top.php へ

[mypage.php]  マイページ（要ログイン・未ログインは追い返す）
      │  名前を変更して送信
      ↓
[mypage_update.php]  自分の名前を UPDATE → mypage.php?updated=1（完了トースト）
```

- ログイン判定は `functions.php` の共通関数で行う（`check_login()`＝追い返す／`is_login()`＝見せ方を変える）。
- ログインのたびに `session_regenerate_id(true)` でセッションIDを作り直し、古いIDを無効化する。

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
- **削除**：物理削除はせず、`deleted_at` に日時をセットする**論理削除**。一覧・詳細では `WHERE deleted_at IS NULL` で削除済みを除外。
- 削除前に、自作の**確認モーダル**（HTML/CSS/JS）でワンクッション置く。

---

## 使用技術

| 種類 | 内容 |
|------|------|
| バックエンド | PHP（XAMPP の Apache） |
| データ保存 | **MySQL**（phpMyAdmin で作成・**PDO** で接続） |
| SQL 実行 | PDO のプリペアドステートメント（**バインド変数**でSQLインジェクション対策） |
| ログイン・状態管理 | PHP のセッション（`$_SESSION` / `session_id` / `session_regenerate_id`） |
| メール送信 | PHPMailer + [Mailtrap](https://mailtrap.io)（テスト用SMTP） |
| 見た目 | Tailwind CSS（CDN版） |
| 入力チェック | jQuery |

---

## ファイル構成

```
07_signup/
├── index.php          # 入口（top.php へ自動転送）
│
│  ── 会員限定メディア／ログイン ──
├── top.php            # TOP・記事一覧（誰でも閲覧可）
├── article.php        # 記事詳細（ペイウォール：続きは要ログイン）
├── login.php          # ログイン画面
├── login_act.php      # ログイン処理（DB照合 → セッション保存）
├── logout.php         # ログアウト処理（セッション破棄）
├── mypage.php         # マイページ（要ログイン・名前変更）
├── mypage_update.php  # 名前の更新処理（UPDATE＋完了トースト）
│
│  ── 会員登録（メール認証） ──
├── signup.php         # 入力画面
├── confirm.php        # 確認画面
├── send_mail.php      # 認証メール送信 ＋ 仮登録(DBにINSERT)
├── verify.php         # 認証リンクの照合(SELECT) → 本登録(UPDATE)
├── complete.php       # 登録完了画面
│
│  ── 会員管理（管理・確認用） ──
├── list.php           # ユーザー一覧（削除済みは非表示）
├── member_detail.php  # 会員の詳細・編集画面（編集＋削除＋モーダル）
├── member_update.php  # 更新処理（name を UPDATE）
├── member_delete.php  # 削除処理（論理削除：deleted_at に日時）
│
│  ── 共通部品（include/require で読み込む裏方） ──
├── includes/
│   ├── functions.php       # ログイン判定（is_login / check_login）
│   ├── articles.php        # 記事のダミーデータ（配列）
│   ├── db_config.php       # DB接続情報（※Git管理外。見本は db_config.php.example）
│   ├── db_config.php.example # DB接続情報の見本
│   └── mail_config.php     # メール接続情報（※Git管理外。自分で作成が必要）
│
├── css/style.css      # 共通スタイル（補足調整）
├── js/main.js         # 入力チェック（バリデーション）
└── PHPMailer/         # メール送信ライブラリ
```

> ※ 会員データは MySQL の `member_system_db` データベースに保存されます。記事データはDBを使わず `includes/articles.php` の配列です。

---

## データベース設計

**DB名：** `member_system_db` ／ **テーブル：** `users`

| カラム | データ型 | 内容 |
|--------|---------|------|
| id | INT（主キー・自動採番） | 通し番号 |
| name | VARCHAR(50) | 名前 |
| email | VARCHAR(255) | メールアドレス（**ログインのIDにも使用**） |
| password | VARCHAR(255) | パスワード（※学習用のため平文保存／ログイン照合にも使用） |
| token | VARCHAR(255) | 認証用トークン（合言葉） |
| status | VARCHAR(20) | `pending`（仮登録）/ `active`（本登録済み。**activeのみログイン可**） |
| created_at | DATETIME | 登録日時 |
| updated_at | DATETIME | 更新日時（本登録・名前更新時に更新） |
| deleted_at | DATETIME（NULL許可） | 論理削除フラグ。未削除時は NULL、削除時に日時をセット（**削除済みはログイン不可**） |

> ※ 今回のログイン機能追加でテーブルの作り替え（`ALTER TABLE`）は不要でした。既存のカラムだけで実現しています。

**接続情報（PDO）:**

接続情報は `includes/db_config.php`（Git管理外）にまとめ、各ページは `require` で読み込んで `$pdo` を使う。
`db_config.php` はアクセス元が `localhost` かどうかで、**ローカル用と本番用の接続先を自動で切り替える**（書き換え不要）。

```php
require __DIR__ . '/includes/db_config.php';   // これで $pdo が使える
```

> ※ 事前に phpMyAdmin で `users` テーブル（上記カラム）を作成しておく必要があります。
> `db_config.php` は公開しないため、`includes/db_config.php.example` を見本にして各自で作成する。

---

## このプロジェクトに含めないもの（今回のスコープ外）

学習課題のため、以下は実装していません：

- パスワードのハッシュ化（現状は平文で保存・照合）
- 管理者／一般ユーザーの権限分け（is_admin）
- トークンの有効期限
- 重複メールアドレスのチェック
- 記事のDB化・投稿機能（記事はダミー配列）

> ⚠️ **本番運用には向きません。** パスワードを暗号化せず保存しているため、あくまで学習用としてご利用ください。

---

## セキュリティ上のメモ

- `includes/db_config.php`（DB接続情報）・`includes/mail_config.php`（メール接続情報）・`debug.log` は **`.gitignore` で除外**しており、GitHub には公開していません。
- **ログイン状態**はセッションで管理し、ログイン判定のたびに `session_regenerate_id(true)` でIDを作り直して、古いIDを無効化しています。
- **アクセス制限**：保護ページ（マイページ）は未ログインだと URL 直打ちでもログイン画面へ追い返します（リンクを隠すだけでは不十分なため）。
- **ペイウォール**：未ログインには続き本文の HTML を**そもそも出力しない**ため、ページのソースを見ても続きは読めません。
- ログインできるのは `status = 'active'`（本登録済み）かつ `deleted_at IS NULL`（未退会）の会員のみ。
- SQL は PDO の **バインド変数** を使って実行し、SQLインジェクションを防いでいます。
- `UPDATE`・削除処理には必ず `WHERE id = :id` を付け、全レコードの誤更新・誤削除を防いでいます（マイページの更新は `$_SESSION['user_id']` を使い、他人を書き換えられません）。
- URLの `id` は整数チェック（`FILTER_VALIDATE_INT`）してから使用しています。
- 画面表示は `htmlspecialchars()` で XSS 対策をしています。
