<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso - Lanchonete IFTO</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="login-body">
    <div class="login-card">
        <img src="imagens/logo.png" alt="Logo IFTO" class="logo-login">
        

        <form id="formLogin">
            <label for="usuario">Usuário</label>
            <input type="text" id="usuario" placeholder="Digite seu usuário" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" placeholder="Digite sua senha" required>

            <button type="submit" class="btn-principal">Entrar</button>
        </form>

        <form id="formCadastro" class="escondido">
            <label for="novoNome">Nome Completo</label>
            <input type="text" id="novoNome" placeholder="Seu nome completo" required>

            <label for="novoUsuario">Usuário</label>
            <input type="text" id="novoUsuario" placeholder="Crie um nome de usuário" required>

            <label for="novaSenha">Senha</label>
            <input type="password" id="novaSenha" placeholder="Crie uma senha" required>

            <label for="tipoConta">Tipo de Conta</label>
            <select id="tipoConta">
                <option value="aluno">Aluno / Cliente</option>
                
            </select>

            <button type="submit" class="btn-principal">Cadastrar</button>
        </form>

        
        <div class="alternar-area">
            <p id="textoAlternar">Não tem uma conta? <a href="#" id="linkCadastro">Criar conta</a></p>
        </div>

        
    </div>

    <script src="login.js"></script>
</body>

</html>