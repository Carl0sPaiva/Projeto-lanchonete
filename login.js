document.getElementById('formLogin').addEventListener('submit', function (e) {
  e.preventDefault();

  const usuario = document.getElementById('usuario').value.trim();
  const senha = document.getElementById('senha').value.trim();

  if (usuario === 'aluno' && senha === '1234') {
    localStorage.setItem('usuarioLogado', JSON.stringify({ tipo: 'aluno', nome: 'Aluno' }));
    window.location.href = 'aluno.html';
  } else if (usuario === 'admin' && senha === 'admin123') {
    localStorage.setItem('usuarioLogado', JSON.stringify({ tipo: 'admin', nome: 'Administrador' }));
    window.location.href = 'admin.html';
  } else {
    alert('Usuário ou senha incorretos!');
  }
});