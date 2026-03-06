<?php
// dozero/includes/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['logado'])) {
        header('Location: login.php');
        exit;
    }
}
