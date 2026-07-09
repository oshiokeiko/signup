<?php
// メール内のURL（verify.php?token=xxxx）からトークンを受け取り、
// DBと照合して status を 'active'（本登録済み）に更新する。
session_start();

// ① URLからトークンを受け取る。なければエラー。
$token = $_GET['token'] ?? '';
if ($token === '') {
    $errorMessage = 'トークンが指定されていません。';
}

// 照合結果のフラグ（一致したかどうか）
$matched = false;

if (!isset($errorMessage)) {
    // ② DBに接続する。db_config.php（Git管理外）を読み込むと $pdo が使える
    require __DIR__ . '/db_config.php';

    try {
        // ③ SELECT文でトークンが一致するレコードを探す（バインド変数で安全に照合）
        $selectSql = 'SELECT * FROM users WHERE token = :token';
        $stmt = $pdo->prepare($selectSql);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // ④ 一致したら UPDATE文で status を 'active' に更新する
            // 【重要】WHERE を必ず指定する（忘れると全レコードが更新される）
            $updateSql = 'UPDATE users SET status = \'active\', updated_at = now() WHERE token = :token';
            $stmt = $pdo->prepare($updateSql);
            $stmt->bindValue(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            $matched = true;
        } else {
            $errorMessage = 'トークンが一致しませんでした。URLが正しいか確認してください。';
        }
    } catch (PDOException $e) {
        // 照合・更新に失敗したらエラー内容を表示する
        echo json_encode(["db error" => "{$e->getMessage()}"]);
        exit();
    }
}

// ③ 一致して本登録できた場合は完了画面へリダイレクトする
if ($matched) {
    header('Location: complete.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会員登録 | 認証エラー</title>
  <!-- Tailwind CSS（CDN版） -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md bg-white rounded-2xl shadow-md p-8 text-center">
    <div class="text-5xl mb-4">⚠️</div>
    <h1 class="text-2xl font-bold text-gray-800 mb-3">認証できませんでした</h1>
    <p class="text-red-500 leading-relaxed">
      <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <a href="index.php" class="inline-block mt-6 text-blue-600 hover:underline text-sm">
      入力画面に戻る
    </a>
  </div>

</body>
</html>
