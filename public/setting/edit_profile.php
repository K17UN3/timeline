<?php
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();
$user_id = $_SESSION['login_user_id'] ?? null;
if (!$user_id) {
  header('Location: /login.php');
  exit;
}

$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $bio = $_POST['bio'] ?? '';
  // 最大1000文字に制限
  if (mb_strlen($bio) > 1000) {
    $error = "自己紹介文は1000文字以内で入力してください。";
  } else {
    $update_sth = $dbh->prepare("UPDATE users SET bio = :bio WHERE id = :id");
    $update_sth->execute([
      ':bio' => $bio,
      ':id' => $user_id,
    ]);
    $message = "プロフィールを更新しました！";
  }
}

// 現在の情報取得
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$select_sth->execute([':id' => $user_id]);
$user = $select_sth->fetch();
?>

<a href="./index.php">設定一覧に戻る</a>

<h1>プロフィール編集</h1>

<?php if (!empty($message)): ?>
  <p style="color: green;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
  <label>自己紹介文（1000文字以内）:</label><br>
  <textarea name="bio" rows="6" cols="60" maxlength="1000"><?= 
    htmlspecialchars(trim($user['bio'] ?? '')) 
  ?></textarea>
  <div>
    <button type="submit">保存する</button>
  </div>
</form>

