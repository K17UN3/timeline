<?php
$session_id = $_COOKIE['session_id'] ?? base64_encode(random_bytes(64));
if (!isset($_COOKIE['session_id'])) setcookie('session_id', $session_id);

$redis = new Redis();
$redis->connect('redis', 6379);
$session = $redis->exists("session-$session_id") ? json_decode($redis->get("session-$session_id"), true) : [];

if (empty($session['login_user_id'])) {
    header("Location: ./login.php", true, 302);
    exit;
}

$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$sth->execute([':id' => $session['login_user_id']]);
$user = $sth->fetch();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update'])) {
    $dbh->prepare("UPDATE users SET name = :name WHERE id = :id")->execute([
        ':name' => $_POST['name'] ?? '',
        ':id' => $user['id']
    ]);
    $message = "名前の変更処理が完了しました";
    $user['name'] = $_POST['name'] ?? '';
}
?>

<h1>会員情報の変更</h1>
<?php if (!empty($message)) echo "<p style='color: green;'>".htmlspecialchars($message)."</p>"; ?>

<form method="post">
  <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
    <label>
    名前: 
    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>">
  </label><br>
  <button type="submit" name="update">決定</button>
</form>

