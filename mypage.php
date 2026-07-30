<?php
// ==========================================================
//  マイページ … ログイン必須
//  ・自分の情報（名前・メール）を表示
//  ・名前を変更するフォーム → mypage_update.php へ送信
//  ・自分がいいねした記事の一覧を表示（各記事へのリンクつき）
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

// --------------------------------------------------
//  自分がいいねした記事の一覧を取得する
//
//  いいねの記録（like_table）には記事番号しか入っていないので、
//  記事テーブル（articles）と結合してタイトルを取ってくる。
//
//  ここは INNER JOIN でよい。
//  「いいねした記事だけ」を出したいので、いいねの記録が無い記事は
//  そもそも結果に出てこなくて正しい（一覧側で LEFT OUTER JOIN を
//  使ったのは、いいね0件の記事も消さずに並べたかったから）。
// --------------------------------------------------
try {
    $sql = 'SELECT
              articles.id,
              articles.title,
              articles.intro,
              like_table.created_at AS liked_at
            FROM
              like_table
              INNER JOIN articles
                ON like_table.article_id = articles.id
            WHERE
              like_table.user_id = :user_id
            ORDER BY
              like_table.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $liked_articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $liked_articles = [];   // 取れなくてもマイページ自体は表示する
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

    <!-- ===== いいねした記事の一覧（クリックでその記事へ） ===== -->
    <section class="bg-white rounded-2xl shadow-sm p-6 sm:p-8 mt-6">
      <div class="flex items-baseline justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-800">いいねした記事</h2>
        <span class="text-sm text-gray-500"><?php echo count($liked_articles); ?>件</span>
      </div>

      <?php if (!$liked_articles): ?>
        <!-- 1件もないとき -->
        <p class="text-sm text-gray-500 leading-relaxed">
          まだいいねした記事はありません。<br>
          <a href="top.php" class="text-blue-600 hover:underline font-semibold">記事一覧</a> でハートを押すと、ここにたまっていきます。
        </p>

      <?php else: ?>
        <ul class="divide-y divide-gray-100">
          <?php foreach ($liked_articles as $liked): ?>
            <li class="py-3 first:pt-0 last:pb-0">
              <a href="article.php?id=<?php echo (int)$liked['id']; ?>"
                 class="text-blue-600 hover:underline font-semibold text-sm leading-snug">
                <?php echo htmlspecialchars($liked['title'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
              <p class="text-xs text-gray-400 mt-1">
                ♥ <?php echo date('Y年n月j日', strtotime($liked['liked_at'])); ?>
              </p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </main>

</body>
</html>
