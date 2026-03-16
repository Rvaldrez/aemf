<?php
// dozero/nova_senha.php — Password recovery: set new password via token

if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to dashboard
if (!empty($_SESSION['logado'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

$token    = trim($_GET['token'] ?? '');
$mensagem = '';
$tipo     = '';   // 'sucesso' | 'erro' | 'invalido'
$tokenOk  = false;
$usuario  = null;

// Validate token
if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
    $tipo = 'invalido';
} else {
    try {
        $db   = getDB();
        $stmt = $db->prepare("
            SELECT pr.id AS reset_id, pr.usuario_id, u.username
            FROM password_resets pr
            JOIN usuarios u ON u.id = pr.usuario_id
            WHERE pr.token = :tok AND pr.usado = 0 AND pr.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([':tok' => $token]);
        $usuario = $stmt->fetch();
        if ($usuario) {
            $tokenOk = true;
        } else {
            $tipo = 'invalido';
        }
    } catch (Throwable $e) {
        error_log('nova_senha.php error: ' . $e->getMessage());
        $tipo = 'invalido';
    }
}

// Handle form submission
if ($tokenOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova   = $_POST['nova_senha']      ?? '';
    $conf   = $_POST['conf_senha']      ?? '';
    $tok    = trim($_POST['token']      ?? '');

    if ($tok === '' || !hash_equals($token, $tok)) {
        $mensagem = 'Token inválido.';
        $tipo     = 'erro';
    } elseif (strlen($nova) < 8) {
        $mensagem = 'A senha deve ter pelo menos 8 caracteres.';
        $tipo     = 'erro';
    } elseif ($nova !== $conf) {
        $mensagem = 'As senhas não conferem.';
        $tipo     = 'erro';
    } else {
        try {
            $db   = getDB();
            $hash = password_hash($nova, PASSWORD_BCRYPT);

            $db->prepare("UPDATE usuarios SET password = :p WHERE id = :id")
               ->execute([':p' => $hash, ':id' => $usuario['usuario_id']]);

            $db->prepare("UPDATE password_resets SET usado = 1 WHERE id = :id")
               ->execute([':id' => $usuario['reset_id']]);

            $mensagem = 'Senha alterada com sucesso! Você já pode fazer login.';
            $tipo     = 'sucesso';
            $tokenOk  = false; // hide the form
        } catch (Throwable $e) {
            error_log('nova_senha.php error: ' . $e->getMessage());
            $mensagem = 'Ocorreu um erro ao salvar a senha. Tente novamente.';
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
<title>Nova Senha — AEMFPAR</title>
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
input[type=password],input[type=text]{width:100%;padding:12px 44px 12px 16px;border:1.5px solid #dee2e6;border-radius:8px;font-size:15px;color:#212529;outline:none;transition:border-color .2s}
input:focus{border-color:#2d7dd2;box-shadow:0 0 0 3px rgba(45,125,210,.15)}
.input-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#adb5bd;cursor:pointer}
.btn-salvar{width:100%;padding:14px;background:linear-gradient(135deg,#1a3c5e,#2d7dd2);color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:opacity .2s;margin-top:4px}
.btn-salvar:hover{opacity:.9}
.msg-sucesso{background:#f0fff4;color:#1a7a42;border:1px solid #b7e4c7;border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:20px;line-height:1.5}
.msg-erro{background:#fff5f5;color:#c0392b;border:1px solid #f5c6cb;border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:20px}
.msg-invalido{background:#fff8e1;color:#856404;border:1px solid #ffc107;border-radius:8px;padding:12px 16px;font-size:14px;margin-bottom:20px;line-height:1.5}
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
    <p class="titulo"><i class="fa-solid fa-key" style="color:#2d7dd2;margin-right:6px"></i>Nova Senha</p>

    <?php if ($tipo === 'invalido'): ?>
        <div class="msg-invalido">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Este link é inválido ou expirou. Solicite um <a href="recuperar_senha.php" style="color:#856404;font-weight:600">novo link de recuperação</a>.
        </div>
    <?php elseif ($tipo === 'sucesso'): ?>
        <div class="msg-sucesso"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($mensagem) ?></div>
    <?php elseif ($tipo === 'erro'): ?>
        <div class="msg-erro"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <?php if ($tokenOk): ?>
    <p class="subtitulo">Olá, <strong><?= htmlspecialchars($usuario['username']) ?></strong>. Defina sua nova senha abaixo.</p>
    <form method="POST" action="nova_senha.php?token=<?= htmlspecialchars(urlencode($token)) ?>" autocomplete="off">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="form-group">
            <label for="nova_senha">Nova senha</label>
            <div class="input-wrap">
                <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo 8 caracteres" required autofocus minlength="8">
                <i class="fa-solid fa-eye input-icon" id="toggle-pw1"></i>
            </div>
        </div>
        <div class="form-group">
            <label for="conf_senha">Confirmar senha</label>
            <div class="input-wrap">
                <input type="password" id="conf_senha" name="conf_senha" placeholder="Repita a nova senha" required minlength="8">
                <i class="fa-solid fa-eye input-icon" id="toggle-pw2"></i>
            </div>
        </div>
        <button type="submit" class="btn-salvar"><i class="fa-solid fa-floppy-disk"></i> Salvar nova senha</button>
    </form>
    <script>
    function togglePw(toggleId, fieldId) {
        document.getElementById(toggleId).addEventListener('click', function(){
            var f = document.getElementById(fieldId);
            f.type = f.type === 'password' ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
    togglePw('toggle-pw1', 'nova_senha');
    togglePw('toggle-pw2', 'conf_senha');
    </script>
    <?php endif; ?>

    <div class="voltar">
        <a href="login.php"><i class="fa-solid fa-arrow-left" style="margin-right:4px"></i>Voltar ao login</a>
    </div>
</div>
</body>
</html>
