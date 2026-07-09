<?php
// ----------------------------------------------------------
//  会員の削除処理（論理削除）
//  member_detail.php の削除フォームから POST される。
//  ※ 実データは消さず、deleted_at に日時を入れて「削除済み」扱いにする。
// ----------------------------------------------------------

// DBに接続する（db_config.php を読み込むと $pdo が使える）
require __DIR__ . '/db_config.php';

// ① POSTの id を整数チェック。不正なら一覧へ戻す
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: list.php');
    exit;
}

// ② 論理削除：物理DELETEはせず、deleted_at に現在日時をセットする
//    【重要】WHERE id を必ず指定する（忘れると全件が削除扱いになる）
try {
    $sql = 'UPDATE users SET deleted_at = now() WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    echo json_encode(["db error" => "{$e->getMessage()}"]);
    exit();
}

// ③ 削除できたら一覧へ戻る
header('Location: list.php');
exit;
