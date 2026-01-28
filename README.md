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

## ファイル作成：vim compose.yml

【内容】
```
services:
  web:
    image: nginx:latest
    ports:
      - 80:80 
    volumes:
      - ./nginx/conf.d/:/etc/nginx/conf.d/
      - ./public/:/var/www/public/
      - image:/var/www/upload/image/
    depends_on:
      - php
      - redis
  php:
    container_name: php
    build:
      context: .
      target: php
    volumes:
      - ./public/:/var/www/public/
      - image:/var/www/upload/image/
  mysql:
    container_name: mysql
    image: mysql:8.4
    environment:
      MYSQL_DATABASE: example_db
      MYSQL_ALLOW_EMPTY_PASSWORD: 1
      TZ: Asia/Tokyo
    volumes:
      - mysql:/var/lib/mysql
    command: >
      mysqld
      --character-set-server=utf8mb4
      --collation-server=utf8mb4_unicode_ci
      --max_allowed_packet=4MB
  redis:
    container_name: redis
    image: redis:latest
    ports:
      - 6379:6379
volumes:
  mysql:
  image:
```

## ファイル作成：vim nginx/conf.d/default.conf

【内容】
```
server {
    listen       0.0.0.0:80;
    server_name  _;
    charset      utf-8;
    client_max_body_size 6M;

    root /var/www/public;

    location ~ \.php$ {
        fastcgi_pass  php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include       fastcgi_params;
    }

    location /image/ {
        root /var/www/upload;
    }

    location /upload/image/ {
      alias /var/www/upload/image/;
      alias /var/www/upload/user_icon/;

      # 画像のみ許可（任意だが推奨）
      autoindex off;

      # キャッシュ（任意）
      expires 30d;
      add_header Cache-Control "public";
  }
}
```

## ファイル作成：vim Dcokerfile

【内容】
```
FROM php:8.4-fpm-alpine AS php

RUN apk add --no-cache autoconf build-base \
    && yes '' | pecl install redis \
    && docker-php-ext-enable redis

RUN docker-php-ext-install pdo_mysql

RUN install -o www-data -g www-data -d /var/www/upload/image/

COPY ./php.ini ${PHP_INI_DIR}/php.ini
```

#### 〈 Dockerの起動・停止 〉
```
docker compose up
Ctrl + C
```

#### 〈 配信するファイルの設置場所：mkdir public 〉
※ プログラムコード通りに作成
