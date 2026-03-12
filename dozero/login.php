<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — AEMFPAR</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#1a3c5e 0%,#2d7dd2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
.card{background:#fff;border-radius:16px;padding:48px 40px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.logo{text-align:center;margin-bottom:32px}
.logo h1{color:#1a3c5e;font-size:28px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:10px}
.logo-icon{height:40px;width:auto}
.logo p{color:#6c757d;font-size:14px;margin-top:4px}
.form-group{margin-bottom:20px}
label{display:block;font-size:14px;color:#495057;margin-bottom:6px;font-weight:500}
.input-wrap{position:relative}
input[type=text],input[type=password]{width:100%;padding:12px 44px 12px 16px;border:1.5px solid #dee2e6;border-radius:8px;font-size:15px;color:#212529;outline:none;transition:border-color .2s}
input:focus{border-color:#2d7dd2;box-shadow:0 0 0 3px rgba(45,125,210,.15)}
.input-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#adb5bd;cursor:pointer}
.btn-login{width:100%;padding:14px;background:linear-gradient(135deg,#1a3c5e,#2d7dd2);color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:opacity .2s;margin-top:8px}
.btn-login:hover{opacity:.9}
.erro{background:#fff5f5;color:#c0392b;border:1px solid #f5c6cb;border-radius:6px;padding:10px 14px;font-size:14px;margin-bottom:20px;text-align:center}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1><img src="images/icone_aemf.png" alt="AEMFPAR" class="logo-icon"> AEMFPAR</h1>
        <p>Sistema Financeiro</p>
    </div>
    <?php if (!empty($_GET['erro'])): ?>
    <div class="erro"><i class="fa-solid fa-circle-xmark"></i> Usuário ou senha inválidos.</div>
    <?php endif; ?>
    <form method="POST" action="acesso.php" autocomplete="off">
        <div class="form-group">
            <label for="username">Usuário</label>
            <div class="input-wrap">
                <input type="text" id="username" name="username" placeholder="Digite seu usuário" required autofocus>
                <i class="fa-solid fa-user input-icon"></i>
            </div>
        </div>
        <div class="form-group">
            <label for="password">Senha</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
                <i class="fa-solid fa-eye input-icon" id="toggle-pw"></i>
            </div>
        </div>
        <button type="submit" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Entrar</button>
    </form>
</div>
<script>
document.getElementById('toggle-pw').addEventListener('click', function(){
    var f = document.getElementById('password');
    f.type = f.type === 'password' ? 'text' : 'password';
    this.classList.toggle('fa-eye');
    this.classList.toggle('fa-eye-slash');
});
</script>
</body>
</html>
