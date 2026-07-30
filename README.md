# 学習用_会員限定メディアサイト（会員登録・ログイン付き）

PHP で作った、**会員登録・ログイン機能つきの会員限定メディアサイト**「よみもの」です。
記事は途中まで誰でも読めますが、**続きを読むには会員登録・ログインが必要**（ペイウォール方式）。会員はマイページで自分の情報を編集でき、記事に**いいね**もできます。

---

## 主な機能

**会員登録（メール認証）**
- 📝 **入力フォーム**（名前・メールアドレス・パスワード）
- ✅ **入力チェック（バリデーション）** … jQuery でリアルタイムにエラー表示
- 👀 **確認画面** … 送信前に入力内容をチェック（パスワードは●で伏字表示）
- 📧 **認証メールの送信** … PHPMailer + Mailtrap 経由で確認メールを送る
- 🔑 **メール認証** … メール内のリンクをクリックすると本登録が完了

**いいね機能（今回追加）**
- ❤️ **記事へのいいね** … 一覧・詳細のハートをクリックしていいね／もう一度押すと取り消し
- 🔢 **いいね数の表示** … `like_table` を `GROUP BY` で集計し、記事テーブルに `LEFT OUTER JOIN` して一度に取得
- 🙋 **1会員1いいね** … 押す前に `COUNT(*)` で件数を確認し、0件なら `INSERT`／1件以上なら `DELETE` に分岐
- 🔗 **多対多（Many to Many）** … 会員と記事の関係を **中間テーブル**（`like_table`）で表現
- 📑 **マイページに「いいねした記事」一覧** … `like_table` と `articles` を `INNER JOIN` して取得。タイトルはリンクで、クリックするとその記事へ移動（新しくいいねした順）

**会員限定メディア**
- 📰 **記事一覧・記事詳細**（記事データは `articles` テーブル）
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

[like_create.php]  いいね処理（画面なし・要ログイン）
      │  ?article_id=● & back=top/article
      ├─ 未ログイン           → login.php へ追い返す
      ├─ COUNT(*) が 0 件     → INSERT（いいね登録）
      └─ COUNT(*) が 1 件以上 → DELETE（いいね取り消し）
      ↓
   元の画面へ戻る（一覧 or その記事）

[mypage.php]  マイページ（要ログイン・未ログインは追い返す）
      ├─ 名前を変更して送信
      │       ↓
      │  [mypage_update.php]  自分の名前を UPDATE → mypage.php?updated=1（完了トースト）
      │
      └─ いいねした記事の一覧（like_table × articles を INNER JOIN・新しい順）
              │  タイトルをクリック
              ↓
         [article.php?id=●]  その記事へ
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
├── mypage.php         # マイページ（要ログイン・名前変更＋いいねした記事一覧）
├── mypage_update.php  # 名前の更新処理（UPDATE＋完了トースト）
├── like_create.php    # いいね処理（COUNT→分岐→INSERT / DELETE）
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
│   ├── like_button.php     # いいねボタンの共通部品（like_button 関数）
│   ├── db_setup_like.sql   # articles / like_table を作るSQL＋記事データ投入
│   ├── db_setup_testusers.sql # テスト会員を追加するSQL（※Git管理外。ログイン情報が平文のため）
│   ├── articles.php        # 【現在未使用】記事のダミー配列（DB移行前の控え）
│   ├── db_config.php       # DB接続情報（※Git管理外。見本は db_config.php.example）
│   ├── db_config.php.example # DB接続情報の見本
│   └── mail_config.php     # メール接続情報（※Git管理外。自分で作成が必要）
│
├── css/style.css      # 共通スタイル（補足調整）
├── js/main.js         # 入力チェック（バリデーション）
└── PHPMailer/         # メール送信ライブラリ
```

> ※ 会員データ・記事データ・いいねデータは、すべて MySQL の `member_system_db` データベースに保存されます（`users` / `articles` / `like_table`）。
> ※ ファイルを書き換える前の控え（`.bak`）は、散らからないよう **`backup/` フォルダにまとめています**（Git管理外・手元のみ）。

---

## データベース設計

**DB名：** `member_system_db` ／ **テーブル：** `users`・`articles`・`like_table`

テーブルの作成用SQLは [`includes/db_setup_like.sql`](includes/db_setup_like.sql) にまとめてあります（phpMyAdmin の「SQL」タブに貼り付けて実行）。

### users（会員テーブル）

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

> ※ ログイン機能追加時、テーブルの作り替え（`ALTER TABLE`）は不要でした。既存のカラムだけで実現しています。

### articles（記事テーブル）

| カラム | データ型 | 内容 |
|--------|---------|------|
| id | INT（主キー・自動採番） | 記事番号（URLの `?id=●`） |
| title | VARCHAR(255) | 記事タイトル |
| intro | TEXT | 誰でも読める冒頭（ペイウォールの手前） |
| body | TEXT | 会員だけ読める続き。**段落は「空行」で区切って保存**し、表示時に分割する |
| created_at | DATETIME | 登録日時 |
| updated_at | DATETIME | 更新日時 |

### like_table（いいねテーブル／中間テーブル）

| カラム | データ型 | 内容 |
|--------|---------|------|
| id | INT（主キー・自動採番） | 通し番号 |
| user_id | INT | いいねした会員（`users.id` に対応） |
| article_id | INT | いいねされた記事（`articles.id` に対応） |
| created_at | DATETIME | いいねした日時 |

> `UNIQUE KEY (user_id, article_id)` を付け、同じ会員が同じ記事に2行入らないようにしています（連打対策の保険。判定自体はPHP側の `COUNT` で行う）。

### データ構造の考え方（RDB）

- **会員と記事は「多対多（Many to Many）」** … 1人の会員が複数の記事にいいねし、1つの記事も複数の会員からいいねされる。
- 多対多は片方のテーブルに列を足しても表現できないため、**「どの会員が」「どの記事に」を1行ずつ記録する中間テーブル**（`like_table`）を作る。
- **いいね数はカラムで持たず、必要なときに数える**（`GROUP BY` ＋ `COUNT`）。記事テーブルに「like数」列を持つと、いいねするたびに2つのテーブルを更新することになり、ズレの原因になる（アンチパターン）。

**いいね数の取得SQL（集計した表を結合する）:**

```sql
SELECT
  articles.*,
  result_table.like_count
FROM
  articles
  LEFT OUTER JOIN (
    SELECT article_id, COUNT(id) AS like_count
    FROM like_table
    GROUP BY article_id
  ) AS result_table
  ON articles.id = result_table.article_id
```

`LEFT OUTER JOIN` を使うのは、**いいねが0件の記事も一覧から消えないようにする**ため（`INNER JOIN` だと0件の記事が結果に出てこない）。0件の記事は `like_count` が `NULL` になるので、PHP側で `(int)` に変換して 0 として表示している。

**マイページ「いいねした記事」の取得SQL（記事タイトルを取ってくる）:**

```sql
SELECT
  articles.id,
  articles.title,
  like_table.created_at AS liked_at
FROM
  like_table
  INNER JOIN articles
    ON like_table.article_id = articles.id
WHERE
  like_table.user_id = :user_id
ORDER BY
  like_table.created_at DESC
```

こちらは **`INNER JOIN` が正解**。「いいねした記事だけ」を並べたいので、いいねの記録が無い記事は結果に出てこなくて良い。
**同じいいねデータでも、目的によって結合の種類が変わる**のがポイント（一覧＝全記事を残したい→`LEFT OUTER JOIN` ／ マイページ＝いいねしたものだけ→`INNER JOIN`）。

**接続情報（PDO）:**

接続情報は `includes/db_config.php`（Git管理外）にまとめ、各ページは `require` で読み込んで `$pdo` を使う。
`db_config.php` はアクセス元が `localhost` かどうかで、**ローカル用と本番用の接続先を自動で切り替える**（書き換え不要）。

```php
require __DIR__ . '/includes/db_config.php';   // これで $pdo が使える
```

> ※ 事前に phpMyAdmin で `users` テーブル（上記カラム）を作成しておく必要があります。
> `articles` / `like_table` は [`includes/db_setup_like.sql`](includes/db_setup_like.sql) を実行すれば作成＋記事データの投入まで完了します。
> `db_config.php` は公開しないため、`includes/db_config.php.example` を見本にして各自で作成する。

---

## このプロジェクトに含めないもの（今回のスコープ外）

学習課題のため、以下は実装していません：

- パスワードのハッシュ化（現状は平文で保存・照合）
- 管理者／一般ユーザーの権限分け（is_admin）
- トークンの有効期限
- 重複メールアドレスのチェック
- 記事の投稿・編集機能（記事はDBにあるが、追加はSQLで行う）
- いいねの非同期化（現状は画面が再読み込みされる）

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
- **いいねの「誰が」は URL から受け取りません。** `$_SESSION['user_id']`（ログイン時に保存した値）を使うため、URLを書き換えて他人としていいねすることはできません（URLで渡すのは `article_id` だけ）。
- 画面表示は `htmlspecialchars()` で XSS 対策をしています。
