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
        
        // --- Logic to redirect based on role ---
        if ($user['role'] === 'superadmin') {
            redirect('dashboard.php'); 
        } elseif ($user['role'] === 'admin') {
            redirect('dashboard.php'); 
        } elseif ($user['role'] === 'secretary') {
            redirect('dea_visits.php'); 
        }
        redirect('login.php'); 

        return true; 
    }
    
    return false; // فشل تسجيل الدخول
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function is_superadmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'superadmin';
}

function is_admin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function is_secretary() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'secretary';
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