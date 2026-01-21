<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

session_start();
if (empty($_SESSION['login_user_id'])) { // 非ログインの場合利用不可
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

  // insertする
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (user_id, body, image_filename) VALUES (:user_id, :body, :image_filename)");
  $insert_sth->execute([
    ':user_id' => $_SESSION['login_user_id'], // ログインしている会員情報の主キー
    ':body' => $_POST['body'], // フォームから送られてきた投稿本文
    ':image_filename' => $image_filename, // 保存した画像の名前 (nullの場合もある)
  ]);

  // 処理が終わったらリダイレクトする
  // リダイレクトしないと，リロード時にまた同じ内容でPOSTすることになる
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
<!-- フォームのPOST先はこのファイル自身にする -->
<form method="POST" action="./timeline.php"><!-- enctypeは外しておきましょう -->
  <textarea name="body" required></textarea>
  <div style="margin: 1em 0;">
    <input type="file" accept="image/*" name="images[]" id="imageInput" multiple>
    <input id="imageBase64Input" type="hidden" name="image_base64_json">
  </div>
  <input id="imageBase64Input" type="hidden" name="image_base64"><!-- base64を送る用のinput (非表示) -->
  <canvas id="imageCanvas" style="display: none;"></canvas><!-- 画像縮小に使うcanvas (非表示) -->
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

  const entryTemplate = document.getElementById('entryTemplate');
  const entriesRenderArea = document.getElementById('entriesRenderArea');

  const LIMIT = 5;
  let offset = 0;
  let isLoading = false;
  let isFinished = false;

  function renderEntry(entry) {
    const entryCopied = entryTemplate.cloneNode(true);
    entryCopied.style.display = 'block';

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

    // 画像（複数対応）
    if (entry.image_file_urls && entry.image_file_urls.length > 0) {
      entry.image_file_urls.forEach(url => {
        const img = new Image();
        img.src = url;
        img.style.maxWidth = '300px';
        img.style.margin = '0.5em 0.5em 0 0';
        entryCopied.querySelector('[data-role="entryBodyArea"]').appendChild(img);
      });
    }

    entriesRenderArea.appendChild(entryCopied);
  }

  function loadEntries() {
    if (isLoading || isFinished) return;

    isLoading = true;

    const xhr = new XMLHttpRequest();
    xhr.onload = () => {
      const response = xhr.response;

      if (!response.entries || response.entries.length === 0) {
        isFinished = true;
        isLoading = false;
        return;
      }

      response.entries.forEach(renderEntry);

      offset += response.entries.length;
      isLoading = false;
    };

    xhr.open('GET', `/timeline_json.php?limit=${LIMIT}&offset=${offset}`, true);
    xhr.responseType = 'json';
    xhr.send();
  }

  loadEntries();

  // スクロールで追加読み込み
  window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 200) {
      loadEntries();
    }
  });


  // 画像複数アップ用
  const imageInput = document.getElementById('imageInput');
  const imageBase64Input = document.getElementById('imageBase64Input');
  const canvas = document.getElementById('imageCanvas');
  const ctx = canvas.getContext('2d');

  imageInput.addEventListener('change', () => {
    const files = Array.from(imageInput.files).slice(0, 4); // 最大4枚
    const base64List = [];
    let processed = 0;

    files.forEach(file => {
      const reader = new FileReader();
      reader.onload = () => {
        const img = new Image();
        img.onload = () => {
          const maxWidth = 800;
          const scale = Math.min(1, maxWidth / img.width);
          canvas.width = img.width * scale;
          canvas.height = img.height * scale;

          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          base64List.push(canvas.toDataURL('image/png'));

          processed++;
          if (processed === files.length) {
            imageBase64Input.value = JSON.stringify(base64List);
          }
        };
        img.src = reader.result;
      };
      reader.readAsDataURL(file);
    });
  });

});
</script>
