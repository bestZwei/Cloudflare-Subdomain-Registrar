<?php
require_once __DIR__ . '/includes/db.php';

$google_client_id = GOOGLE_CLIENT_ID;
$google_client_secret = GOOGLE_CLIENT_SECRET;
$redirect_uri = 'https://'.REGISTRAR_DOMAIN.'/google_callback.php';



if (isset($_GET['code'])) {
    // Get access token
    $token_url = "https://www.googleapis.com/oauth2/v4/token";
    $post_data = http_build_query([
        'client_id' => $google_client_id,
        'redirect_uri' => $redirect_uri,
        'client_secret' => $google_client_secret,
        'code' => $_GET['code'],
        'grant_type' => 'authorization_code'
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $post_data
        ]
    ]);

    $token_response = file_get_contents($token_url, false, $context);
    if($token_response === FALSE){
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve access token']);
        exit;
    }

    $token_data = json_decode($token_response, true);
    $access_token = $token_data['access_token'];

    // Get user info
    $user_info_request = "https://www.googleapis.com/oauth2/v2/userinfo?access_token=$access_token";
    $user_info_response = file_get_contents($user_info_request);
    $user_data = json_decode($user_info_response, true);
    $email = $user_data['email'];

    // Validate email format
    /*$validationResult = validateRegistration($email, 'google_login');
    if (!$validationResult['success']) {
        echo json_encode($validationResult);
        exit;
    }*/

    // Check if user exists in MySQL, if not, create
    global $conn;
    $stmt = $conn->prepare('SELECT id, username, role FROM users WHERE username = ?');
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $password = "google_login";
        $role = "user";
        $subdomain_limit = 5;

        $stmt = $conn->prepare('INSERT INTO users (username, password, role, subdomain_limit) VALUES (?, ?, ?, ?)');
        $stmt->bind_param("sssi", $email, $password, $role, $subdomain_limit);
        $stmt->execute();
        $user = [
            'id' => $conn->insert_id,
            'username' => $email,
            'role' => 'user'
        ];
    }

    // Start session and set session variables
    session_start();
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role']
    ];

    //echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => $_SESSION['user']]);
    header("Location: /views/dashboard.php");
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>
