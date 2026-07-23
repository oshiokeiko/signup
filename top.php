<?php
// ==========================================================
//  TOPページ（記事一覧）… 誰でも見られる
//  ・記事のタイトルと冒頭を一覧表示する
//  ・各記事の「続きを読む」で article.php?id=● へ移動
//  ・ヘッダーはログイン状態で出し分ける
// ==========================================================

session_start();                 // ログイン状態を確認するため
include('functions.php');         // is_login() を使う
include('articles.php');          // $articles（記事データ）を読み込む
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>よみもの | TOP</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">

  <!-- ===== ヘッダー（ログイン状態で出し分け） ===== -->
  <header class="bg-white shadow-sm">
    <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
      <a href="top.php" class="text-xl font-bold text-gray-800">よみもの</a>
      <nav class="flex items-center gap-3 text-sm">
        <?php if (is_login()): ?>
          <span class="text-gray-600"><?php echo htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>さん</span>
          <a href="mypage.php" class="text-blue-600 hover:underline">マイページ</a>
          <a href="logout.php" class="text-gray-500 hover:underline">ログアウト</a>
        <?php else: ?>
          <a href="login.php" class="text-blue-600 hover:underline">ログイン</a>
          <a href="signup.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1.5 rounded-lg transition-colors">会員登録</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <!-- ===== 記事一覧 ===== -->
  <main class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">最新のよみもの</h1>

    <div class="space-y-4">
      <?php foreach ($articles as $id => $article): ?>
        <article class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition-shadow">
          <h2 class="text-lg font-bold text-gray-800 mb-2">
            <?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>
          </h2>
          <p class="text-gray-600 text-sm leading-relaxed mb-4 line-clamp-2">
            <?php echo htmlspecialchars($article['intro'], ENT_QUOTES, 'UTF-8'); ?>
          </p>
          <a href="article.php?id=<?php echo (int)$id; ?>"
             class="inline-block text-blue-600 hover:underline text-sm font-semibold">
            続きを読む →
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </main>

</body>
</html>
