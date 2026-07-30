<?php
// ==========================================================
//  いいねボタン（共通部品）
//  一覧（top.php）と記事詳細（article.php）の両方で使う。
//
//  使い方：
//    include(__DIR__ . '/includes/like_button.php');
//    echo like_button($id, $like_count, $liked, 'top', $login);
//
//  引数：
//    $article_id … どの記事のボタンか
//    $like_count … いいね数（DBから来た NULL でもそのまま渡してOK）
//    $liked      … 自分がすでにいいね済みか true/false
//    $back       … 押したあとの戻り先 'top' または 'article'
//    $login      … ログインしているか true/false
// ==========================================================

function like_button($article_id, $like_count, $liked, $back, $login)
{
    $article_id = (int)$article_id;
    $count      = (int)$like_count;                              // NULL は 0 になる
    $back       = ($back === 'article') ? 'article' : 'top';      // 想定外の値は 'top' に寄せる

    // ---- 未ログイン：数だけ見せて、押したらログイン案内へ ----
    if (!$login) {
        return '<a href="login.php?back=' . $article_id . '"'
             . ' title="ログインするといいねできます"'
             . ' class="inline-flex items-center gap-1 text-sm font-semibold text-gray-400 hover:text-pink-600 transition-colors">'
             . '<span>♡</span><span>' . $count . '</span></a>';
    }

    // ---- ログイン済み：いいね済みならピンク、まだならグレー ----
    $mark  = $liked ? '♥' : '♡';
    $color = $liked
        ? 'text-pink-600 hover:text-pink-700'
        : 'text-gray-400 hover:text-pink-600';
    $label = $liked ? 'いいねを取り消す' : 'いいねする';

    return '<a href="like_create.php?article_id=' . $article_id . '&amp;back=' . $back . '"'
         . ' title="' . $label . '"'
         . ' class="inline-flex items-center gap-1 text-sm font-semibold ' . $color . ' transition-colors">'
         . '<span>' . $mark . '</span><span>' . $count . '</span></a>';
}
