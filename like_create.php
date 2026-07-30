<?php
// ==========================================================
//  いいね処理（画面なし）
//  ・ログイン必須。押した会員は $_SESSION['user_id'] から判定する
//  ・すでにいいね済み → 取り消す（DELETE）
//  ・まだいいねしていない → 登録する（INSERT）
//  ・処理後は元の画面に戻す
//
//  【講義との違い】
//  講義では user_id も URL で送っていた（?user_id=1&todo_id=3）。
//  しかしそれだと URL を書き換えて「他人としていいね」ができてしまう。
//  このサイトはログイン機能があるので、誰が押したかはセッションから取り、
//  URL で受け取るのは「どの記事か（article_id）」だけにしている。
// ==========================================================

session_start();
include(__DIR__ . '/includes/functions.php');   // check_login() を使う
require __DIR__ . '/includes/db_config.php';    // $pdo が使える

// ① ログイン必須（未ログインは login.php へ追い返す）
check_login();

// ② 誰が・どの記事に、を用意する
$user_id    = $_SESSION['user_id'];                                  // 押した会員（セッションから）
$article_id = filter_input(INPUT_GET, 'article_id', FILTER_VALIDATE_INT);
$back       = $_GET['back'] ?? 'top';                                // 戻り先（top / article）

// 記事番号が不正ならTOPへ
if (!$article_id) {
    header('Location: top.php');
    exit;
}

// 戻り先のURLを先に決めておく（このあと何度も使う）
$redirect = ($back === 'article') ? 'article.php?id=' . $article_id : 'top.php';

// ------------------------------------------------------
//  ③ いま「いいね済みか」を調べる
//     COUNT(*) で該当データの件数を取得する
// ------------------------------------------------------
try {
    $sql  = 'SELECT COUNT(*) FROM like_table
             WHERE user_id = :user_id AND article_id = :article_id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id',    $user_id,    PDO::PARAM_INT);
    $stmt->bindValue(':article_id', $article_id, PDO::PARAM_INT);
    $stmt->execute();
    $like_count = (int)$stmt->fetchColumn();   // 0 か 1 が返る
} catch (PDOException $e) {
    // 練習で原因を見たいときは下の行のコメントを外す
    // echo json_encode(["sql error" => "{$e->getMessage()}"]); exit();
    header('Location: ' . $redirect);
    exit;
}

// ------------------------------------------------------
//  ④ 件数で処理を分ける（0件なら登録、それ以外なら取り消し）
// ------------------------------------------------------
if ($like_count !== 0) {
    // すでにいいねしている → 取り消す
    $sql = 'DELETE FROM like_table
            WHERE user_id = :user_id AND article_id = :article_id';
} else {
    // まだいいねしていない → 登録する
    $sql = 'INSERT INTO like_table (id, user_id, article_id, created_at)
            VALUES (NULL, :user_id, :article_id, now())';
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id',    $user_id,    PDO::PARAM_INT);
    $stmt->bindValue(':article_id', $article_id, PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    // 練習で原因を見たいときは下の行のコメントを外す
    // echo json_encode(["sql error" => "{$e->getMessage()}"]); exit();
    header('Location: ' . $redirect);
    exit;
}

// ⑤ 元の画面へ戻る
header('Location: ' . $redirect);
exit;
