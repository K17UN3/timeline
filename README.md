#### 〈 EC2インスタンスへSSHでログイン 〉
```powershell
ssh ec2-user@IPアドレス -i 秘密鍵ファイルのパス
```

#### 〈 vim インストール 〉
```powershell
sudo yum install vim -y
```

#### 〈 screen インストール 〉
```powershell
sudo yum install screen -y
```

#### 〈 Docker インストール 〉
```powershell
sudo yum install -y docker  
sudo systemctl start docker  
sudo systemctl enable docker  
sudo usermod -a -G docker ec2-user
```

#### 〈 Docker Compose インストール 〉
```powershell
sudo mkdir -p /usr/local/lib/docker/cli-plugins/
sudo curl -SL https://github.com/docker/compose/releases/download/v2.36.0/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose
sudo chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
```

#### 〈 Git インストールと設定 〉
```powershell
sudo yum install git -y
git config --global init.defaultBranch main
git config --global user.name "名前"
git config --global user.email "GitHubに登録したメールアドレス"
```

#### 〈 Dockerの起動・停止 〉
```powershell
docker compose up
Ctrl + C
```

#### 〈 MySQL 起動方法とテーブル作成 〉
dockerを起動した後に、example_db内に入る
```powershell
docker compose exec mysql mysql example_db
```

テーブル作成 CREATE文
```mysql
CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  password TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  icon_filename TEXT,
  bio TEXT,
  cover_filename VARCHAR(255),
  birthday DATE,
  PRIMARY KEY (id)
);

CREATE TABLE bbs_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  image_filename TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
);

CREATE TABLE user_relationships (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  followee_user_id INT UNSIGNED NOT NULL,
  follower_user_id INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
):
```

#### 〈 screenの起動・停止 〉
・作業用ディレクトリを作成する場合は、作ったディレクトリに cd で入ってから「screen」コマンドを実行  
・停止方法：「exit」コマンドを実行
```powershell
screen
exit
```

#### 〈 Gitからクローンする 〉
・作業用ディレクトリを作ってGitからクローンする  
・「pwd」 で「/home/ec2-user/workspace」となっていれば良い  
・GitHubのCode(緑のボタン)からSSHのURLをコピーしてクローンする
```powershell
mkdir workspace
cd workspace
pwd
git clone git@github.com:K17UN3/timeline.git
```
