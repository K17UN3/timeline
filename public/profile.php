<?php
$user = null;
if (!empty($_GET['user_id'])) {
  $user_id = $_GET['user_id'];
  // DBに接続
  $dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
  // 対象の会員情報を引く
  $select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
  $select_sth->execute([
    ':id' => $user_id,
  ]);
  $user = $select_sth->fetch();
}
if (empty($user)) {
  header("HTTP/1.1 404 Not Found");
  print("そのようなユーザーIDの会員情報は存在しません");
  return;
}

// 投稿データを取得
$select_sth = $dbh->prepare(
  'SELECT bbs_entries.*, users.name AS user_name, users.icon_filename AS user_icon_filename
   FROM bbs_entries
   INNER JOIN users ON bbs_entries.user_id = users.id
   WHERE bbs_entries.user_id = :user_id
   ORDER BY bbs_entries.created_at DESC'
);

$select_sth->execute([':user_id' => $user_id]);

// 投稿データを配列で取得
$posts = $select_sth->fetchAll(PDO::FETCH_ASSOC);

// フォロー状態を取得
$relationship = null;
session_start();
if (!empty($_SESSION['login_user_id'])) { // ログインしている場合
  // フォロー状態をDBから取得
  $select_sth = $dbh->prepare(
    "SELECT * FROM user_relationships"
    . " WHERE follower_user_id = :follower_user_id AND followee_user_id = :followee_user_id"
  );
  $select_sth->execute([
    ':followee_user_id' => $user['id'], // フォローされる側は閲覧しようとしているプロフィールの会員
    ':follower_user_id' => $_SESSION['login_user_id'], // フォローする側はログインしている会員
  ]);
  $relationship = $select_sth->fetch();
}

// 相手が自分をフォローしているか確認
$select_sth = $dbh->prepare(
  "SELECT * FROM user_relationships
    WHERE follower_user_id = :follower_user_id
      AND followee_user_id = :followee_user_id"
);

$select_sth->execute([
  ':follower_user_id' => $user['id'], // プロフィールのユーザー（相手）
  ':followee_user_id' => $_SESSION['login_user_id'], // 自分
]);
$followed_relationship = $select_sth->fetch();
?>

<a href="/timeline.php">タイムラインに戻る</a>

<?php
$cover = $user['cover_filename'] ?? null;
$icon  = $user['icon_filename'] ?? null;
?>

<!-- カバー画像 or 未設定 -->
<div style="
  width:100%; height:180px; overflow:hidden;
  background: <?= $cover ? 'none' : '#ddd' ?>;
  display:flex; justify-content:center; align-items:center;
  color:#666;
">
  <?php if($cover): ?>
    <img src="/image/<?= htmlspecialchars($cover) ?>"
      style="width:100%; height:100%; object-fit:cover;">
  <?php else: ?>
    カバー画像未設定
  <?php endif; ?>
</div>

<!-- アイコン + 名前 -->
<div style="
  display:flex; align-items:center;
  gap:1em;
  margin-top:-35px;    /* アイコンをカバーにかぶせる */
  padding:0 1em 1em;   /* 下に余白 */
">
  <?php if($icon): ?>
    <img src="/image/<?= htmlspecialchars($icon) ?>"
      style="
        width:70px; height:70px; border-radius:50%;
        object-fit:cover; border:3px solid #fff; flex-shrink:0;
      ">
  <?php else: ?>
    <div style="
      width:70px; height:70px; border-radius:50%;
      background:#ccc; color:#666;
      display:flex; justify-content:center; align-items:center;
      flex-shrink:0;
    ">
      未設定
    </div>
<?php endif; ?>

<h1 style="
margin:0;
padding-top:40px;  /* アイコンの分だけ少し下にずらす */
font-size:1.4em;
">
<?= htmlspecialchars($user['name']) ?>
</h1>
</div>

<hr>

<h2>自己紹介</h2>

<?php if($user['id'] === $_SESSION['login_user_id']): // 自分自身の場合 ?>
<div style="margin: 1em 0;">
  これはあなたです！<br>
  <a href="/setting/index.php">設定画面はこちら</a>
</div>
<?php else: // 他人の場合 ?>
<div style="margin: 1em 0;">
  <?php if(empty($relationship)): // フォローしていない場合 ?>
  <div>
    <a href="./follow.php?followee_user_id=<?= $user['id'] ?>">フォローする</a>
  </div>
  <?php else: // フォローしている場合 ?>
  <div>
    <?= $relationship['created_at'] ?> にフォローしました。
  </div>
  <?php endif; ?>
  <?php if(!empty($follower_relationship)): // フォローされている場合 ?>
  <div>
    フォローされています。
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
if (!empty($user['birthday'])) {
    $birthDate = new DateTime($user['birthday']);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    echo "<p>{$age}歳</p>";
} else {
    echo '<p>年齢未設定</p>';
}
?>

<div style="white-space: pre-wrap;"><?= 
  !empty($user['bio']) 
    ? htmlspecialchars(trim($user['bio'])) 
    : '自己紹介未設定' 
?></div>

<h2>投稿一覧</h2>

<?php if (empty($posts)): ?>
  <p>まだ投稿がありません。</p>
<?php else: ?>
  <ul>
    <?php foreach ($posts as $post): ?>
      <li style="margin-bottom: 1.5em; list-style: none; border-bottom: 1px solid #ccc; padding-bottom: 0.5em;">
        <div>
          <dt>日時</dt>
          <dd><?= htmlspecialchars($post['created_at']) ?></dd>
          <dt>内容</dt>
          <dd><?= nl2br(htmlspecialchars($post['body'])) ?></dd>
        </div>

        <?php if (!empty($post['image_filename'])): ?>
          <div style="margin-top: 0.5em;">
            <img src="/image/<?= htmlspecialchars($post['image_filename']) ?>" style="max-height: 10em; display:block;">
          </div>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
