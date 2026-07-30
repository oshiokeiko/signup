<?php
// ==========================================================
//  TOPページ（記事一覧）… 誰でも見られる
//  ・記事は articles テーブルから取得する
//  ・いいね数は like_table を集計（GROUP BY）し、記事に結合（JOIN）して取得する
//  ・各記事の「続きを読む」で article.php?id=● へ移動
//  ・ヘッダーはログイン状態で出し分ける
// ==========================================================

session_start();                 // ログイン状態を確認するため
include(__DIR__ . '/includes/functions.php');         // is_login() を使う
include(__DIR__ . '/includes/like_button.php');       // like_button() を使う
require __DIR__ . '/includes/db_config.php';          // $pdo が使える

$login = is_login();

// --------------------------------------------------
//  ① 記事一覧＋いいね数をまとめて取得する
//     ・カッコの中（サブクエリ）が「記事ごとのいいね数を集計した表」
//     ・LEFT OUTER JOIN なので、いいねが0件の記事も一覧から消えない
//       （その場合 like_count は NULL になる）
// --------------------------------------------------
$sql = 'SELECT
          articles.*,
          result_table.like_count
        FROM
          articles
          LEFT OUTER JOIN (
            SELECT
              article_id,
              COUNT(id) AS like_count
            FROM
              like_table
            GROUP BY
              article_id
          ) AS result_table
          ON articles.id = result_table.article_id
        ORDER BY
          articles.id ASC';
try {
    $stmt = $pdo->query($sql);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit('記事の取得に失敗しました');
}

// --------------------------------------------------
//  ② ログイン中の会員が「すでにいいねした記事の番号」を集めておく
//     → ハートの色を塗り分けるために使う
// --------------------------------------------------
$liked_ids = [];
if ($login) {
    try {
        $stmt = $pdo->prepare('SELECT article_id FROM like_table WHERE user_id = :user_id');
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $liked_ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        $liked_ids = [];   // 取れなくても一覧表示は続ける
    }
}
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
        <?php if ($login): ?>
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
      <?php foreach ($articles as $article): ?>
        <?php
          $id    = (int)$article['id'];
          $liked = in_array($id, $liked_ids, true);   // 自分がいいね済みか
        ?>
        <article class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition-shadow">
          <h2 class="text-lg font-bold text-gray-800 mb-2">
            <?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>
          </h2>
          <p class="text-gray-600 text-sm leading-relaxed mb-4 line-clamp-2">
            <?php echo htmlspecialchars($article['intro'], ENT_QUOTES, 'UTF-8'); ?>
          </p>
          <div class="flex items-center justify-between">
            <a href="article.php?id=<?php echo $id; ?>"
               class="inline-block text-blue-600 hover:underline text-sm font-semibold">
              続きを読む →
            </a>
            <!-- いいねボタン（数は集計結果 like_count） -->
            <?php echo like_button($id, $article['like_count'], $liked, 'top', $login); ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </main>

</body>
</html>
