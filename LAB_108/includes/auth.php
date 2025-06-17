<?php
session_start();

require_once '../config/db.php';

function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $password == $user['password']) {
        $_SESSION['user'] = $user;
        return true;
    }
    
  
    return false;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function is_superadmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'superadmin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function logout() {
    session_destroy();
    redirect('login.php');
}
?>