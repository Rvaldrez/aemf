<!-- login.php -->
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página de Login</title>
    <!-- Adicionando o Font Awesome para os ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Adicionando a Google Fonts para uma fonte mais moderna -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* Reset de margens e paddings */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Corpo da página */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Container do login */
        .login-container {
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        /* Título */
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* Labels */
        label {
            font-size: 16px;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }

        /* Campos de input */
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            color: #333;
            transition: border-color 0.3s ease;
        }

        /* Efeito no campo de input ao focar */
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: #46889e;
        }

        /* Botão */
        button {
            width: 100%;
            padding: 12px;
            background-color: #46889e;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        /* Efeito hover no botão */
        button:hover {
            background-color: #32697a;
        }

        /* Mensagem de erro */
        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }

        /* Responsividade */
        @media (max-width: 480px) {
            .login-container {
                padding: 15px;
                width: 90%;
            }

            h2 {
                font-size: 20px;
            }

            input[type="text"], input[type="password"], button {
                font-size: 14px;
            }
        }

        /* Ícone de olho */
        .password-container {
            position: relative;
        }

        .password-container i {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <form action="acesso.php" method="POST">
            <label for="username">Usuário:</label>
            <input type="text" id="username" name="username" required placeholder="Digite seu usuário">
            
            <label for="password">Senha:</label>
            <div class="password-container">
                <input type="password" id="password" name="password" required placeholder="Digite sua senha">
                <i class="fas fa-eye" id="toggle-password" onclick="togglePassword()"></i> <!-- Ícone do olho -->
            </div>
            
            <button type="submit">Entrar</button>
        </form>

        <!-- Exibição de mensagens de erro -->
        <?php
        // Verifica se existe o parâmetro de erro na URL e exibe a mensagem
        if (isset($_GET['error']) && $_GET['error'] == 'invalid') {
            echo '<p class="error-message">Usuário ou senha inválidos. Tente novamente.</p>';
        }
        ?>
    </div>

    <script>
        // Função para alternar a visibilidade da senha
        function togglePassword() {
            var passwordField = document.getElementById('password');
            var icon = document.getElementById('toggle-password');
            
            // Alterna entre "password" e "text"
            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>

        
