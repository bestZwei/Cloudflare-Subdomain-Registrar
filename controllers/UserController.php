<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cloudflare.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list_users') {
    listUsers();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $userId = filter_input(INPUT_POST, 'userId', FILTER_VALIDATE_INT);
    if ($userId === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    deleteUser($userId);
    exit;
}

function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
} 

function listUsers() {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    global $conn;
    $stmt = $conn->prepare("SELECT id, username, role, subdomain_limit FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
}

function deleteUser($userId) {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    global $conn;

    // Fetch all subdomains for the user
    $stmt = $conn->prepare("SELECT subdomain FROM domains WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Delete each DNS record from Cloudflare
    while ($row = $result->fetch_assoc()) {
        $subdomain = $row['subdomain'];
        $cloudflareResult = deleteARecord(CLOUDFLARE_ZONE_ID, $subdomain);
        if (!$cloudflareResult['success']) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete DNS record from Cloudflare: ' . $cloudflareResult['message']]);
            return;
        }
    }
    $stmt->close();

    // Delete user's domains from the database
    $stmt = $conn->prepare("DELETE FROM domains WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User and their domains deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
    $stmt->close();
}

echo json_encode(['success' => false, 'message' => 'Unknown Error']);