<?php
// dozero/recuperar_senha.php — Password recovery: request reset link

if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to dashboard
if (!empty($_SESSION['logado'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$mensagem = '';
$tipo     = '';   // 'sucesso' | 'erro'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um endereço de e-mail válido.';
        $tipo     = 'erro';
    } else {
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, username FROM usuarios WHERE LOWER(email) = :e AND ativo = 1 LIMIT 1");
            $stmt->execute([':e' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Invalidate previous unused tokens for this user
                $db->prepare("UPDATE password_resets SET usado = 1 WHERE usuario_id = :uid AND usado = 0")
                   ->execute([':uid' => $user['id']]);

                // Generate secure token
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

                $db->prepare("INSERT INTO password_resets (usuario_id, token, expires_at) VALUES (:uid, :tok, :exp)")
                   ->execute([':uid' => $user['id'], ':tok' => $token, ':exp' => $expires]);

                // Build reset URL
                $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $path      = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                $resetUrl  = $protocol . '://' . $host . $path . '/nova_senha.php?token=' . $token;

                // Send e-mail
                $subject = 'AEMFPAR – Redefinição de senha';
                $body    = "Olá, {$user['username']}!\n\n"
                         . "Recebemos uma solicitação para redefinir a senha da sua conta.\n"
                         . "Clique no link abaixo (válido por 1 hora):\n\n"
                         . $resetUrl . "\n\n"
                         . "Se você não fez essa solicitação, ignore este e-mail.\n\n"
                         . "— AEMFPAR Sistema Financeiro";

                $headers  = "From: noreply@" . ($_SERVER['SERVER_NAME'] ?? 'aemfpar.com.br') . "\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                $mailOk = @mail($email, $subject, $body, $headers);
                error_log('recuperar_senha.php: mail() to ' . $email . ' returned ' . ($mailOk ? 'true' : 'false'));
            }

            // Always show the same message to prevent user enumeration
            $mensagem = 'Se o e-mail informado estiver cadastrado, você receberá um link para redefinir sua senha em breve.';
            $tipo     = 'sucesso';

        } catch (Throwable $e) {
            error_log('recuperar_senha.php error: ' . $e->getMessage());
            $mensagem = 'Ocorreu um erro. Tente novamente.';
            $tipo     = 'erro';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar Senha — AEMFPAR</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#1a3c5e 0%,#2d7dd2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
.card{background:#fff;border-radius:16px;padding:48px 40px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.logo{text-align:center;margin-bottom:28px}
.logo h1{color:#1a3c5e;font-size:24px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:10px}
.logo-icon{height:28px;width:28px;min-width:28px;min-height:28px;object-fit:contain;display:block;flex-shrink:0}
.titulo{font-size:18px;font-weight:700;color:#1a3c5e;margin-bottom:8px}
.subtitulo{font-size:14px;color:#6c757d;margin-bottom:24px;line-height:1.5}
.form-group{margin-bottom:20px}
label{display:block;font-size:14px;color:#495057;margin-bottom:6px;font-weight:500}
.input-wrap{position:relative}
input[type=email]{width:100%;padding:12px 44px 12px 16px;border:1.5px solid #dee2e6;border-radius:8px;font-size:15px;color:#212529;outline:none;transition:border-color .2s}
input:focus{border-color:#2d7dd2;box-shadow:0 0 0 3px rgba(45,125,210,.15)}
.input-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#adb5bd}
.btn-enviar{width:100%;padding:14px;background:linear-gradient(135deg,#1a3c5e,#2d7dd2);color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:opacity .2s;margin-top:4px}
.btn-enviar:hover{opacity:.9}
.msg-sucesso{background:#f0fff4;color:#1a7a42;border:1px solid #b7e4c7;border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:20px;line-height:1.5}
.msg-erro{background:#fff5f5;color:#c0392b;border:1px solid #f5c6cb;border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:20px}
.voltar{text-align:center;margin-top:18px;font-size:13px}
.voltar a{color:#2d7dd2;text-decoration:none;font-weight:500}
@media(max-width:480px){
    .card{padding:32px 20px;border-radius:12px}
    .logo h1{font-size:20px}
}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1><img src="images/azul_aemf.svg" alt="AEMFPAR" class="logo-icon"> AEMFPAR</h1>
    </div>
    <p class="titulo"><i class="fa-solid fa-lock-open" style="color:#2d7dd2;margin-right:6px"></i>Recuperar Senha</p>
    <p class="subtitulo">Informe o e-mail cadastrado na sua conta. Você receberá um link para criar uma nova senha.</p>

    <?php if ($mensagem !== ''): ?>
        <?php if ($tipo === 'sucesso'): ?>
            <div class="msg-sucesso"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($mensagem) ?></div>
        <?php else: ?>
            <div class="msg-erro"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($tipo !== 'sucesso'): ?>
    <form method="POST" action="recuperar_senha.php" autocomplete="off">
        <div class="form-group">
            <label for="email">E-mail</label>
            <div class="input-wrap">
                <input type="email" id="email" name="email" placeholder="seu@email.com" required autofocus
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <i class="fa-solid fa-envelope input-icon"></i>
            </div>
        </div>
        <button type="submit" class="btn-enviar"><i class="fa-solid fa-paper-plane"></i> Enviar link</button>
    </form>
    <?php endif; ?>

    <div class="voltar">
        <a href="login.php"><i class="fa-solid fa-arrow-left" style="margin-right:4px"></i>Voltar ao login</a>
    </div>
</div>
</body>
</html>
