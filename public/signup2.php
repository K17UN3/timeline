<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['password'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        $check_sth = $dbh->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $check_sth->execute([':email' => $email]);
	$count = $check_sth->fetchColumn();

        if ($count > 0) {
            $error_message = "このメールアドレスは既に登録されています。";
	} else {
	    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insert_sth = $dbh->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
            $insert_sth->execute([
                ':name' => $name,
                ':email' => $email,
		':password' => $hashed_password,
            ]);

            header("HTTP/1.1 303 See Other");
            header("Location: ./signup_finish.php");
            exit;
        }
    }
}
?>

<h1>会員登録</h1>

会員登録済の人は<a href="/login2.php">ログイン</a>しましょう。
<hr>

<?php if (!empty($error_message)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
<?php endif; ?>

<!-- 登録フォーム -->
<form method="POST">
  <label>
    名前:
    <input type="text" name="name">
  </label>
  <br>
  <label>
    メールアドレス:
    <input type="email" name="email">
  </label>
  <br>
  <label>
    パスワード:
    <input type="password" name="password" minlength="6" autocomplete="new-password">
  </label>
  <br>
  <button type="submit">決定</button>
</form>

