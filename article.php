<?php
// ==========================================================
//  記事詳細ページ（ペイウォール）… 誰でも入れる（追い返さない）
//  ・未ログイン → 冒頭(intro)だけ表示＋フェード＋「続きを読む」案内
//  ・ログイン済 → 続き(body)も全部表示
//  ※ 未ログインには続き本文のHTML自体を出力しない（ソースを見ても読めない）
// ==========================================================

session_start();
include(__DIR__ . '/includes/functions.php');   // is_login() を使う
include(__DIR__ . '/includes/articles.php');    // $articles を読み込む

// ① URLから id を受け取り、整数チェック。記事が無ければ TOP へ戻す
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || !isset($articles[$id])) {
    header('Location: top.php');
    exit;
}
$article = $articles[$id];

// ② ログインしているか
$login = is_login();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?> | よみもの</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">

  <!-- ===== ヘッダー ===== -->
  <header class="bg-white shadow-sm">
    <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
      <a href="top.php" class="text-xl font-bold text-gray-800">よみもの</a>
      <nav class="flex items-center gap-3 text-sm">
        <?php if ($login): ?>
          <span class="text-gray-600"><?php echo htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>さん</span>
          <a href="mypage.php" class="text-blue-600 hover:underline">マイページ</a>
          <a href="logout.php" class="text-gray-500 hover:underline">ログアウト</a>
        <?php else: ?>
          <a href="login.php?back=<?php echo (int)$id; ?>" class="text-blue-600 hover:underline">ログイン</a>
          <a href="signup.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1.5 rounded-lg transition-colors">会員登録</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <!-- ===== 記事本文 ===== -->
  <main class="max-w-3xl mx-auto px-4 py-8">
    <article class="bg-white rounded-2xl shadow-sm p-6 sm:p-10">
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 leading-snug">
        <?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>
      </h1>

      <!-- 冒頭（誰でも読める） -->
      <p class="text-gray-700 leading-loose mb-6">
        <?php echo htmlspecialchars($article['intro'], ENT_QUOTES, 'UTF-8'); ?>
      </p>

      <?php if ($login): ?>
        <!-- ===== ログイン済み：続きを全部表示 ===== -->
        <?php foreach ($article['body'] as $paragraph): ?>
          <p class="text-gray-700 leading-loose mb-6">
            <?php echo htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8'); ?>
          </p>
        <?php endforeach; ?>

      <?php else: ?>
        <!-- ===== 未ログイン：続きは出さず、フェード＋案内を表示 ===== -->
        <!-- 続き本文はここには一切出力しない（ソースを見ても読めない） -->

        <!-- フェード演出：本文の“続きがある感”を出すためのダミー行＋白グラデ -->
        <div class="relative">
          <p class="text-gray-700 leading-loose select-none" aria-hidden="true">
            <?php echo str_repeat('　　　　　　　　　　', 6); ?>
          </p>
          <!-- 下に向かって白くフェードさせるオーバーレイ -->
          <div class="absolute inset-x-0 bottom-0 h-full bg-gradient-to-t from-white via-white/90 to-transparent"></div>
        </div>

        <!-- 続きを読む案内（会員登録=primary / ログイン=secondary） -->
        <div class="mt-2 border border-gray-200 rounded-2xl p-6 text-center bg-gray-50">
          <p class="text-gray-800 font-bold mb-1">続きを読むには会員登録が必要です</p>
          <p class="text-gray-500 text-sm mb-5">会員登録（無料）で、この記事の続きが読めます。</p>
          <a href="signup.php"
             class="block sm:inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition-colors">
            会員登録して続きを読む
          </a>
          <p class="text-sm text-gray-500 mt-4">
            すでに会員の方は
            <a href="login.php?back=<?php echo (int)$id; ?>" class="text-blue-600 hover:underline font-semibold">ログイン</a>
          </p>
        </div>
      <?php endif; ?>

      <div class="mt-8 pt-6 border-t border-gray-100">
        <a href="top.php" class="text-blue-600 hover:underline text-sm">← 一覧へ戻る</a>
      </div>
    </article>
  </main>

</body>
</html>
