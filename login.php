<?php
// ==========================================================
//  ログイン画面
//  ・メールアドレスとパスワードを入力して login_act.php へ送信
//  ・「back」（読もうとしていた記事id）があれば hidden で引き継ぐ
//  ・すでにログイン済みなら TOP へ飛ばす（ログイン画面は見せない）
// ==========================================================

session_start();
include(__DIR__ . '/includes/functions.php');

// すでにログイン済みなら TOP へ
if (is_login()) {
    header('Location: top.php');
    exit;
}

// 戻り先の記事id（任意）。整数以外は無視する。
$back = filter_input(INPUT_GET, 'back', FILTER_VALIDATE_INT);

// ログイン失敗で戻ってきた場合の判定（login_act.php から ?error=1 が付く）
$hasError = (($_GET['error'] ?? '') === '1');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ログイン | よみもの</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md bg-white rounded-2xl shadow-md p-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">ログイン</h1>

    <?php if ($hasError): ?>
      <!-- ログイン失敗メッセージ -->
      <div class="mb-5 bg-red-50 text-red-600 text-sm rounded-lg px-4 py-3">
        メールアドレスまたはパスワードが正しくありません。
      </div>
    <?php endif; ?>

    <form action="login_act.php" method="post" class="space-y-5">
      <!-- 読もうとしていた記事id を引き継ぐ（あれば） -->
      <?php if ($back): ?>
        <input type="hidden" name="back" value="<?php echo (int)$back; ?>">
      <?php endif; ?>

      <div>
        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">メールアドレス</label>
        <input type="email" id="email" name="email" required
               placeholder="taro@example.com"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <div>
        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">パスワード</label>
        <input type="password" id="password" name="password" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>

      <button type="submit"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors">
        ログイン
      </button>
    </form>

    <p class="text-sm text-gray-500 mt-6 text-center">
      まだ会員でない方は
      <a href="signup.php" class="text-blue-600 hover:underline font-semibold">会員登録</a>
    </p>
    <a href="top.php" class="block text-center text-sm text-gray-400 hover:underline mt-3">← TOPへ戻る</a>
  </div>

</body>
</html>
