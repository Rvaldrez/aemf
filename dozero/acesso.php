<?php
// dozero/acesso.php — autenticação

if (session_status() === PHP_SESSION_NONE) session_start();

// Senhas armazenadas como hashes bcrypt (use password_hash() para gerar novos hashes)
$usuarios = [
    'antonio' => password_hash('moraes123', PASSWORD_BCRYPT),
    'admin'   => password_hash('admin123',  PASSWORD_BCRYPT),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['username'] ?? '');
    $senha   = $_POST['password'] ?? '';

    if (isset($usuarios[$usuario]) && password_verify($senha, $usuarios[$usuario])) {
        session_regenerate_id(true);
        $_SESSION['logado']  = true;
        $_SESSION['usuario'] = $usuario;
        header('Location: index.php');
    } else {
        header('Location: login.php?erro=1');
    }
    exit;
}

header('Location: login.php');
exit;
