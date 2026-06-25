<?php
// ----------------------------------------------------------
//  
// 実際にメールを送る作業をするもの
//
// ----------------------------------------------------------

// セッションを開始する（confirm.php で保存した値を取り出すため）
session_start();

// セッションに値がなければ（直接アクセスなど）入力画面へ戻す
if (empty($_SESSION['name']) || empty($_SESSION['email']) || empty($_SESSION['password'])) {
    header('Location: index.php');
    exit;
}

// ① セッションから値を取り出す
$name     = $_SESSION['name'];
$email    = $_SESSION['email'];
$password = $_SESSION['password'];

// ② 認証トークン（推測されにくいランダムな文字列）を生成する
$token = bin2hex(random_bytes(32));

// 登録日時（現在時刻）を作成する
$created_at = date('Y-m-d H:i:s');

// ③ 仮登録データをCSVに保存する -------------------------------------------------
// 保存先：data/users.csv
$csvFile = __DIR__ . '/data/users.csv';

// CSVに書き込む1行ぶんのデータ（カラム順：name, email, password, token, status, created_at）
// status は仮登録を表す 'pending' で保存する
$row = [$name, $email, $password, $token, 'pending', $created_at];

// ファイルを追記モード（'a'）で開く。なければ自動で作成される。
$fp = fopen($csvFile, 'a');
if ($fp === false) {
    exit('CSVファイルを開けませんでした。data フォルダの権限を確認してください。');
}

// 同時書き込みでデータが壊れないようにロックをかける
flock($fp, LOCK_EX);

// ファイルが空（＝初回）ならヘッダー行を先に書き込む
if (filesize($csvFile) === 0) {
    fputcsv($fp, ['name', 'email', 'password', 'token', 'status', 'created_at']);
}

// データ行を書き込む（fputcsv はカンマ区切り・改行を自動でやってくれる）
fputcsv($fp, $row);

// ロックを解除してファイルを閉じる
flock($fp, LOCK_UN);
fclose($fp);

// ④ 認証URL付きのメールを送信する -----------------------------------------------
// 現在アクセスしているホスト（localhost など）と、このファイルのある場所から
// verify.php のURLを自動で組み立てる
$host    = $_SERVER['HTTP_HOST'];                       // 例：localhost
$dir     = dirname($_SERVER['PHP_SELF']);               // 例：/gs/07_signup
$baseUrl = 'http://' . $host . rtrim($dir, '/');        // 例：http://localhost/gs/07_signup
$verifyUrl = $baseUrl . '/verify.php?token=' . $token;  // 認証用URL

// メールの件名
$subject = '【会員登録】メールアドレスの確認';

// メールの本文（認証URLを記載する）
$body  = $name . " 様\n\n";
$body .= "会員登録ありがとうございます。\n";
$body .= "下記のURLをクリックすると、登録が完了します。\n\n";
$body .= $verifyUrl . "\n\n";
$body .= "※このメールに心当たりがない場合は破棄してください。\n";

// --- PHPMailer を使って Mailtrap 経由で送信する ---
// PHPMailer の本体ファイルを読み込む（Composer を使わず手動で読み込む）
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 接続情報（mail_config.php）を読み込む
$mailConfig = require __DIR__ . '/mail_config.php';

// 送信できたかどうかのフラグ
$mailResult = false;

$mail = new PHPMailer(true); // true = エラー時に例外を投げる
try {
    // SMTP（メール送信サーバー）を使う設定
    $mail->isSMTP();
    $mail->Host       = $mailConfig['host'];       // 接続先サーバー
    $mail->SMTPAuth   = true;                       // ログイン認証を使う
    $mail->Username   = $mailConfig['username'];    // Mailtrap のユーザー名
    $mail->Password   = $mailConfig['password'];    // Mailtrap のパスワード
    $mail->Port       = $mailConfig['port'];        // ポート番号
    $mail->CharSet    = 'UTF-8';                     // 日本語の文字化け対策

    // 差出人と宛先
    $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
    $mail->addAddress($email, $name);

    // 件名と本文
    $mail->Subject = $subject;
    $mail->Body    = $body;

    // 送信
    $mail->send();
    $mailResult = true;
} catch (Exception $e) {
    // 送信に失敗した場合は $mailResult が false のまま。
    // 詳しい原因を知りたい場合は $mail->ErrorInfo に入る。
    $mailResult = false;
}

// ⑤ セッションを破棄する（仮登録が終わったので入力値はもう不要）
session_destroy();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会員登録 | メール送信</title>
  <!-- Tailwind CSS（CDN版） -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md bg-white rounded-2xl shadow-md p-8 text-center">
    <!-- メールアイコン（絵文字で簡易表示） -->
    <div class="text-5xl mb-4">📧</div>
    <h1 class="text-2xl font-bold text-gray-800 mb-3">確認メールを送信しました</h1>

    <?php if ($mailResult): ?>
      <!-- 送信成功時のメッセージ -->
      <p class="text-gray-600 leading-relaxed mb-2">
        ご入力いただいたメールアドレス宛に<br>確認メールをお送りしました。
      </p>
      <p class="text-gray-600 leading-relaxed">
        メール内のURLをクリックすると<br>会員登録が完了します。
      </p>
    <?php else: ?>
      <!-- 送信失敗時のメッセージ（メール設定の確認を促す） -->
      <p class="text-red-500 leading-relaxed">
        メールの送信に失敗しました。<br>
        メールサーバー（Mailtrap等）の設定を確認してください。
      </p>
    <?php endif; ?>

    <a href="index.php" class="inline-block mt-6 text-blue-600 hover:underline text-sm">
      入力画面に戻る
    </a>
  </div>

</body>
</html>
