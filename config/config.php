<?php
	
//------------DATABASE SETUP-------------------
//Self Explanatory
define('DB_HOST', 'DB_HOST_REPLACE');
//Self Explanatory
define('DB_USER', 'DB_TABLE_NAME_REPLACE');
//Self Explanatory
define('DB_PASS', 'DB_PASSWORD_REPLACE');
//Self Explanatory
define('DB_NAME', 'DB_USERNAME_REPLACE');

//------------REGISTRAR SYSTEM SETUP-------------------
//The domain being distributed in lower case. e.g. example.com
define('DOMAIN_NAME', 'your_top_level_domain_replace'); 
//The domain name being distributed in UPPER CASE. e.g. EXAMPLE.COM
define('DOMAIN_NAME_UP', 'YOUR_TOP_LEVEL_DOMAIN_REPLACE'); 
//The domain name that runs the registrar system. e.g. registrar.example.com, nic.example.com
define('REGISTRAR_DOMAIN', 'REGISTRAR_DOMAIN_NAME_REPLACE'); 

//------------CLOUDFLARE SETUP-------------------
//Cloudflare API Key found in https://dash.cloudflare.com/profile/api-tokens
//You can create an API Token that limits access to the domain name you wish to distribute with permissions:
//DNS:Read, DNS:Edit
//e.g. 
//example@example.com's Account
//example.com - DNS:Read, DNS:Edit
define('CLOUDFLARE_API_KEY', 'CLOUDFLARE_API_KEY_REPLACE');
//Cloudflare zone ID, found under overview tab in Cloudflare of your distributing domain name.
define('CLOUDFLARE_ZONE_ID', 'CLOUDFLARE_ZONE_ID_REPLACE'); 


//------------H-Captcha SETUP-------------------
//H-Captcha site key
define('HCAPTCHA_SITE_KEY', 'HCAPTCHA_SITE_KEY_REPLACE');
//H-Captcha secret
define('HCAPTCHA_SECRET', 'HCAPTCHA_SECRET_REPLACE');

//------------Google Login SETUP-------------------
//Google Client ID From Google Cloud Console, e.g. ABCDEFG.apps.googleusercontent.com
define('GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_ID_REPLACE');
//Google Client Secret From Google Cloud Console, e.g. XXXXX-XXXXXXXXXX
define('GOOGLE_CLIENT_SECRET', 'GOOGLE_CLIENT_SECRET_REPLACE');