<?php
session_start();

function checkAuth() {
    // Eğer kullanıcı giriş yapmamışsa login sayfasına yönlendir
    if(!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    // Session süresi kontrolü (8 saat)
    if(isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 28800) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    
    // Session'ı yenile
    $_SESSION['last_activity'] = time();
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

function getLoggedInUser() {
    return isset($_SESSION['kullanici_adi']) ? $_SESSION['kullanici_adi'] : null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
?>