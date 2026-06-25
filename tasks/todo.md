# メール認証付き会員登録システム — 実装計画

PRD.md / prompt.md に基づく実装。**1ファイルずつ**完成させてから次へ進む。

## 実装の前提
- 環境：XAMPP（ローカル）
- PHPとHTMLは同じ `.php` に共存、CSS/JSは別ファイル
- Tailwind CSS（CDN）、jQuery（CDN）
- データ保存：CSV（`data/users.csv`）、MySQLは使わない
- ページ間のデータ引き回しは `$_SESSION`（hiddenは使わない）
- メール送信は `mail()` 関数（Mailtrap経由想定）

## チェックリスト
- [ ] フォルダ構成を作成（css / js / data）
- [ ] index.php（入力フォーム）
- [ ] confirm.php（確認画面）
- [ ] send_mail.php（メール送信＋CSV保存）
- [ ] verify.php（トークン照合・本登録）
- [ ] complete.php（登録完了）
- [ ] list.php（ユーザー一覧・管理用）
- [ ] css/style.css（共通スタイル補足）
- [ ] js/main.js（バリデーション）

## スコープ外（実装しない）
- ログイン・ログアウト
- パスワードのハッシュ化
- トークン有効期限
- 重複メールチェック
- MySQL移行

## レビュー（実装完了）

全8ファイル＋data保護用 .htaccess を実装完了。PHP構文チェック（php -l）は全ファイル合格。

実装の要点：
- index.php … 入力フォーム。「戻る」で戻ったときセッションから名前・メールを復元（パスワードは安全のため復元しない）
- confirm.php … POST受信→$_SESSIONに保存→確認表示。直接アクセスはindex.phpへ戻す
- send_mail.php … トークン生成（bin2hex(random_bytes(32))）→CSVへ追記（flockでロック、初回はヘッダー行）→mb_send_mailで認証URL送信→session_destroy
- verify.php … GETのトークンをCSVと照合（hash_equalsで安全比較）→statusをactiveに更新（ファイル丸ごと書き直し）→complete.phpへ
- complete.php … 完了メッセージ
- list.php … CSVを表で一覧表示。状態をバッジ表示。全出力をhtmlspecialchars
- css/style.css … エラー時の赤枠・フェードイン等の補足
- js/main.js … 名前/メール/パスワードのバリデーション（blur時＋送信時）、エラーは入力欄直下に表示

スコープ外（PRD通り未実装）：ログイン、パスワードハッシュ化、トークン有効期限、重複メールチェック、MySQL移行

残課題（環境依存）：メール送信を実際に飛ばすには XAMPP の php.ini に Mailtrap の SMTP 設定が必要。
