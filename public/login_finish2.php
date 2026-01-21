<?php
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();

if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: ./login2.php");
  exit;
}

$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$insert_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$insert_sth->execute([
    ':id' => $_SESSION['login_user_id'],
]);
$user = $insert_sth->fetch(PDO::FETCH_ASSOC);
?>

<h1>ログイン完了</h1>

<p>
  ログイン完了しました!
  <a href="/timeline.php">タイムラインはこちら</a>
</p>
<hr>
<p>
  現在ログインしている会員情報は以下のとおりです。
</p>
<dl> <!-- 登録情報を出力する際はXSS防止のため htmlspecialchars() を必ず使いましょう -->
  <dt>ID</dt>
  <dd><?= htmlspecialchars($user['id']) ?></dd>
  <dt>メールアドレス</dt>
  <dd><?= htmlspecialchars($user['email']) ?></dd>
  <dt>名前</dt>
  <dd><?= htmlspecialchars($user['name']) ?></dd>
</dl>
