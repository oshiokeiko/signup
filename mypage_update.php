<?php
// ==========================================================
//  名前の更新処理（画面なし）… ログイン必須
//  ・mypage.php のフォームから送られた名前でDBを更新する
//  ・更新するのは「自分」だけ（WHERE id には $_SESSION['user_id'] を使う）
// ==========================================================

session_start();
include(__DIR__ . '/includes/functions.php');
require __DIR__ . '/includes/db_config.php';   // $pdo が使える

// 門番：未ログインなら追い返す
check_login();

// 新しい名前を受け取り、前後の空白を除いて空チェック
$name = trim($_POST['name'] ?? '');
if ($name === '') {
    // 空なら更新せずマイページへ戻す
    header('Location: mypage.php');
    exit;
}

// UPDATE文で自分の名前だけ更新する
//   【重要】WHERE id を必ず指定（idはセッションのuser_id＝他人は変更できない）
try {
    $sql = 'UPDATE users SET name = :name, updated_at = now() WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':id',   $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    echo json_encode(["db error" => "{$e->getMessage()}"]);
    exit();
}

// ヘッダー表示用のセッションの名前も更新しておく
$_SESSION['name'] = $name;

// マイページへ戻る（updated=1 を付けて「更新できた」トーストを出す）
header('Location: mypage.php?updated=1');
exit;
