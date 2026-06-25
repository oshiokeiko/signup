<?php
// セッションを開始する（入力値をページ間で引き回すために使用）
session_start();

// index.php から POST で送られてきたかチェックする。
// 直接 confirm.php を開いた場合（POSTでない）は入力画面に戻す。
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POSTで受け取った値をセッションに保存する。
    // ※ 名前・メールは画面に表示するので htmlspecialchars でXSS対策をする
    $_SESSION['name']     = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $_SESSION['email']    = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
    // パスワードは画面に表示しないが、確認画面では「●」で伏せて見せる
    $_SESSION['password'] = $_POST['password'] ?? '';
}

// セッションに値がなければ（直接アクセスなど）入力画面へ戻す
if (empty($_SESSION['name']) || empty($_SESSION['email']) || empty($_SESSION['password'])) {
    header('Location: index.php');
    exit;
}

// セッションから値を取り出して表示用の変数に入れる
$name     = $_SESSION['name'];
$email    = $_SESSION['email'];
$password = $_SESSION['password'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会員登録 | 確認</title>
  <!-- Tailwind CSS（CDN版） -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md bg-white rounded-2xl shadow-md p-8">
    <p class="text-sm text-gray-400 mb-1">STEP 2 / 3</p>
    <h1 class="text-2xl font-bold text-gray-800 mb-2">入力内容の確認</h1>
    <p class="text-sm text-gray-500 mb-6">以下の内容で登録します。よろしければ「登録する」を押してください。</p>

    <!-- 確認内容の表示 -->
    <dl class="divide-y divide-gray-200 mb-8">
      <div class="py-3">
        <dt class="text-xs text-gray-400">お名前</dt>
        <dd class="text-gray-800 font-medium break-all"><?php echo $name; ?></dd>
      </div>
      <div class="py-3">
        <dt class="text-xs text-gray-400">メールアドレス</dt>
        <dd class="text-gray-800 font-medium break-all"><?php echo $email; ?></dd>
      </div>
      <div class="py-3">
        <dt class="text-xs text-gray-400">パスワード</dt>
        <!-- パスワードは文字数ぶんだけ「●」で伏せて表示する -->
        <dd class="text-gray-800 font-medium"><?php echo str_repeat('●', mb_strlen($password)); ?></dd>
      </div>
    </dl>

    <!-- ボタン2つ：戻る（index.php）／登録する（send_mail.php） -->
    <div class="flex gap-3">
      <!-- 「戻る」：入力画面へ。セッションは残したままにして入力値を復元できるようにする -->
      <a href="index.php"
         class="flex-1 text-center border border-gray-300 text-gray-700 font-bold py-3 rounded-lg hover:bg-gray-50 transition-colors">
        戻る
      </a>

      <!-- 「登録する」：送信処理 send_mail.php へ POST する -->
      <form action="send_mail.php" method="post" class="flex-1">
        <button type="submit"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors">
          登録する
        </button>
      </form>
    </div>
  </div>

</body>
</html>
