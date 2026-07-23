# 更新（UPDATE）・削除（DELETE）機能の学びまとめ

**作成日：2026-07-02**
会員登録システムに「編集（更新）」と「削除」を追加したときに学んだことの整理。
（関連：DB移行・本番デプロイの流れは `learn_sakura_db.md` を参照）

---

## 0. 何を作ったか

一覧から1人を選んで、**名前を編集（更新）** したり、**削除** したりできるようにした。

```
[list.php]  一覧（削除済みは出ない）
     │  各行の「詳細」リンク（?id=●）
     ↓
[member_detail.php]  詳細・編集ページ
     ├─ 名前を編集 →「保存」→ [member_update.php] → 一覧へ戻る
     └─「削除」→ 確認モーダル →「削除する」→ [member_delete.php] → 一覧へ戻る
```

追加・変更したファイル：
- `list.php`（変更）… 削除済みを隠す＋「詳細」リンク追加
- `member_detail.php`（新規）… 編集フォーム＋削除フォーム＋確認モーダル
- `member_update.php`（新規）… 名前を更新する処理
- `member_delete.php`（新規）… 削除する処理（論理削除）

---

## 1. CRUDの基本の流れ（一覧→詳細→更新／削除）

「編集・削除は、まず**一覧から1件を選んで詳細ページに行き**、そこで操作する」という画面の流れが定番。
- 一覧の各行に、その人の `id` を付けたリンクを置く：`member_detail.php?id=5`
- 詳細ページは、その `id` を使って「その1件」をDBから取ってくる

**学び：** どのレコードを操作するかは **`id`（通し番号）で指定**する。URLやhiddenでこの `id` を次のページへ渡していく。

---

## 2. GETで受け取った id は必ず「整数チェック」する

URLの `?id=●` は誰でも書き換えられるので、そのまま信用しない。

```php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: list.php');  // 不正なら一覧へ戻す
    exit;
}
```

**学び：** `FILTER_VALIDATE_INT` で「整数かどうか」を確認。数字でなければ処理を止めて一覧へ戻す。安全対策の基本。

---

## 3. 1ページに「目的の違う2つのフォーム」を置く

`member_detail.php` には2つの `<form>` を置いた。
- **更新フォーム** … `name` の入力欄 ＋ `id`(hidden) → `member_update.php` へPOST
- **削除フォーム** … `id`(hidden) だけ → `member_delete.php` へPOST

**学び：** フォームは「送信先（action）」ごとに分ける。どのレコードかを伝えるため、`id` を `<input type="hidden">` で一緒に送る。

---

## 4. UPDATE文には必ず WHERE を付ける（超重要）

```sql
UPDATE users SET name = :name, updated_at = now() WHERE id = :id
```

- `WHERE id = :id` を**忘れると全員の名前が同じに書き換わる**大事故になる。
- 入力値は直接埋め込まず、必ず**バインド変数**（`:name` `:id`）で渡す（SQLインジェクション対策）。

**学び：** 「UPDATE と DELETE は WHERE とセット」と体で覚える。

---

## 5. 削除は「論理削除」にした（データを消さない削除）

実際に行を消す（物理削除 `DELETE FROM`）のではなく、
**「削除した印（deleted_at に日時）」を付けるだけ**にした。

```sql
-- 削除処理（実データは残す）
UPDATE users SET deleted_at = now() WHERE id = :id
```

そして一覧・詳細では「まだ削除されていないもの」だけを出す：

```sql
SELECT * FROM users WHERE deleted_at IS NULL ...
```

| | 物理削除（DELETE FROM） | 論理削除（今回） |
|---|---|---|
| データ | 完全に消える | 残る（`deleted_at` に日時） |
| 復元 | できない | できる（`deleted_at` を NULL に戻せば） |
| 一覧表示 | 消える | `WHERE deleted_at IS NULL` で隠す |

**学び：** 実務では「間違えて消しても戻せる」論理削除がよく使われる。`deleted_at` が NULL＝生きている、日時入り＝削除済み。

---

## 6. 削除前に「確認モーダル」でワンクッション

いきなり削除せず、「本当に削除しますか？」の自作モーダル（HTML/CSS/JS）を挟んだ。
- 「削除」ボタンは `type="button"` にして、**すぐには送信しない**
- JS（jQuery）でモーダルを表示 → 「削除する」で初めてフォームを `submit()`
- 「キャンセル」はモーダルを閉じるだけ（何もしない）

**学び：** 取り返しのつかない操作は、送信の前に確認を入れる。ボタンの `type="button"` と `type="submit"` の使い分けがポイント。

---

## 7. テーブルにカラムを1つ追加した（deleted_at）

論理削除のために `users` テーブルへ追加：

```sql
ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL;
```

- **NULL許可**にするのが重要（未削除のとき空にできる）
- **ローカルと本番（さくら）の両方**に追加が必要
- phpMyAdmin の「SQL」タブに上記を貼って「実行」でOK
- `ALTER TABLE` の結果は「返り値が空でした（行数 0）」と出るが、**緑チェックが出れば成功**（データを取り出す命令ではないので0行が正常）

**学び：** 表の構造を変えるのは `ALTER TABLE`。機能追加でカラムが増えたら、コードだけでなく**DBの構造も両方の環境で**変える。

---

## 8. db_config.php を「自動切り替え」に改良

ローカルと本番でDB接続先が違う問題を、1ファイルで解決した。
アクセス元が `localhost` かどうかで、接続情報を自動で切り替える。

```php
$host_name = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($host_name, 'localhost') !== false) {
    // ローカル用の接続情報
} else {
    // 本番用の接続情報
}
// このあと $pdo を作る
```

**学び：** 「毎回手で書き換える」運用は書き換え忘れ事故のもと。環境で自動判定させると、同じファイルをそのままアップできて安全（db_config.php は Git管理外のまま）。

---

## 9. 本番（さくら）へ反映した手順

1. さくらの phpMyAdmin で `users` に `deleted_at` を追加（上記SQL）
2. FileZillaで新規・変更ファイルをアップ
   - 新規：member_detail.php / member_update.php / member_delete.php
   - 変更：list.php / send_mail.php / db_config.php
3. ブラウザで一覧→詳細→更新／削除を実際に試して確認
4. phpMyAdmin で、削除した人の `deleted_at` に日時が入り、データは残っていることを確認

**学び：** 「コードのアップ」と「DBの構造変更」はセットで本番反映する。片方だけだとエラーになる。

---

## 10. 完成後のチェックリスト（次回も使える）

- [ ] URLの `id` を整数チェックしているか
- [ ] UPDATE・削除に `WHERE id = :id` を付けたか
- [ ] 入力値をバインド変数で渡しているか
- [ ] 削除は論理削除（`deleted_at`）にしたか／一覧は `WHERE deleted_at IS NULL` か
- [ ] 削除前に確認モーダルを入れたか
- [ ] DBのカラム追加を**ローカル・本番の両方**でやったか
- [ ] デバッグ用の一時コード（`ini_set('display_errors', 1)` 等）を消したか

---

## 11. 用語（かんたん言い換え）

| 用語 | かんたんに言うと |
|------|----------------|
| CRUD | 作る(Create)・読む(Read)・更新(Update)・削除(Delete)の4操作 |
| 論理削除 | 実データは消さず「削除済みの印」を付ける削除。戻せる |
| 物理削除 | 本当にデータを消す削除（`DELETE FROM`）。戻せない |
| バインド変数 | 入力値を安全にSQLへ渡す仕組み（`:id` など） |
| モーダル | 画面の上に重ねて出る確認用の小窓 |
| ALTER TABLE | 表の構造（カラム）を変えるSQL |
| hidden | 画面に見えないが一緒に送られる入力欄（idを渡すのに使う） |
