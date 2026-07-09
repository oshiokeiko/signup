<?php
// 管理・確認用のユーザー一覧画面。DB（users テーブル）の中身を表として表示する。
// ※ 開発確認用のページなので、本番ではアクセス制限をかける想定。

// DBに接続する。db_config.php（Git管理外）を読み込むと $pdo が使える
require __DIR__ . '/db_config.php';

// SELECT文で登録済みユーザーを新しい順に取得する
// ※ deleted_at が NULL のもの（＝削除されていないもの）だけを表示する
$users = [];
try {
    $sql = 'SELECT * FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    // 結果は連想配列（カラム名で取り出せる形）でまとめて受け取る
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(["db error" => "{$e->getMessage()}"]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ユーザー一覧（管理用）</title>
  <!-- Tailwind CSS（CDN版） -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 min-h-screen p-4">

  <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-md p-6 sm:p-8">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">ユーザー一覧</h1>
      <span class="text-sm text-gray-400">登録件数：<?php echo count($users); ?> 件</span>
    </div>

    <?php if (empty($users)): ?>
      <!-- データが1件もない場合 -->
      <p class="text-gray-500 text-center py-12">まだ登録されたユーザーはいません。</p>
    <?php else: ?>
      <!-- 横スクロール対応のためのラッパー -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="bg-gray-50 text-left text-gray-600">
              <th class="border-b px-3 py-2">名前</th>
              <th class="border-b px-3 py-2">メールアドレス</th>
              <th class="border-b px-3 py-2">パスワード</th>
              <th class="border-b px-3 py-2">状態</th>
              <th class="border-b px-3 py-2">登録日時</th>
              <th class="border-b px-3 py-2">操作</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr class="hover:bg-gray-50">
                <!-- DBの値は列名で取り出し、htmlspecialchars でXSS対策をして表示する -->
                <td class="border-b px-3 py-2 break-all"><?php echo htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="border-b px-3 py-2 break-all"><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="border-b px-3 py-2 break-all"><?php echo htmlspecialchars($user['password'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="border-b px-3 py-2">
                  <?php
                    // 状態（pending / active）を色付きバッジで表示する
                    $status = $user['status'] ?? '';
                    if ($status === 'active') {
                        $badge = '<span class="inline-block bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs">本登録</span>';
                    } else {
                        $badge = '<span class="inline-block bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-xs">仮登録</span>';
                    }
                    echo $badge;
                  ?>
                </td>
                <td class="border-b px-3 py-2 whitespace-nowrap"><?php echo htmlspecialchars($user['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="border-b px-3 py-2 whitespace-nowrap">
                  <!-- id は数値なので (int) で整数に変換してからURLに埋め込む -->
                  <a href="member_detail.php?id=<?php echo (int)($user['id'] ?? 0); ?>"
                     class="text-blue-600 hover:underline">詳細</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <a href="index.php" class="inline-block mt-6 text-blue-600 hover:underline text-sm">入力画面へ</a>
  </div>

</body>
</html>
