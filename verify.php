<?php
// メール内のURL（verify.php?token=xxxx）からトークンを受け取り、
// CSVと照合して status を 'active'（本登録済み）に更新する。
session_start();

// ① URLからトークンを受け取る。なければエラー。
$token = $_GET['token'] ?? '';
if ($token === '') {
    $errorMessage = 'トークンが指定されていません。';
}

$csvFile = __DIR__ . '/data/users.csv';

// CSVファイルが存在しない場合はエラー
if (!isset($errorMessage) && !file_exists($csvFile)) {
    $errorMessage = '登録データが見つかりません。';
}

// 照合結果のフラグ（一致したかどうか）
$matched = false;

if (!isset($errorMessage)) {
    // ② CSVを読み込み、トークンが一致する行の status を 'active' に書き換える

    // ファイルを読み書き両用（'r+'）で開く
    $fp = fopen($csvFile, 'r+');
    if ($fp === false) {
        $errorMessage = 'CSVファイルを開けませんでした。';
    } else {
        // 書き換え中に他の処理が割り込まないようロックをかける
        flock($fp, LOCK_EX);

        // CSVの全行をいったん配列に読み込む
        $rows = [];
        while (($data = fgetcsv($fp)) !== false) {
            $rows[] = $data;
        }

        // 各行をチェックして、トークン列（4列目＝添字3）が一致する行を探す
        foreach ($rows as $i => $row) {
            // ヘッダー行やカラム数が足りない行はスキップ
            if (!isset($row[3]) || $row[3] === 'token') {
                continue;
            }
            if (hash_equals($row[3], $token)) {
                // status列（5列目＝添字4）を 'active' に更新する
                $rows[$i][4] = 'active';
                $matched = true;
                break;
            }
        }

        if ($matched) {
            // 書き換えた内容でファイルを丸ごと上書きする
            // いったんファイルの中身を空にして先頭から書き直す
            rewind($fp);
            ftruncate($fp, 0);
            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }
        }

        // ロックを解除して閉じる
        flock($fp, LOCK_UN);
        fclose($fp);

        if (!$matched) {
            $errorMessage = 'トークンが一致しませんでした。URLが正しいか確認してください。';
        }
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
