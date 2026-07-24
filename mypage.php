<?php
// ==========================================================
//  マイページ … ログイン必須
//  ・自分の情報（名前・メール）を表示
//  ・名前を変更するフォーム → mypage_update.php へ送信
//  ・未ログインでURLを直打ちしても check_login() で追い返す
// ==========================================================

session_start();
include(__DIR__ . '/includes/functions.php');
require __DIR__ . '/includes/db_config.php';   // $pdo が使える

// 門番：未ログインなら login.php へ追い返す（＋id最新化）
check_login();

// 自分のデータを取得する（セッションの user_id を使う）
try {
    $sql = 'SELECT * FROM users WHERE id = :id AND deleted_at IS NULL';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(["db error" => "{$e->getMessage()}"]);
    exit();
}

// 万が一データが無ければ（退会済みなど）ログアウト扱いでTOPへ
if (!$user) {
    header('Location: logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>マイページ | よみもの</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">

  <?php if (($_GET['updated'] ?? '') === '1'): ?>
    <!-- ===== 更新完了トースト（数秒で自動的に消える） ===== -->
    <div id="toast"
         class="fixed top-6 left-1/2 -translate-x-1/2 z-50 bg-gray-800 text-white text-sm font-semibold px-5 py-3 rounded-lg shadow-lg transition-opacity duration-500">
      ✅ お名前を更新しました。
    </div>
    <script>
      // 2.5秒後にふわっと消す
      setTimeout(function () {
        var t = document.getElementById('toast');
        if (t) { t.style.opacity = '0'; }
        setTimeout(function () { if (t) { t.remove(); } }, 500);
      }, 2500);
    </script>
  <?php endif; ?>

  <!-- ===== ヘッダー（ログイン済み） ===== -->
  <header class="bg-white shadow-sm">
    <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
      <a href="top.php" class="text-xl font-bold text-gray-800">よみもの</a>
      <nav class="flex items-center gap-3 text-sm">
        <span class="text-gray-600"><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>さん</span>
        <a href="logout.php" class="text-gray-500 hover:underline">ログアウト</a>
      </nav>
    </div>
  </header>

  <main class="max-w-md mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8">
      <h1 class="text-2xl font-bold text-gray-800 mb-6">マイページ</h1>

      <!-- 名前の変更フォーム（idはセッションのuser_idを使うので送らない） -->
      <form action="mypage_update.php" method="post" class="space-y-4">
        <div>
          <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">お名前</label>
          <input type="text" id="name" name="name" required
                 value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>

        <!-- メールは表示のみ（今回は変更しない） -->
        <div>
          <span class="block text-sm font-semibold text-gray-700 mb-1">メールアドレス</span>
          <p class="text-gray-800 break-all"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors">
          名前を変更する
        </button>
      </form>

      <a href="top.php" class="inline-block mt-6 text-blue-600 hover:underline text-sm">← 記事一覧へ</a>
    </div>
  </main>

</body>
</html>
