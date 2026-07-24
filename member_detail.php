<?php
// ----------------------------------------------------------
//  会員の詳細・編集画面
//  一覧(list.php)の「詳細」リンクから id を受け取り、その1件を表示する。
//  ・名前(name)の更新フォーム   → member_update.php へPOST
//  ・削除フォーム（論理削除）    → member_delete.php へPOST（モーダルで確認）
// ----------------------------------------------------------

// DBに接続する（db_config.php を読み込むと $pdo が使える）
require __DIR__ . '/includes/db_config.php';

// ① URLから id を受け取り、整数かどうかチェックする
//    不正な値・未指定なら一覧へ戻す
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: list.php');
    exit;
}

// ② id が一致し、まだ削除されていない(deleted_at IS NULL)1件をSELECTで取得する
try {
    $sql = 'SELECT * FROM users WHERE id = :id AND deleted_at IS NULL';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(["db error" => "{$e->getMessage()}"]);
    exit();
}

// 該当データが無ければ（削除済み・存在しない id など）一覧へ戻す
if (!$user) {
    header('Location: list.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会員詳細・編集（管理用）</title>
  <!-- Tailwind CSS（CDN版） -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-gray-100 min-h-screen p-4">

  <div class="max-w-md mx-auto bg-white rounded-2xl shadow-md p-6 sm:p-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">会員詳細・編集</h1>

    <!-- ===== 更新フォーム（編集できるのは名前だけ） ===== -->
    <form action="member_update.php" method="post" class="space-y-4">
      <!-- どのレコードを更新するかを hidden で渡す -->
      <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">

      <div>
        <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">名前</label>
        <input type="text" id="name" name="name"
               value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
               required>
      </div>

      <!-- 以下は表示のみ（今回は編集しない） -->
      <div>
        <span class="block text-sm font-semibold text-gray-700 mb-1">メールアドレス</span>
        <p class="text-gray-800 break-all"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <div>
        <span class="block text-sm font-semibold text-gray-700 mb-1">状態</span>
        <p class="text-gray-800"><?php echo htmlspecialchars($user['status'], ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <div>
        <span class="block text-sm font-semibold text-gray-700 mb-1">登録日時</span>
        <p class="text-gray-800"><?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?></p>
      </div>

      <button type="submit"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors">
        保存する
      </button>
    </form>

    <!-- ===== 削除フォーム（論理削除） ===== -->
    <!-- 「削除」ボタンでは送信せず、まずモーダルで確認する（type="button"） -->
    <form id="deleteForm" action="member_delete.php" method="post" class="mt-4">
      <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
      <button type="button" id="openDeleteModal"
              class="w-full bg-red-50 hover:bg-red-100 text-red-600 font-bold py-3 rounded-lg transition-colors">
        削除する
      </button>
    </form>

    <a href="list.php" class="inline-block mt-6 text-blue-600 hover:underline text-sm">← 一覧へ戻る</a>
  </div>

  <!-- ===== 削除確認モーダル（自作／初期は非表示） ===== -->
  <div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm text-center">
      <div class="text-4xl mb-3">🗑️</div>
      <h2 class="text-lg font-bold text-gray-800 mb-2">本当に削除しますか？</h2>
      <p class="text-gray-600 text-sm mb-6">
        「<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>」さんを削除します。<br>この操作は元に戻せません。
      </p>
      <div class="flex gap-3">
        <button type="button" id="cancelDelete"
                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-lg transition-colors">
          キャンセル
        </button>
        <button type="button" id="confirmDelete"
                class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-lg transition-colors">
          削除する
        </button>
      </div>
    </div>
  </div>

  <!-- jQuery（既存ページと同じCDN方針） -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script>
    // 「削除する」ボタン → モーダルを表示（flex にして中央表示）
    $('#openDeleteModal').on('click', function () {
      $('#deleteModal').removeClass('hidden').addClass('flex');
    });
    // 「キャンセル」 → モーダルを閉じるだけ（送信しない）
    $('#cancelDelete').on('click', function () {
      $('#deleteModal').addClass('hidden').removeClass('flex');
    });
    // モーダル内の「削除する」 → 削除フォームを送信（member_delete.php へPOST）
    $('#confirmDelete').on('click', function () {
      $('#deleteForm').submit();
    });
  </script>

</body>
</html>
