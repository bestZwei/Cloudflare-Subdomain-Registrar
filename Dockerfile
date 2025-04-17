FROM php:8.1-apache

# 设置工作目录
WORKDIR /var/www/html

# 安装依赖
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    libcurl4-openssl-dev \
    && docker-php-ext-install mysqli pdo_mysql zip curl

# 启用Apache模块
RUN a2enmod rewrite

# 复制应用程序文件
COPY . /var/www/html/
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# 设置权限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 设置配置文件权限
RUN mkdir -p /var/www/html/config \
    && touch /var/www/html/config/config.php \
    && chmod 644 /var/www/html/config/config.php

# 暴露端口
EXPOSE 80

# 启动Apache
CMD ["apache2-foreground"]