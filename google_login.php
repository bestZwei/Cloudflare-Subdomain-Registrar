<?php
include 'config/config.php';
$google_client_id = GOOGLE_CLIENT_ID;
$redirect_uri = 'https://'.REGISTRAR_DOMAIN.'/google_callback.php';
$scope = 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile';

$google_login_url = "https://accounts.google.com/o/oauth2/v2/auth?scope=$scope&redirect_uri=$redirect_uri&response_type=code&client_id=$google_client_id&access_type=online";

header("Location: $google_login_url");
exit;
?>
