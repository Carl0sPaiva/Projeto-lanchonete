
function obterUsuarios() {
    let usuarios = JSON.parse(localStorage.getItem('usuariosLanchonete'));
    if (!usuarios) {
        usuarios = [
            { usuario: 'aluno', senha: '1234', nome: 'Aluno Teste', tipo: 'aluno' },
            { usuario: 'admin', senha: 'admin123', nome: 'Administrador', tipo: 'admin' }
        ];
        localStorage.setItem('usuariosLanchonete', JSON.stringify(usuarios));
    }
    return usuarios;
}


const formLogin = document.getElementById('formLogin');
const formCadastro = document.getElementById('formCadastro');
const textoAlternar = document.getElementById('textoAlternar');
const tituloTela = document.getElementById('tituloTela');

let modoCadastro = false;


document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'linkCadastro') {
        e.preventDefault();
        modoCadastro = !modoCadastro;

        if (modoCadastro) {
            formLogin.classList.add('escondido');
            formCadastro.classList.remove('escondido');
            tituloTela.textContent = 'Criar Conta';
            textoAlternar.innerHTML = 'Já possui uma conta? <a href="#" id="linkCadastro">Fazer Login</a>';
        } else {
            formCadastro.classList.add('escondido');
            formLogin.classList.remove('escondido');
            tituloTela.textContent = 'Lanchonete IFTO';
            textoAlternar.innerHTML = 'Não tem uma conta? <a href="#" id="linkCadastro">Criar conta</a>';
        }
    }
});

formLogin.addEventListener('submit', function(e) {
    e.preventDefault();

    const usuarioInput = document.getElementById('usuario').value.trim();
    const senhaInput = document.getElementById('senha').value.trim();

    const usuarios = obterUsuarios();
    const contaEncontrada = usuarios.find(u => u.usuario === usuarioInput && u.senha === senhaInput);

    if (contaEncontrada) {
        localStorage.setItem('usuarioLogado', JSON.stringify(contaEncontrada));
        if (contaEncontrada.tipo === 'admin') {
            window.location.href = 'admin.html';
        } else {
            window.location.href = 'aluno.html';
        }
    } else {
        alert('Usuário ou senha incorretos!');
    }
});


formCadastro.addEventListener('submit', function(e) {
    e.preventDefault();

    const nome = document.getElementById('novoNome').value.trim();
    const usuario = document.getElementById('novoUsuario').value.trim();
    const senha = document.getElementById('novaSenha').value.trim();
    const tipo = document.getElementById('tipoConta').value;

    let usuarios = obterUsuarios();

   
    if (usuarios.some(u => u.usuario.toLowerCase() === usuario.toLowerCase())) {
        alert('Este nome de usuário já está cadastrado. Por favor, escolha outro!');
        return;
    }

    
    usuarios.push({ nome, usuario, senha, tipo });
    localStorage.setItem('usuariosLanchonete', JSON.stringify(usuarios));

    alert('Conta criada com sucesso! Você pode realizar o login agora.');
    location.reload();
});