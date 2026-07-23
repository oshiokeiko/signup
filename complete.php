<?php
// --------------------------------------------------
//
// 登録完了画面
// verify.php での本登録が成功するとここにリダイレクトされる。
// 
// --------------------------------------------------

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会員登録 | 完了</title>
  <!-- Tailwind CSS（CDN版） -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md bg-white rounded-2xl shadow-md p-8 text-center">
    <!-- 完了アイコン -->
    <div class="text-6xl mb-4">🎉</div>
    <p class="text-sm text-gray-400 mb-1">STEP 3 / 3</p>
    <h1 class="text-2xl font-bold text-gray-800 mb-3">会員登録が完了しました！</h1>
    <p class="text-gray-600 leading-relaxed">
      メールアドレスの認証が完了し、<br>会員登録が正常に完了しました。
    </p>

    <a href="login.php"
       class="inline-block mt-8 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition-colors">
      ログインして記事を読む
    </a>
    <a href="top.php" class="block mt-4 text-sm text-gray-400 hover:underline">TOPへ戻る</a>
  </div>

</body>
</html>
