# Claude Code 実装指示プロンプト（DB版）

## あなたの役割

PHPを学習中の学生の実装をサポートするエンジニアとして動いてください。
添付の`PRD_DB版.md`をベースに、CSV版で完成している「メール認証付き会員登録システム」のデータ保存先をMySQLに移行してください。

**前提：CSV版のコードはすでに完成しています。今回はゼロから作るのではなく、データ操作部分（保存・取得・更新）をCSVからPDO + SQLに置き換える作業です。**

---

## 実装の前提条件

- 実行環境：XAMPP（ローカル開発）
- DB：phpMyAdminで`member_system_db`データベース・`users`テーブルを作成済み（PRD_DB版.md の「7. DB / テーブル設計」を参照）
- PHPとHTMLは同じ`.php`ファイルに共存させる（CSV版から変更なし）
- CSSとJSは別ファイルのまま（`css/style.css`、`js/main.js`）
- Tailwind CSS・jQueryはCDN版のまま
- ページ間のデータ引き回しは引き続き`$_SESSION`を使う（変更なし）
- メール送信は引き続きPHPの`mail()`関数を使用（変更なし）
- データ保存・取得・更新は**PDO**を使用し、SQLインジェクション対策として**必ずバインド変数**を使う

---

## 実装の進め方

**必ずファイルを1つずつ修正し、各ファイルの動作確認ポイントを示してから次に進んでください。**
いきなり全ファイルを一括変更しないこと。

修正順序：
1. `send_mail.php`（CSVへの保存 → PDOでINSERT）
2. `verify.php`（CSV照合 → PDOでSELECT・UPDATE）
3. `list.php`（CSV読み込み → PDOでSELECT）
4. `index.php` / `confirm.php`（変更不要だが、動作確認のため最後に通しテスト）

---

## コードの書き方ルール

### PHP / PDO
- 処理の意図がわかるように**日本語コメントを必ず書く**
- DB接続処理は以下の形式に統一する（PRD_DB版.md の「8. PDO接続情報」を参照）

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

- SQL文は必ず`prepare()` → `bindValue()` → `execute()`の順で実行する
- ユーザーが入力した値をSQL文に直接埋め込まない（必ずバインド変数を使う）
- `INSERT`/`UPDATE`の実行時は`try-catch`でエラーハンドリングする
- `SELECT`の結果取得は`fetchAll(PDO::FETCH_ASSOC)`を使う
- `UPDATE`文には必ず`WHERE`を指定する（全件更新の事故防止）

### 変更しないもの
- `$_POST`を受け取った際の`htmlspecialchars()`によるXSS対策
- `$_SESSION`を使ったページ間のデータ引き回し
- `bin2hex(random_bytes(32))`によるトークン生成
- バリデーションルール・jQueryでのフロント側チェック

---

## 各ファイルの完成基準

| ファイル | 完成基準 |
|---------|---------|
| `send_mail.php` | セッションから値を取り出し、PDOでDBにINSERTされる。認証メールが送信され、セッションが破棄される |
| `verify.php` | URLのトークンをSELECTで照合し、一致すればUPDATEでstatusが`active`になる |
| `list.php` | PDOのSELECTでDBの内容が一覧表示される |
| 全体の通しテスト | index.php入力 → confirm.php確認 → send_mail.phpでDB保存・メール送信 → verify.phpで本登録 → complete.php表示 → list.phpで確認、までが一通り動作する |

---

## 注意事項

- スコープ外の機能（ログイン・パスワードハッシュ化・トークン有効期限・重複メールチェック）は**実装しない**
- `data/`フォルダやCSV関連のコード（`fopen`/`fwrite`/`fgets`など）は削除する
- 実装中に不明点があれば実装を止めて質問すること
- 各ファイルを修正したら、phpMyAdmin側でどう確認すればよいかも一緒に教えること（例：「表示」タブでレコードを確認）

---

## 参照ファイル

`PRD_DB版.md` を必ず参照して実装すること。
CSV版の実装（既存コード）は壊さないよう、変更箇所を最小限にすること。
