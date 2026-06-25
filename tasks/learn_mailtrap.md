# Mailtrap でメール送信できるようにする（学びメモ）

XAMPP（Mac）で、会員登録フォームから確認メールを送れるようにした手順のまとめ。

---

## 0. まず知っておくこと

- **Mac版XAMPPには「メールを送る部品」が入っていない**（sendmail が無い）
- **Mac では php.ini の `SMTP=...` 設定は無視される**（あれはWindows専用）
- → だから「PHPMailer というライブラリ」を使って送るのが確実で定番

---

## 1. Mailtrap とは

- **テスト用のメールサービス**。本物の受信箱には届かず、**おためし受信箱に捕まえて**中身を確認できる
- 開発中に誤って本物のメールへ送る事故を防げる
- 無料・カード登録不要

---

## 2. PHPMailer とは

- **PHPからメールを送る作業を代行してくれる、世界的な定番ライブラリ**
- 面倒な送信手続きを肩代わりしてくれる（宛先・件名・本文・接続先を渡すだけ）
- システムには入れず、**プロジェクト内にファイルを置くだけ**

---

## 3. 実際にやった手順

### ① PHPMailer のファイルを入手
Composer が無いので、必要な3ファイルを手動ダウンロードして `PHPMailer/src/` に配置：
- `PHPMailer.php` / `SMTP.php` / `Exception.php`

### ② 接続情報の設定ファイルを作成（`mail_config.php`）
Mailtrap の Host / Port / Username / Password を1か所にまとめた。
（パスワード等の秘密情報をコード本体に直書きしないため、別ファイルに分離）

### ③ `send_mail.php` を書き換え
- CSV保存などの処理は**そのまま**
- メール送信部分だけ `mb_send_mail` → **PHPMailer の SMTP送信** に置き換え
- 元ファイルは `send_mail.php.bak` としてバックアップ

### ④ Mailtrap に登録して接続情報を取得
- mailtrap.io で **Sign Up**（無料）
- **「Email Sandbox」→「Start Testing」** を選ぶ（※Live sending は本番用なので選ばない）
- 受信箱の **Integration → SMTP** に Username / Password が表示される
- その値を `mail_config.php` に貼り付け

### ⑤ 送信テスト → 成功

---

## 4. つまずいた点と対処

### 権限エラー（Permission denied）
```
fopen(.../data/users.csv): Failed to open stream: Permission denied
```
- **原因**：Webサーバーは「daemon」というユーザーで動いており、
  「oshiosan」所有の `data` フォルダに書き込めなかった
- **対処**：`chmod 777 data` で data フォルダを書き込み可能にした
- ※ daemon = 人間ではなく、安全のため権限を弱くした「Web担当の作業員アカウント」

---

## 5. Mailtrap での確認方法（届かないけど見られる）

1. フォーム送信 → メールは **Mailtrap のおためし受信箱**に届く
2. Mailtrap を開いてメールを確認
3. 本文の **認証リンク（http://localhost/.../verify.php?token=...）をクリック**
   - `localhost` = 自分のパソコン。自分のPCのブラウザで開けば動く
4. verify.php が status を `active` に更新 → **完了画面へ**

---

## 6. 用語のかんたん辞書

- **SMTP** … メールを送るための通信のしくみ／送信サーバー
- **Sandbox** … 砂場＝おためし環境（本番に影響しない）
- **ライブラリ** … 便利な機能をまとめた既製の部品
- **chmod 777** … 「全員に読み書きを許可」する権限設定
- **daemon** … 裏で働くプログラム用のユーザー（Webサーバー担当）
