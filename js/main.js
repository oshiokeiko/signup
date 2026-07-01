// 入力フォーム（index.php）の送信前バリデーション（jQuery）
// 対象：名前（必須）／メール（必須・形式）／パスワード（必須・半角英数8文字以上）

$(function () {
  //----------------------------------
  //  フォームに何も入力がない場合は何もしない
  //----------------------------------
  const $form = $('#signupForm');
  if ($form.length === 0) {
    return;
  }

  // 指定した入力欄にエラーを表示する関数
  function showError($input, message) {
    // 入力欄を赤く強調する（css/style.css の .input-error）
    $input.addClass('input-error');
    // 入力欄の直下にあるエラーメッセージ欄に文言を入れて表示する
    const $msg = $form.find('.error-message[data-for="' + $input.attr('name') + '"]');
    $msg.text(message).removeClass('hidden');
  }

  // 指定した入力欄のエラー表示を消す関数
  function clearError($input) {
    $input.removeClass('input-error');
    const $msg = $form.find('.error-message[data-for="' + $input.attr('name') + '"]');
    $msg.text('').addClass('hidden');
  }

  // 1項目ぶんのチェックを行う関数。問題なければ true を返す。
  function validateField($input) {
    const name  = $input.attr('name');
    const value = $input.val().trim();

    //----------------------------------
    // name未入力
    // 「お名前を入力してください」と返す
    //----------------------------------
    if (name === 'name') {
      if (value === '') {
        showError($input, 'お名前を入力してください。');
        return false;
      }
    }

    //----------------------------------
    // email未入力
    // 「メールアドレスを入力してください。」と返す
    //----------------------------------
    if (name === 'email') {
      if (value === '') {
        showError($input, 'メールアドレスを入力してください。');
        return false;
      }

      //----------------------------------
      // 簡易的なメール形式チェック（@とドメインがあるか）
      //----------------------------------
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(value)) {
        showError($input, 'メールアドレスの形式が正しくありません。');
        return false;
      }
    }

    //----------------------------------
    // password未入力
    // 「パスワードを入力してください。」と返す
    //----------------------------------
    // パスワード：必須 ＋ 半角英数8文字以上
    if (name === 'password') {
      if (value === '') {
        showError($input, 'パスワードを入力してください。');
        return false;
      }

      //----------------------------------
      // 半角英数字8文字以上ではない場合
      // 「パスワードは半角英数字8文字以上で入力してください。」と返す
      //----------------------------------
      const passwordPattern = /^[A-Za-z0-9]{8,}$/;
      if (!passwordPattern.test(value)) {
        showError($input, 'パスワードは半角英数字8文字以上で入力してください。');
        return false;
      }
    }

    //----------------------------------
    // 問題なければエラーを消して true
    //----------------------------------
    clearError($input);
    return true;
  }

  //----------------------------------
  //  入力欄からフォーカスが外れたタイミングで、その項目だけチェックする
  //----------------------------------
  $form.find('input[name="name"], input[name="email"], input[name="password"]').on('blur', function () {
    validateField($(this));
  });

  //----------------------------------
  //  フォーム送信時に全項目をチェックする
  //----------------------------------
  $form.on('submit', function (e) {
    let isValid = true;

    //----------------------------------
    //  各項目をチェック（&& にしないことで全項目のエラーをまとめて表示する）
     //----------------------------------
    $form.find('input[name="name"], input[name="email"], input[name="password"]').each(function () {
      if (!validateField($(this))) {
        isValid = false;
      }
    });

    //----------------------------------
    //  1つでもエラーがあれば送信を中止する
    //----------------------------------
    if (!isValid) {
      e.preventDefault();
    }
  });
});
