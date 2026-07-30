# いいね機能の実装計画（PHP05：中間テーブル・集計・結合）

Zenn 講義資料「PHP05」の内容を、このプロジェクト（会員限定メディア）に当てはめて実装する。
講義の `todo` を `記事` に置き換える。

## ゴール

- 会員は複数の記事にいいねでき、記事は複数の会員からいいねされる（**Many to Many**）
- いいねボタンを押すといいねでき、記事一覧・記事詳細にいいね数が表示される
- いいねは 1 会員 1 回まで（もう一度押すと取り消し）

## 方針（ユーザー選択：記事もデータベースへ移す）

記事データを PHP ファイルの配列からデータベースへ移し、講義とまったく同じ
`LEFT OUTER JOIN` ＋ 集計サブクエリで、いいね数を一覧に出す。

---

## テーブル設計

### articles（記事テーブル）… 新規

| カラム名   | データ型     | 長さ | その他設定 |
|-----------|-------------|-----|-----------|
| id        | INT         | 11  | PRIMARY / A_I |
| title     | VARCHAR     | 255 | 記事タイトル |
| intro     | TEXT        | -   | 誰でも読める冒頭（ペイウォールの手前） |
| body      | TEXT        | -   | 会員だけ読める続き。**段落は空行で区切って 1 つに保存** |
| created_at| DATETIME    | -   | |
| updated_at| DATETIME    | -   | |

### like_table（いいねテーブル）… 新規・**中間テーブル**

| カラム名   | データ型  | 長さ | その他設定 |
|-----------|----------|-----|-----------|
| id        | INT      | 11  | PRIMARY / A_I |
| user_id   | INT      | 11  | 既存 `users.id` に対応 |
| article_id| INT      | 11  | `articles.id` に対応 |
| created_at| DATETIME | -   | |

「どの会員が」「どの記事に」いいねしたかを 1 行ずつ記録する。
講義の `todo_id` は、このプロジェクトでは `article_id` にあたる。

### データ構造の確認（アンチパターン点検）

- [x] 会員の操作でテーブルが増減しない
- [x] 会員の操作でカラムが増減しない
- [x] 1 つのセルに複数の値を詰めていない（いいねは 1 行 1 件）
- [x] いいね追加時に他テーブルを更新する必要がない（数は毎回集計する）

---

## 講義と違えるところ（1 点だけ・意図的）

講義では `like_create.php?user_id=1&todo_id=3` のように **user_id を URL で送る**。
この形だと URL を書き換えて「他人になりすましていいね」ができてしまう。

このプロジェクトはログイン機能があるので、**user_id は URL では受け取らず、
ログイン時にセッションへ保存済みの `$_SESSION['user_id']` を使う**。
URL で送るのは `article_id` だけにする。

---

## 実装ステップ

### 1. テーブルを作る
- [x] `includes/db_setup_like.sql` を作成（articles / like_table の CREATE 文）
- [x] SQL を実行してテーブルを作る（phpMyAdmin で実行）
- [x] 記事 3 件を `articles` へ投入（いまの `includes/articles.php` の内容をそのまま）

### 2. 表示をデータベース読み込みに切り替える（※ここまでで見た目は今と同じ）
- [x] `top.php` … `articles` テーブルから一覧を取得
- [x] `article.php` … `articles` テーブルから 1 件取得（body は空行で段落に分ける）
- [x] **退行チェック**：ペイウォール（未ログインは続きが読めない）が今と同じ動きか確認

### 3. いいねを記録する
- [x] `like_create.php` を新規作成
- [x] `top.php` / `article.php` にいいねボタンを設置（共通部品 `includes/like_button.php`）
- [x] 押したら `like_table` に行が増えることを確認

### 4. 1 会員 1 いいねにする
- [x] `like_create.php` で `COUNT(*)` で件数を調べる
- [x] 0 件なら INSERT、1 件以上なら DELETE に分岐

### 5. いいね数を表示する
- [x] `GROUP BY article_id` ＋ `COUNT(id)` で集計する SQL を確認
- [x] `articles LEFT OUTER JOIN (集計) AS result_table ON articles.id = result_table.article_id`
- [x] 一覧・詳細に「♥ ◯」を表示。自分がいいね済みなら色を変える

### 6. 仕上げ
- [x] `php -l` で全ファイル構文チェック
- [x] 未ログイン／ログイン済の両方で画面確認
- [x] `README.md` に機能を追記
- [x] `tasks/lessons.md` に学びを追記

---

## 安全対策

- `top.php` / `article.php` は書き換える前に `.bak` を作る
- `includes/articles.php` は **削除しない**（読み込みをやめるだけ。中身は記事投入の元データ）
- 削除系コマンドは使わない

## 後続作業（今回のスコープ外）

- 本番（さくら）側の DB にも同じ 2 テーブルを作り、記事を投入する必要がある
- いいねの取り消しを画面遷移なし（jQuery）にする

---

## レビュー（実装完了）

### 作ったもの
| ファイル | 内容 |
|---|---|
| `includes/db_setup_like.sql` | 新規。articles / like_table の CREATE ＋記事3件の INSERT ＋練習用SQL（コメント） |
| `like_create.php` | 新規。ログイン必須 → COUNT → 0件なら INSERT / それ以外は DELETE → 元の画面へ |
| `includes/like_button.php` | 新規。いいねボタンの共通部品（`like_button()` 関数） |
| `top.php` | articles から一覧取得（GROUP BY ＋ LEFT OUTER JOIN）。ハート設置 |
| `article.php` | articles から1件取得。body を空行で段落に分割。ハート設置 |
| `includes/articles.php` | 冒頭に「現在未使用」の注記のみ追加（中身は移行元の控えとして保持） |
| `top.php.bak` / `article.php.bak` | 書き換え前の控え |

### 実装の要点
- 記事本文は `TEXT` 1列に「段落を空行区切り」で保存し、表示時に `preg_split('/\R{2,}/u', ...)` で分割。
  段落ごとにテーブルを作るのは今回の規模では過剰なため。
- いいね数は列で持たず、毎回 `GROUP BY` で集計。`LEFT OUTER JOIN` で0件の記事も一覧に残す。
- 「自分がいいね済みか」は、一覧では自分の `article_id` を一括取得して `in_array`、
  詳細では `COUNT(*)` で1件だけ確認する。
- 押した人は `$_SESSION['user_id']` から取得（講義のURL渡しは採用しない）。

### 検証結果（すべて実機で確認）
| 確認項目 | 結果 |
|---|---|
| `php -l` 構文チェック（5ファイル） | 合格 |
| 表2つ作成・記事3件投入 | OK（本文の空行区切りも正常） |
| 結合SQLが3記事すべて返す（0件でも消えない） | OK |
| 未ログインでTOP・記事詳細（HTTP 200・エラー0件） | OK |
| 未ログインに続き本文が漏れていない | OK（本文の語句が0件） |
| ログイン後にいいね → `like_table` に1行 | OK |
| 同じ記事をもう一度 → 行が削除される | OK |
| 未ログインでいいね → login.php へ追い返す | OK |
| `back=article` で元の記事へ戻る | OK |

---

## 追加実装：マイページに「いいねした記事」一覧

### やったこと
- `mypage.php` に、自分がいいねした記事の一覧を追加（新しくいいねした順）
- タイトルはリンクになっており、クリックすると `article.php?id=●` へ移動
- 0 件のときは「まだいいねした記事はありません」＋記事一覧への案内を表示
- 書き換え前の控えとして `mypage.php.bak` を作成

### SQL（`like_table` × `articles` の結合）
```sql
SELECT articles.id, articles.title, like_table.created_at AS liked_at
FROM like_table
  INNER JOIN articles ON like_table.article_id = articles.id
WHERE like_table.user_id = :user_id
ORDER BY like_table.created_at DESC
```

ここは `INNER JOIN` が正解。「いいねした記事だけ」を出したいので、
いいねの記録が無い記事は結果に出てこなくて良い。
（記事一覧側で `LEFT OUTER JOIN` を使ったのは、いいね0件の記事も残したかったから）

### 検証結果
| 確認項目 | 結果 |
|---|---|
| `php -l` 構文チェック | 合格 |
| いいね2件の会員でマイページ表示 | OK（新しい順に2件、リンク先の id も正しい） |
| リンクが `article.php?id=●` になっている | OK |
| いいね0件のときの表示 | OK（「まだいいねした記事はありません」を表示） |
| いいねを外す → 一覧から消える → 戻すと再表示 | OK |

---

## テスト用会員（動作確認用に追加）

`includes/db_setup_testusers.sql` で3名追加。パスワードは全員 `test1234`。

| 名前 | メールアドレス |
|---|---|
| 山田花子 | test1@login.com |
| 佐藤次郎 | test2@login.com |
| 鈴木三郎 | test3@login.com |

複数会員で同じ記事にいいねし、数が正しく積み上がることを確認済み（多対多の動作確認）。

---

## 残作業
- 本番（さくら）DB にも `articles` / `like_table` を作り、記事を投入する（`db_setup_like.sql` を実行）
- `includes/db_setup_testusers.sql` を Git に含めるか判断する（メール・パスワードが平文で書かれている）
- いいねを画面遷移なし（jQuery）にする
