<?php
session_start();
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// ログインユーザーID
$login_user_id = $_SESSION['login_user_id'] ?? null;

$search_name = $_GET['search_name'] ?? '';
$year_from = $_GET['year_from'] ?? '';
$year_to = $_GET['year_to'] ?? '';

$where = [];
$params = [];

// 名前検索
if ($search_name !== '') {
    $where[] = "name LIKE :name";
    $params[':name'] = "%$search_name%";
}

// 年の範囲検索
if ($year_from !== '' && ctype_digit($year_from) && $year_to !== '' && ctype_digit($year_to)) {
    $where[] = "birth_year BETWEEN :year_from AND :year_to";
    $params[':year_from'] = (int)$year_from;
    $params[':year_to'] = (int)$year_to;
}

// AND条件で結合
$where_sql = '';
if ($where) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// 会員データ取得
$select_sth = $dbh->prepare("SELECT * FROM users $where_sql ORDER BY id DESC");
$select_sth->execute($params);
?>

<body>
<h1>会員一覧</h1>

<div>
  <a href="/setting/index.php">設定画面</a> /
  <a href="/timeline.php">タイムライン</a>
</div>

<form method="get">
  <div>
    <input type="text" name="search_name" placeholder="名前で検索" value="<?= htmlspecialchars($search_name) ?>">
  </div>
  <div>
    <input type="text" name="year_from" placeholder="XXXX年" value="<?= htmlspecialchars($year_from) ?>">
    ～
    <input type="text" name="year_to" placeholder="YYYY年" value="<?= htmlspecialchars($year_to) ?>">
  </div>
  <div>
    <button type="submit">検索</button>
  </div>
</form>

<?php foreach($select_sth as $user): ?>
  <?php
  $relationship = null;

  if ($login_user_id !== null && $user['id'] !== $login_user_id) {
    $sth = $dbh->prepare(
      "SELECT * FROM user_relationships
       WHERE follower_user_id = :follower
       AND followee_user_id = :followee"
    );
    $sth->execute([
      ':follower' => $login_user_id,
      ':followee' => $user['id'],
    ]);
    $relationship = $sth->fetch();
  }
?>

  <div style="display: flex; align-items: center; padding: 1em 2em; gap: 1em;">
    <?php if(empty($user['icon_filename'])): ?>
      <div style="height: 2em; width: 2em;"></div>
    <?php else: ?>
      <img src="/image/<?= htmlspecialchars($user['icon_filename']) ?>"
           style="height: 2em; width: 2em; border-radius: 50%; object-fit: cover;">
    <?php endif; ?>

    <a href="/profile.php?user_id=<?= $user['id'] ?>" style="margin-left: 1em;">
      <?= htmlspecialchars($user['name']) ?>
    </a>

    <?php if ($login_user_id !== null && $user['id'] !== $login_user_id): ?>
      <?php if (empty($relationship)): ?>
        <a href="/follow.php?followee_user_id=<?= (int)$user['id'] ?>
           <?= $search_name !== '' ? '&search_name='.urlencode($search_name) : '' ?>
           <?= ($year_from !== '' && $year_to !== '') ? '&year_from='.urlencode($year_from).'&year_to='.urlencode($year_to) : '' ?>">
           フォローする
        </a>
      <?php else: ?>
        フォローしています
      <?php endif; ?>
  <?php elseif ($login_user_id === $user['id']): ?>
    これはあなたです！
  <?php endif; ?>
</div>

  <hr style="border: none; border-bottom: 1px solid gray;">
<?php endforeach; ?>
</body>

