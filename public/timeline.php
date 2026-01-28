<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

session_start();
if (empty($_SESSION['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: /login2.php");
  return;
}

// 現在のログイン情報を取得する
$user_select_sth = $dbh->prepare("SELECT * from users WHERE id = :id");
$user_select_sth->execute([':id' => $_SESSION['login_user_id']]);
$user = $user_select_sth->fetch();

// 投稿処理
if (isset($_POST['body']) && !empty($_SESSION['login_user_id'])) {

  $image_filename = null;
  
  // base64が送られてきた場合
  if (!empty($_POST['image_base64'])) {
    // 先頭の data:~base64, のところは削る
    $base64 = preg_replace('/^data:.+base64,/', '', $_POST['image_base64']);

    // base64からバイナリにデコードする
    $image_binary = base64_decode($base64);

    // 新しいファイル名を決めてバイナリを出力する
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.png';
    $filepath =  '/var/www/upload/image/' . $image_filename;
    file_put_contents($filepath, $image_binary);
  }

  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (user_id, body, image_filename) VALUES (:user_id, :body, :image_filename)");
  $insert_sth->execute([
    ':user_id' => $_SESSION['login_user_id'], // 投稿者ID
    ':body' => $_POST['body'], // 投稿本文
    ':image_filename' => $image_filename, // 画像の名前
  ]);

  // 処理が終わったらリダイレクト
  header("HTTP/1.1 303 See Other");
  header("Location: ./timeline.php");
  return;
}
?>

<div>
  現在 <?= htmlspecialchars($user['name']) ?> (ID: <?= $user['id'] ?>) さんでログイン中
</div>
<div style="margin-bottom: 1em;">
  <a href="/setting/index.php">設定画面</a>
  /
  <a href="/users.php">会員一覧画面</a>
</div>

<!-- 投稿フォーム -->
<form method="POST" action="./timeline.php">
  <textarea name="body" required></textarea>
  <div style="margin: 1em 0;">
    <input type="file" accept="image/*" name="image" id="imageInput">
  </div>
  <input id="imageBase64Input" type="hidden" name="image_base64"><!-- base64を送る用 -->
  <canvas id="imageCanvas" style="display: none;"></canvas><!-- 画像縮小に使うcanvas -->
  <button type="submit">送信</button>
</form>
<hr>

<dl id="entryTemplate" style="display: none; margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
  <dt>番号</dt>
  <dd data-role="entryIdArea"></dd>
  <dt>投稿者</dt>
  <dd>
    <a href="" data-role="entryUserAnchor">
      <img data-role="entryUserIconImage"
        style="height: 2em; width: 2em; border-radius: 50%; object-fit: cover;">
      <span data-role="entryUserNameArea"></span>
    </a>
  </dd>
  <dt>日時</dt>
  <dd data-role="entryCreatedAtArea"></dd>
  <dt>内容</dt>
  <dd data-role="entryBodyArea">
  </dd>
</dl>
<div id="entriesRenderArea"></div>

<script>
document.addEventListener("DOMContentLoaded", () => {

  // 投稿テンプレートと描画エリア
  const entryTemplate = document.getElementById('entryTemplate');
  const entriesRenderArea = document.getElementById('entriesRenderArea');

  const LIMIT = 5;
  let offset = 0;  // 取得位置
  let isLoading = false;  // 読み込み中フラグ
  let isFinished = false;  // 全件読み終わったかどうか

  // 画像の描画
  function renderEntry(entry) {
    const entryCopied = entryTemplate.cloneNode(true);
    entryCopied.style.display = 'block';

    // 基本情報
    entryCopied.querySelector('[data-role="entryIdArea"]').innerText = entry.id;
    entryCopied.querySelector('[data-role="entryUserNameArea"]').innerText = entry.user_name;
    entryCopied.querySelector('[data-role="entryUserAnchor"]').href = entry.user_profile_url;
    entryCopied.querySelector('[data-role="entryCreatedAtArea"]').innerText = entry.created_at;
    entryCopied.querySelector('[data-role="entryBodyArea"]').innerHTML = entry.body;

    const icon = entryCopied.querySelector('[data-role="entryUserIconImage"]');
    if (entry.user_icon_file_url) {
      icon.src = entry.user_icon_file_url;
    } else {
      icon.style.display = 'none';
    }

    // 投稿画像がある
    if (entry.image_file_url) {
      const img = new Image();
      img.src = entry.image_file_url;
      img.style.maxWidth = '300px';
      img.style.marginTop = '1em';
      img.style.display = 'block';
      entryCopied.querySelector('[data-role="entryBodyArea"]').appendChild(img);
    }

    entriesRenderArea.appendChild(entryCopied);
  }

  // 投稿一覧を取得
  function loadEntries() {
    if (isLoading || isFinished) return;

    // 読み込み中フラグをON
    isLoading = true;

    // サーバーと通信するためのオブジェクト作成
    const xhr = new XMLHttpRequest();
    xhr.onload = () => {
      const response = xhr.response;

      // データがもうない
      if (!response.entries || response.entries.length === 0) {
        isFinished = true;
        isLoading = false;
        return;
      }

      // 投稿の表示
      response.entries.forEach(renderEntry);

      // 次に読み込む投稿の開始位置を更新
      offset += response.entries.length;

      // 読み込み中フラグを解除
      isLoading = false;
    };

    // サーバーに投稿データをJSONで取りに行く
    xhr.open(
      'GET',
      `/timeline_json.php?limit=${LIMIT}&offset=${offset}`,
      true
    );
    xhr.responseType = 'json';
    xhr.send();
  }

  // 初回読み込み
  loadEntries();

  window.addEventListener('scroll', () => {
    if (
      // ページ上から下までの高さ>= 全体の高さ- 200
      window.innerHeight + window.scrollY
      >= document.body.offsetHeight - 200
    ) {
      loadEntries();
    }
  });

});
</script>
 
