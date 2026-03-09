<?php
// dozero/acesso.php — autenticação via banco de dados

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/database.php';

    $username = trim($_POST['username'] ?? '');
    $senha    = $_POST['password'] ?? '';

    if ($username !== '' && $senha !== '') {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, password, role FROM usuarios WHERE username = :u AND ativo = 1 LIMIT 1");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['logado']  = true;
                $_SESSION['usuario'] = $username;
                $_SESSION['role']    = $user['role'];
                header('Location: index.php');
                exit;
            }
        } catch (Throwable $e) {
            error_log('acesso.php error: ' . $e->getMessage());
        }
    }

    header('Location: login.php?erro=1');
    exit;
}

header('Location: login.php');
exit;
