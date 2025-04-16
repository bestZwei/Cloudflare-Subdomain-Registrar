<?php
require_once 'db.php';

function registerUser($username, $password, $role = 'user') {
    global $conn;
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashedPassword, $role);
    return $stmt->execute();
}

function loginUser($username, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashedPassword, $role);
        $stmt->fetch();
        if (password_verify($password, $hashedPassword)) {
            return ['id' => $id, 'role' => $role];
        }
    }
    return false;
} 