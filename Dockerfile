FROM php:8.2-apache

# 安裝 PostgreSQL 的 PHP 擴充套件
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# 將本機所有程式碼複製到 Apache 的網頁根目錄
COPY . /var/www/html/

# 開放 80 埠號
EXPOSE 80