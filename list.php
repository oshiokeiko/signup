<?php
// 管理・確認用のユーザー一覧画面。data/users.csv の中身を表として表示する。
// ※ 開発確認用のページなので、本番ではアクセス制限をかける想定。

$csvFile = __DIR__ . '/data/users.csv';

// CSVを読み込んで配列に入れる
$users = [];
if (file_exists($csvFile)) {
    $fp = fopen($csvFile, 'r');
    if ($fp !== false) {
        // 読み込み中に書き込みが起きないよう共有ロックをかける
        flock($fp, LOCK_SH);
        while (($data = fgetcsv($fp)) !== false) {
            $users[] = $data;
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

// 1行目（ヘッダー行）があれば取り出し、残りをデータ行とする
$header = !empty($users) ? array_shift($users) : [];
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
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr class="hover:bg-gray-50">
                <!-- CSVの値は htmlspecialchars でXSS対策をして表示する -->
                <td class="border-b px-3 py-2 break-all"><?php echo htmlspecialchars($user[0] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="border-b px-3 py-2 break-all"><?php echo htmlspecialchars($user[1] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="border-b px-3 py-2 break-all"><?php echo htmlspecialchars($user[2] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="border-b px-3 py-2">
                  <?php
                    // 状態（pending / active）を色付きバッジで表示する
                    $status = $user[4] ?? '';
                    if ($status === 'active') {
                        $badge = '<span class="inline-block bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs">本登録</span>';
                    } else {
                        $badge = '<span class="inline-block bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-xs">仮登録</span>';
                    }
                    echo $badge;
                  ?>
                </td>
                <td class="border-b px-3 py-2 whitespace-nowrap"><?php echo htmlspecialchars($user[5] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
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
