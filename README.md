#### 〈 EC2インスタンス作成 〉
１．AWSコンソールにログイン  
２．インスタンスからEC2を選択  
３．インスタンスの起動  
４．「名前とタグ」を設定  
　　　（マシンイメージ、インスタンスタイプはそのまま）  
５．「キーペア(ログイン)」で新しいキーペア → 名前を入れて作成  
　　　（タイプ：RSA、ファイル形式：.pem のまま）  
６．「インターネットからのHTTPトラフィックを許可」をチェック  
７．「ストレージ」を 20GiB に設定  
８．「インスタンスを起動」する  

#### 〈 EC2インスタンスへPCからSSHでログイン 〉
```
ssh ec2-user@{IPアドレス} -i {秘密鍵ファイルのパス}
```

#### 〈 vim インストール 〉
```
sudo yum install vim -y
```

#### 〈 screen インストール 〉
```
sudo yum install screen -y
```

#### 〈 Docker インストール 〉
```
sudo yum install -y docker  
sudo systemctl start docker  
sudo systemctl enable docker  
sudo usermod -a -G docker ec2-user
```

#### 〈 Docker Compose インストール 〉
```
sudo mkdir -p /usr/local/lib/docker/cli-plugins/
sudo curl -SL https://github.com/docker/compose/releases/download/v2.36.0/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose
sudo chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
```

#### 〈 設定ファイル作成 〉
・vim compose.yml  
・nginx/conf.d/default.conf  
・Dcokerfile  
※ プログラムコード通りに作成  

#### 〈 Dockerの起動・停止 〉
```
docker compose up
Ctrl + C
```

#### 〈 配信するファイルの設置場所：mkdir public 〉
※ プログラムコード通りに作成
