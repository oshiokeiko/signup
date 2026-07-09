# PRD：会員データの更新（UPDATE）・削除（DELETE）機能の追加

**作成日：** 2026-07-02
**ステータス：** 確定
**対象：** PHP基礎課題（CRUD回）/ DB版会員登録システムの拡張
**ベース：** `PRD_DB.md`（DB移行版）＋ AI作成の要求定義（確定版）

---

## 1. 概要

DB（PDO）版として完成している会員登録システムに、**更新（UPDATE）** と **削除（DELETE）** の機能を追加する。
`list.php`（一覧）から**1レコードごとの詳細（編集）ページ**に遷移し、そこで名前の更新・削除を行う画面遷移パターンを実装する。

対象は `users` テーブル。削除は**論理削除**（実データは残し、削除済みフラグを立てる方式）とする。

---

## 2. 要求定義からの変更点（実装に合わせた調整）

AI作成の要求定義を、**現在の実装に合わせて1点だけ調整**した。

| 要求定義 | 本PRDでの扱い | 理由 |
|---|---|---|
| `functions.php` に `connect_to_db()` を作り `include()` する | **作らない。既存の `db_config.php` を使い回す** | 本プロジェクトはすでに `db_config.php`（`require` で `$pdo` を用意）で接続を共通化済み。同じ役割の `functions.php` を新設すると重複するため。 |

→ 新しいページ（member_detail.php など）でも、DB接続は既存と同じ1行で行う：

```php
require __DIR__ . '/db_config.php';   // これで $pdo が使える
```

その他（詳細ページ方式・論理削除・編集は name のみ・自作モーダル）は要求定義どおり。

---

## 3. テーブル設計への影響

`users` テーブルに、論理削除用のカラムを1つ追加する。

| カラム名 | データ型 | 長さ | その他設定 | 用途 |
|---|---|---|---|---|
| `deleted_at` | DATETIME | - | **NULL許可** | 論理削除フラグ。未削除時は `NULL`、削除時に日時をセット |

既存カラム（`id, name, email, password, token, status, created_at, updated_at`）はそのまま。

> phpMyAdminでの追加手順：`users` テーブル →「構造」タブ → カラム追加 → 名前 `deleted_at` / 型 `DATETIME` / **NULL にチェック** / デフォルト `NULL`。
> ※ ローカル(`member_system_db`)・本番(さくら `gsoshio_signup`)の**両方**に追加すること。

---

## 4. 画面・ファイル構成

### 既存（変更あり）

| ファイル | 変更内容 |
|---|---|
| `list.php` | ①SELECTに `WHERE deleted_at IS NULL` を追加（削除済みを非表示）②各行に「詳細」リンク（`member_detail.php?id=●`）を追加 |

### 新規

| No | ファイル | 役割 |
|----|---------|------|
| 1 | `member_detail.php` | 詳細・編集画面（名前の編集フォーム＋削除フォーム＋削除確認モーダル） |
| 2 | `member_update.php` | 更新処理（`name` を UPDATE）→ list.php へリダイレクト |
| 3 | `member_delete.php` | 削除処理（論理削除。`deleted_at` に日時をセット）→ list.php へリダイレクト |

### 画面遷移

```
[list.php]  一覧（削除済みは非表示）
    │  「詳細」リンク（?id=●）
    ↓
[member_detail.php]  詳細・編集
    ├─ 更新フォーム（name）── POST ──▶ [member_update.php] ─▶ list.php
    └─ 削除フォーム（id）
          │ 「削除」ボタン → 自作モーダルで確認
          │ 「削除する」── POST ──▶ [member_delete.php] ─▶ list.php
          └ 「キャンセル」→ JSでモーダルを閉じるだけ
```

---

## 5. 各ファイルの仕様

### 5-1. list.php（変更）

- **SELECT文を変更**：削除済みを除外する
  ```sql
  SELECT * FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC;
  ```
- 表に「操作」列を追加し、各行に詳細リンクを置く
  ```php
  <a href="member_detail.php?id=<?php echo (int)$user['id']; ?>">詳細</a>
  ```
  ※ `id` は数値なので `(int)` で整数に変換してから埋め込む（安全のため）
- 登録件数の表示も、削除済みを除いた件数になる（SELECT結果の件数のまま）

### 5-2. member_detail.php（新規）

- `require __DIR__ . '/db_config.php';` で `$pdo` を用意
- URLの `id` を受け取り、数値チェック（無い・不正なら list.php へ戻す）
  ```php
  $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
  if (!$id) { header('Location: list.php'); exit; }
  ```
- **SELECT（prepared statement）で1件取得**。削除済みは取得しない
  ```sql
  SELECT * FROM users WHERE id = :id AND deleted_at IS NULL;
  ```
  - `fetch(PDO::FETCH_ASSOC)` で1件取得。見つからなければエラー表示 or list.php へ戻す
- 画面に**目的の異なる2つの `<form>`** を配置する
  - **更新フォーム**：`name` の入力欄（初期値は現在の名前）＋ `id`(hidden) ＋「保存」ボタン → `member_update.php` へ `method="post"`
  - **削除フォーム**：`id`(hidden) ＋「削除」ボタン → `member_delete.php` へ `method="post"`（送信は後述モーダルの「削除する」押下時のみ）
- `email`・`password`・`status`・登録日時は**表示のみ**（編集不可。`readonly` かテキスト表示）
- 全ての表示値は `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` でXSS対策

### 5-3. 削除確認モーダル（member_detail.php 内・自作）

- HTML/CSS/JS（jQueryまたは素のJS）で作り込む
- 初期状態は非表示。削除フォームの「削除」ボタンで表示
- モーダル内：
  - 「本当に削除しますか？」＋対象の名前を表示
  - 「削除する」ボタン → 削除フォームを `submit()`（member_delete.php へPOST）
  - 「キャンセル」ボタン → JSでモーダルを閉じるだけ（送信しない）
- 見た目は既存ページに合わせ Tailwind CSS（CDN）で作る（半透明の背景＋中央カード）

### 5-4. member_update.php（新規・更新処理）

- `require __DIR__ . '/db_config.php';`
- POSTの `id`・`name` を受け取り**チェック**
  - `id`：整数か（`filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT)`）
  - `name`：`isset` かつ 空文字でない（`trim` して空でないか）
  - 不正なら list.php へ戻す
- **UPDATE（prepared statement・バインド変数）**。WHERE を必ず指定
  ```sql
  UPDATE users SET name = :name, updated_at = now() WHERE id = :id;
  ```
- `try-catch` でエラーハンドリング
- 成功時：`header('Location: list.php'); exit;` でリダイレクト

### 5-5. member_delete.php（新規・削除処理／論理削除）

- `require __DIR__ . '/db_config.php';`
- POSTの `id` を受け取り整数チェック（不正なら list.php へ戻す）
- **論理削除：物理DELETEはしない。`deleted_at` に日時をセットするUPDATE**
  ```sql
  UPDATE users SET deleted_at = now() WHERE id = :id;
  ```
- `try-catch` でエラーハンドリング
- 成功時：`header('Location: list.php'); exit;` でリダイレクト

> 【重要】UPDATE・削除ともに **`WHERE id = :id` を必ず指定**する（忘れると全件が更新・削除される事故になる）。

---

## 6. 入力・バリデーション

| 項目 | 場所 | チェック |
|---|---|---|
| `id` | detail/update/delete | 整数であること（`FILTER_VALIDATE_INT`）。不正なら list.php へ |
| `name` | update | 必須・空文字不可（`trim`後に判定） |

- `email` / `password` / `status` は今回**編集対象外**（更新しない）
- ユーザー入力値は必ず**バインド変数**でSQLに渡す（SQLインジェクション対策）
- 画面表示は必ず `htmlspecialchars()`（XSS対策）

---

## 7. コードの書き方ルール（既存踏襲）

- DB接続は `require __DIR__ . '/db_config.php';`（`$pdo` を使う）
- SQLは `prepare()` → `bindValue()` → `execute()` の順
- SELECT取得は `fetch()` / `fetchAll(PDO::FETCH_ASSOC)`
- INSERT/UPDATEは `try-catch` でエラーハンドリング
- 処理の意図がわかる**日本語コメント**を書く
- 見た目は Tailwind CSS（CDN）＋ `css/style.css`、既存ページのカードデザインに合わせる

---

## 8. 実装ステップ

1. phpMyAdmin（ローカル・さくら両方）で `users` に `deleted_at`（DATETIME・NULL許可）を追加
2. `list.php` を変更（SELECTに `WHERE deleted_at IS NULL`、「詳細」リンク追加）
3. `member_detail.php` を実装（SELECT＋name編集フォーム＋削除フォーム＋モーダル）
4. `member_update.php` を実装（UPDATE name ＋ WHERE id）
5. `member_delete.php` を実装（UPDATE deleted_at ＋ WHERE id）
6. 動作確認（下記チェック）

---

## 9. 動作確認（完成基準）

| 確認 | 期待結果 |
|---|---|
| list.php の「詳細」リンク | member_detail.php?id=● に遷移し、その人の情報が表示される |
| 名前を変更して「保存」 | list.php に戻り、名前が変わっている。phpMyAdminで `name` と `updated_at` が更新されている |
| 「削除」→モーダル「キャンセル」 | 何も起きない（削除されない） |
| 「削除」→モーダル「削除する」 | list.php から消える。phpMyAdminで `deleted_at` に日時が入っている（レコード自体は残っている） |
| 削除済みの詳細URLに直接アクセス | 表示されない（list.php へ戻る） |
| `WHERE` の指定 | UPDATE・削除で対象の1件だけが変わる（全件が変わらない） |

---

## 10. スコープ外（今回はやらない）

- 削除済みデータの復元（`deleted_at` を NULL に戻す）画面
- `email` / `password` / `status` の編集
- ログイン・権限管理（list.php 等のアクセス制限）
- ページネーション・検索・並び替え
- 物理削除（`DELETE FROM`）

---

## 11. 学習ポイント（授業との対応）

| 実装内容 | 対応する授業内容 |
|---|---|
| 一覧→詳細→更新／削除の画面遷移 | todoリスト演習のCRUD構成 |
| GETで id を渡し1件取得 | `SELECT ... WHERE id = :id` + prepared statement |
| 名前の更新 | `UPDATE ... SET name = :name WHERE id = :id` |
| 論理削除 | `UPDATE ... SET deleted_at = now() WHERE id = :id`（物理削除しない考え方） |
| 削除済みの非表示 | `WHERE deleted_at IS NULL` |
| 削除前の確認 | 自作モーダル（HTML/CSS/JS） |
| 接続の共通化 | `db_config.php` の `require` による再利用 |
