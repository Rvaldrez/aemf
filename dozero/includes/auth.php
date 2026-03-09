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

function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: index.php?erro=acesso');
        exit;
    }
}

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}
