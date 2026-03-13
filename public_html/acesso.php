<!-- acesso.php -->

<?php
// Definir credenciais de acesso
$valid_username = 'antonio';  // Usuário válido
$valid_password = 'moraes123';  // Senha válida

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obter os valores dos campos do formulário
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Verificar se o usuário e senha estão corretos
    if ($username === $valid_username && $password === $valid_password) {
        // Redirecionar para o arquivo PDF
        header('Location: consolidado.pdf');
        exit;
    } else {
        // Redirecionar para a página de login com o erro
        header('Location: login.php?error=invalid');
        exit;
    }
}
?>

