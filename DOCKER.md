# Docker 部署指南

本项目已支持 Docker 部署，通过以下步骤可以快速搭建环境。

## 前提条件

- 安装 [Docker](https://docs.docker.com/get-docker/)
- 安装 [Docker Compose](https://docs.docker.com/compose/install/)
- 拥有 GitHub 账户（用于设置 CI/CD）
- 拥有 DockerHub 账户（用于存储镜像）

## 本地部署

### 1. 准备环境变量（可选）

创建 `.env` 文件设置数据库参数：

```env
MYSQL_ROOT_PASSWORD=your_secure_root_password
MYSQL_DATABASE=cloudflareNIC
MYSQL_USER=cloudflare
MYSQL_PASSWORD=your_secure_password
```

### 2. 准备配置文件

创建 `config.php` 文件并填入正确的配置信息：

```php
<?php
//数据库配置
define('DB_HOST', 'db'); // 使用Docker内部服务名称
define('DB_USER', 'cloudflare'); // 与.env中MYSQL_USER保持一致
define('DB_PASS', 'your_secure_password'); // 与.env中MYSQL_PASSWORD保持一致
define('DB_NAME', 'cloudflareNIC'); // 与.env中MYSQL_DATABASE保持一致

//系统配置
define('DOMAIN_NAME', 'your_top_level_domain_replace'); 
define('DOMAIN_NAME_UP', 'YOUR_TOP_LEVEL_DOMAIN_REPLACE'); 
define('REGISTRAR_DOMAIN', 'REGISTRAR_DOMAIN_NAME_REPLACE'); 

//Cloudflare配置
define('CLOUDFLARE_API_KEY', 'CLOUDFLARE_API_KEY_REPLACE');
define('CLOUDFLARE_ZONE_ID', 'CLOUDFLARE_ZONE_ID_REPLACE'); 

//HCaptcha配置
define('HCAPTCHA_SITE_KEY', 'HCAPTCHA_SITE_KEY_REPLACE');
define('HCAPTCHA_SECRET', 'HCAPTCHA_SECRET_REPLACE');

//Google登录配置
define('GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_ID_REPLACE');
define('GOOGLE_CLIENT_SECRET', 'GOOGLE_CLIENT_SECRET_REPLACE');
```

### 3. 启动服务

```bash
docker-compose up -d
```

服务将在 http://localhost 上运行，MySQL数据库可通过 localhost:3306 访问。

### 4. 初始化管理员账户

使用Google账号登录后，执行以下SQL设置管理员权限：

```sql
UPDATE users SET role = 'admin' WHERE username = 'your_google_email@gmail.com';
```

## 生产环境部署

在生产环境中，建议：

1. 使用HTTPS（可通过反向代理如Nginx或Traefik配置）
2. 确保数据库密码安全且定期更换
3. 限制MySQL和容器对外暴露的端口

## GitHub Actions 自动部署

项目已配置GitHub Actions工作流，每次推送到main分支时自动构建Docker镜像并发布到DockerHub。

### 1. 设置GitHub Secrets

在GitHub仓库的Settings > Secrets and variables > Actions中添加以下secrets：

- `DOCKER_USERNAME`: 您的DockerHub用户名
- `DOCKER_TOKEN`: 您的DockerHub访问令牌（非密码）

### 2. 推送代码触发构建

代码推送到main分支后，GitHub Actions将自动构建并推送镜像：
- `bestzwei/cloudflare-subdomain-registrar:latest` - 最新版本
- `bestzwei/cloudflare-subdomain-registrar:[commit-sha]` - 特定提交版本

### 3. 使用镜像

创建docker-compose-prod.yml文件：

```yaml
version: '3'

services:
  app:
    image: bestzwei/cloudflare-subdomain-registrar:latest
    container_name: cloudflare-subdomain-app
    restart: always
    ports:
      - "80:80"
    volumes:
      - ./config.php:/var/www/html/config/config.php
    depends_on:
      - db

  db:
    image: mysql:8.0
    container_name: cloudflare-subdomain-db
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./cloudflareNIC.sql:/docker-entrypoint-initdb.d/cloudflareNIC.sql

volumes:
  mysql_data:
```

然后运行：
```bash
docker-compose -f docker-compose-prod.yml up -d
```