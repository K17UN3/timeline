<?php
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login2.php");
  return;
}

// DB 接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
$select_sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$select_sth->execute([
    ':id' => $_SESSION['login_user_id'],
]);
$user = $select_sth->fetch();

if (isset($_POST['image_base64'])) {
  $image_filename = null;
  if (!empty($_POST['image_base64'])) {

    $base64 = preg_replace('/^data:.+base64,/', '', $_POST['image_base64']);
    $image_binary = base64_decode($base64);

    // カバー画像のためのファイル名
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.png';
    $filepath = '/var/www/upload/image/' . $image_filename;
    file_put_contents($filepath, $image_binary);
  }

  // DB更新（アイコンではなくカバー画像）
  $update_sth = $dbh->prepare("UPDATE users SET cover_filename = :cover_filename WHERE id = :id");
  $update_sth->execute([
      ':id' => $user['id'],
      ':cover_filename' => $image_filename,
  ]);

  header("HTTP/1.1 302 Found");
  header("Location: /setting/cover_image.php");
  return;
}
?>

<a href="./index.php">設定一覧に戻る</a>

<h1>カバー画像設定/変更</h1>

<div>
  <?php if(empty($user['cover_filename'])): ?>
    現在未設定
  <?php else: ?>
    <img src="/image/<?= $user['cover_filename'] ?>"
      style="height: 10em; width: 100%; object-fit: cover; border-radius: 8px;">
  <?php endif; ?>
</div>

<form method="POST">
  <div style="margin: 1em 0;">
    <input type="file" accept="image/*" id="imageInput">
  </div>

<img id="preview" style="max-width: 100%; max-height: 250px; border-radius: 8px; display: none;">

  <input id="imageBase64Input" type="hidden" name="image_base64">
  <canvas id="imageCanvas" style="display: none;"></canvas>

  <button type="submit">アップロード</button>
</form>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const imageInput = document.getElementById("imageInput");
  imageInput.addEventListener("change", () => {

    if (imageInput.files.length < 1) return;
    const file = imageInput.files[0];
    if (!file.type.startsWith('image/')) return;

    const reader = new FileReader();
    const image = new Image();
    const canvas = document.getElementById("imageCanvas");
    const imageBase64Input = document.getElementById("imageBase64Input");

    reader.onload = () => {
      image.onload = () => {

        const originalWidth = image.naturalWidth;
        const originalHeight = image.naturalHeight;
        const maxLength = 2000; // カバー画像は大きめにOK

        if (originalWidth > originalHeight) {
          canvas.width = maxLength;
          canvas.height = maxLength * (originalHeight / originalWidth);
        } else {
          canvas.height = maxLength;
          canvas.width = maxLength * (originalWidth / originalHeight);
        }

        const ctx = canvas.getContext("2d");
        ctx.drawImage(image, 0, 0, canvas.width, canvas.height);

        imageBase64Input.value = canvas.toDataURL();
      };
      image.src = reader.result;
    };

    reader.readAsDataURL(file);
  });
});
</script>

