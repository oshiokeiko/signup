<?php
// ----------------------------------------------------------
//  会員情報の更新処理（今回は名前 name のみ更新する）
//  member_detail.php の更新フォームから POST される。
// ----------------------------------------------------------

// DBに接続する（db_config.php を読み込むと $pdo が使える）
require __DIR__ . '/includes/db_config.php';

// ① POSTの id を整数チェック。不正なら一覧へ戻す
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: list.php');
    exit;
}

// ② POSTの name を受け取り、前後の空白を除いて空でないかチェック
$name = trim($_POST['name'] ?? '');
if ($name === '') {
    // 名前が空なら更新せず、詳細画面に戻す
    header('Location: member_detail.php?id=' . $id);
    exit;
}

// ③ UPDATE文で名前を更新する（バインド変数を使用）
//    【重要】WHERE id を必ず指定する（忘れると全件更新される）
try {
    $sql = 'UPDATE users SET name = :name, updated_at = now() WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':id',   $id,   PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    echo json_encode(["db error" => "{$e->getMessage()}"]);
    exit();
}

// ④ 更新できたら一覧へ戻る
header('Location: list.php');
exit;
