<?php
// --------------------------------------------------
//
// 会員登録画面
// 
// --------------------------------------------------

// セッションを開始する（「戻る」で戻ってきたときに入力値を復元するために使用）
session_start();

// セッションに前回の入力値があれば取り出す。なければ空文字にする。
// ※ この画面(signup.php)で表示する値なので、念のため htmlspecialchars でXSS対策をする
$name  = isset($_SESSION['name'])  ? htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8')  : '';
$email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8') : '';
// パスワードはセキュリティ上、画面に復元表示しない
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会員登録 | 入力</title>
  <!-- Tailwind CSS（CDN版・ビルド不要） -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- 共通の補足スタイル -->
  <link rel="stylesheet" href="css/style.css">
</head>


<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-md bg-white rounded-2xl shadow-md p-8">
    <!-- ステップ表示 -->
    <p class="text-sm text-gray-400 mb-1">STEP 1 / 3</p>
    <h1 class="text-2xl font-bold text-gray-800 mb-6">会員登録</h1>

    <!------------------------------------------
    【ここで確認画面に情報を渡す指示】
    入力フォーム：確認画面 confirm.php へ POST 送信する
    ------------------------------------------->
    <form id="signupForm" action="confirm.php" method="post" novalidate>

      <!---------------------------------
      名前 
      ---------------------------------->
      <div class="mb-5">
        <!--フォームタイトル（お名前）-->
        <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
          お名前 <span class="text-red-500">*</span>
        </label>

        <!--フォーム--->
        <input
          type="text"
          id="name"
          name="name"
          value="<?php echo $name; ?>"
          placeholder="山田太郎"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
        
        <!-- エラーだった場合、hiddenを外しエラーメッセージ表示 -->
        <p class="error-message text-red-500 text-sm mt-1 hidden" data-for="name"></p>
      </div>

      <!---------------------------------
      メールアドレス
      ---------------------------------->
      <div class="mb-5">
        <!--フォームタイトル（メール）-->
        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">
          メールアドレス <span class="text-red-500">*</span>
        </label>

        <!--フォーム--->
        <input
          type="email"
          id="email"
          name="email"
          value="<?php echo $email; ?>"
          placeholder="taro@example.com"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
          <!--注釈--->
        <p class="text-xs text-gray-400 mt-1">本当のメールアドレスは入れずにダミーにしてね！</p>

          <!-- エラーだった場合、hiddenクラスを外しエラーメッセージ表示 -->
        <p class="error-message text-red-500 text-sm mt-1 hidden" data-for="email"></p>
      </div>

      <!---------------------------------
      パスワード
      ---------------------------------->
      <div class="mb-6">
        <!--フォームタイトル（パスワード）-->
        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">
          パスワード <span class="text-red-500">*</span>
        </label>

        <!--フォーム--->
        <input
          type="password"
          id="password"
          name="password"
          placeholder="半角英数8文字以上"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
        <p class="error-message text-red-500 text-sm mt-1 hidden" data-for="password"></p>
        <!--注釈--->
        <p class="text-xs text-gray-400 mt-1">半角英数字、8文字以上で入力してください</p>
      </div>

      <!---------------------------------
      送信ボタン
      ---------------------------------->
      <button
        type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition-colors">
        確認画面へ進む
      </button>
    </form>
  </div>

  <!---------------------------------
  jquery読み込み
  ---------------------------------->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- フォームのバリデーション処理 -->
  <script src="js/main.js"></script>
</body>
</html>
