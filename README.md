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
・compose.yml  
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
