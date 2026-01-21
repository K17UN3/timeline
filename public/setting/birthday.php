<?php
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();

// ログインチェック
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login2.php");
  return;
}

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// ログイン中ユーザ情報取得
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$select_sth->execute([
    ':id' => $_SESSION['login_user_id'],
]);
$user = $select_sth->fetch();

$error = '';

// POSTで送信された場合
if (isset($_POST['birthday'])) {
    $birthday = $_POST['birthday'];

    if (!empty($birthday)) {
        $update_sth = $dbh->prepare("UPDATE users SET birthday = :birthday WHERE id = :id");
        $update_sth->execute([
            ':birthday' => $birthday,
            ':id' => $user['id'],
        ]);

        // 更新完了後にリダイレクト
        header("HTTP/1.1 302 Found");
        header("Location: /setting/birthday.php");
        return;
    } else {
        $error = "生年月日を入力してください。";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>生年月日登録</title>
</head>
<body>

<a href="/setting/index.php">設定一覧に戻る</a>

  <h2>生年月日登録</h2>

  <?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST">
    <label for="birthday">生年月日:</label>
    <input type="date" id="birthday" name="birthday"
      value="<?= !empty($user['birthday']) ? htmlspecialchars($user['birthday']) : '' ?>">
    <br><br>
    <button type="submit">登録</button>
  </form>

</body>
</html>

