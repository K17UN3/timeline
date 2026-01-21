<?php
session_start();

if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: ./login.php");
  return;
}

// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// フォロー解除対象(フォローされる側)のデータを引く
$followee_user = null;
if (!empty($_GET['followee_user_id'])) {
  $select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
  $select_sth->execute([
      ':id' => $_GET['followee_user_id'],
  ]);
  $followee_user = $select_sth->fetch();
}
if (empty($followee_user)) {
  header("HTTP/1.1 404 Not Found");
  print("そのようなユーザーIDの会員情報は存在しません");
  return;
}

// 現在のフォロー状態をDBから取得
$select_sth = $dbh->prepare(
  "SELECT * FROM user_relationships"
  . " WHERE follower_user_id = :follower_user_id AND followee_user_id = :followee_user_id"
);
$select_sth->execute([
  ':followee_user_id' => $followee_user['id'], // フォローされる側(フォロー対象)
  ':follower_user_id' => $_SESSION['login_user_id'], // フォローする側はログインしている会員
]);
$relationship = $select_sth->fetch();

// フォロー状態が存在しなければエラー表示
if (empty($relationship)) {
  print("フォローしていません。");
  return;
}

$delete_result = false;

// フォームでPOSTした場合はフォロー解除を実行
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $delete_sth = $dbh->prepare(
    "DELETE FROM user_relationships 
     WHERE follower_user_id = :follower_user_id AND followee_user_id = :followee_user_id"
  );
  $delete_result = $delete_sth->execute([
    ':followee_user_id' => $followee_user['id'],
    ':follower_user_id' => $_SESSION['login_user_id'],
  ]);
}
?>

<?php if($delete_result): ?>
<div>
  <?= htmlspecialchars($followee_user['name']) ?> さんのフォローを解除しました。<br>
  <a href="/profile.php?user_id=<?= $followee_user['id'] ?>">
    <?= htmlspecialchars($followee_user['name']) ?> さんのプロフィールに戻る
  </a>
  /
  <a href="/follow_list.php">
    フォロー一覧に戻る
  </a>
</div>
<?php else: ?>
<div>
  <?= htmlspecialchars($followee_user['name']) ?> さんのフォローを解除しますか?
  <form method="POST">
    <button type="submit">
      フォロー解除する
    </button>
  </form>
</div>
<?php endif; ?>

