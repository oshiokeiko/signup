<?php
// ==========================================================
//  記事詳細ページ（ペイウォール）… 誰でも入れる（追い返さない）
//  ・未ログイン → 冒頭(intro)だけ表示＋フェード＋「続きを読む」案内
//  ・ログイン済 → 続き(body)も全部表示
//  ※ 未ログインには続き本文のHTML自体を出力しない（ソースを見ても読めない）
// ==========================================================

session_start();
include(__DIR__ . '/includes/functions.php');   // is_login() を使う
include(__DIR__ . '/includes/like_button.php'); // like_button() を使う
require __DIR__ . '/includes/db_config.php';    // $pdo が使える

// ① URLから id を受け取り、整数チェック
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: top.php');
    exit;
}

// ② 記事1件＋いいね数を取得する（集計した表を結合して一度に取る）
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
        WHERE
          articles.id = :id';
try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit('記事の取得に失敗しました');
}

// 記事が無ければ TOP へ戻す
if (!$article) {
    header('Location: top.php');
    exit;
}

// ③ ログインしているか
$login = is_login();

// ④ 自分がこの記事をいいね済みか（ハートの色を塗り分けるため）
$liked = false;
if ($login) {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM like_table
                               WHERE user_id = :user_id AND article_id = :article_id');
        $stmt->bindValue(':user_id',    $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':article_id', $id,                  PDO::PARAM_INT);
        $stmt->execute();
        $liked = ((int)$stmt->fetchColumn() !== 0);
    } catch (PDOException $e) {
        $liked = false;   // 取れなくても記事表示は続ける
    }
}

// ⑤ 続き本文を段落に分ける（DBには段落を「空行」で区切って保存している）
$paragraphs = preg_split('/\R{2,}/u', trim($article['body']));
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
        <?php foreach ($paragraphs as $paragraph): ?>
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

      <div class="mt-8 pt-6 border-t border-gray-100 flex items-center justify-between">
        <a href="top.php" class="text-blue-600 hover:underline text-sm">← 一覧へ戻る</a>
        <!-- いいねボタン（押したらこの記事に戻ってくる） -->
        <?php echo like_button($id, $article['like_count'], $liked, 'article', $login); ?>
      </div>
    </article>
  </main>

</body>
</html>
