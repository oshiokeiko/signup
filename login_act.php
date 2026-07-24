<?php
// ==========================================================
//  ログイン処理（画面なし）
//  ・login.php から送られたメール・パスワードをDBと照合する
//  ・本登録済み(active)かつ退会していない(deleted_at IS NULL)会員だけ許可
//  ・成功 → セッションにログイン情報を保存し、記事 or TOP へ
//  ・失敗 → login.php へ戻す（?error=1）
// ==========================================================

session_start();
require __DIR__ . '/includes/db_config.php';   // $pdo が使える

// ① 入力を受け取る
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
// 戻り先の記事id（任意）
$back = filter_input(INPUT_POST, 'back', FILTER_VALIDATE_INT);

// ② DBに該当会員がいるか照合する（バインド変数でSQLインジェクション対策）
//    ※ パスワードは今回そのまま（平文）で照合する
try {
    $sql = 'SELECT * FROM users
            WHERE email = :email
              AND password = :password
              AND status = \'active\'
              AND deleted_at IS NULL';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email',    $email,    PDO::PARAM_STR);
    $stmt->bindValue(':password', $password, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(["db error" => "{$e->getMessage()}"]);
    exit();
}

// ③ 見つからなければログイン失敗 → login.php へ戻す
if (!$user) {
    $redirect = 'login.php?error=1';
    if ($back) {
        $redirect .= '&back=' . (int)$back;   // 戻り先を保ったままやり直せるように
    }
    header('Location: ' . $redirect);
    exit;
}

// ④ 成功：セッションを作り直してログイン情報を保存する
$_SESSION = array();                       // 念のため中身を初期化
session_regenerate_id(true);               // id を作り直す（乗っ取り対策）
$_SESSION['session_id'] = session_id();    // ログイン判定に使う最新id
$_SESSION['user_id']    = $user['id'];      // マイページの更新に使う
$_SESSION['name']       = $user['name'];    // 画面に「◯◯さん」と出す用

// ⑤ 読もうとしていた記事があればそこへ、なければ TOP へ
if ($back && $back > 0) {
    header('Location: article.php?id=' . (int)$back);
} else {
    header('Location: top.php');
}
exit;
