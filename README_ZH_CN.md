# SubdomainRegistrarCloudflare
一个基于 Cloudflare DNS 的子域名注册和管理 PHP 程序。

# 待办事项

- [ ] 编辑 NS 服务器记录
- [ ] 多语言支持
- [ ] 多域名支持

# 配置
请仔细阅读并按照以下步骤操作。

## 第一步：创建并导入数据库
在你的服务器上创建一个 MySQL 数据库，并导入 `cloudflareNIC.sql` 文件。确保记下你的数据库名称、用户名和密码。

## 第二步：配置 `config.php`

### 1. 数据库配置

使用你的数据库凭证更新以下常量：

```php
define('DB_HOST', 'your-database-host');        // 例如：localhost
define('DB_USER', 'your-database-username');
define('DB_PASS', 'your-database-password');
define('DB_NAME', 'your-database-name');
```

---

### 2. 注册系统设置

用你的域名信息替换以下内容：

```php
define('DOMAIN_NAME', 'example.com');           // 小写版本
define('DOMAIN_NAME_UP', 'EXAMPLE.COM');        // 大写版本
define('REGISTRAR_DOMAIN', 'registrar.example.com');  // 注册系统的域名
```

---

### 3. Cloudflare 设置

为你的域名创建一个带有 DNS:Read 和 DNS:Edit 权限的 API 令牌。

```php
define('CLOUDFLARE_API_KEY', 'your-cloudflare-api-token');
define('CLOUDFLARE_ZONE_ID', 'your-cloudflare-zone-id');
```

在 [hCaptcha 控制台](https://dash.cloudflare.com/profile/api-tokens) 获取 API Token。
你可以在 Cloudflare 控制面板的域名“概览”栏目中找到 Zone ID。

---

### 4. HCaptcha 集成

设置 HCaptcha 以防止机器人攻击。

```php
define('HCAPTCHA_SITE_KEY', 'your-hcaptcha-site-key');
define('HCAPTCHA_SECRET', 'your-hcaptcha-secret-key');
```

从你的 [hCaptcha 控制台](https://dashboard.hcaptcha.com/) 获取这些密钥。

---

### 5. Google 登录设置

在 [Google Cloud Console](https://console.cloud.google.com/) 启用 OAuth 并获取你的凭证。
详细指南见：[设置 Google 登录](https://documentation.commerce7.com/how-do-i-setup-google-login)
按照指南的第 1 步进行，你就可以获得此步骤所需的所有信息。

```php
define('GOOGLE_CLIENT_ID', 'your-google-client-id');
define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret');
```

使用如下格式的重定向 URI：
```
https://your-registrar-domain.com/oauth_callback.php
```

---

## 第三步：在 MySQL 表中更新用户角色
你使用 Google 登录后需要手动更新 MySQL 中的条目，将你的用户记录的 `role` 字段从 `user` 改为 `admin`。完成此操作后登出并重新登录，你将能够访问管理员面板。
