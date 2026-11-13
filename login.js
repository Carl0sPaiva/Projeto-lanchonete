async function api(acao, dados = {}) {
    const r = await fetch(`api.php?acao=${acao}`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(dados)});
    const j = await r.json(); if(!r.ok) throw new Error(j.erro || 'Não foi possível concluir a operação.'); return j;
}
const formLogin=document.getElementById('formLogin');
const formCadastro=document.getElementById('formCadastro');
const textoAlternar=document.getElementById('textoAlternar');
let modoCadastro=false;
document.addEventListener('click',e=>{
    if(e.target && e.target.id==='linkCadastro'){
        e.preventDefault(); modoCadastro=!modoCadastro;
        formLogin.classList.toggle('escondido',modoCadastro);
        formCadastro.classList.toggle('escondido',!modoCadastro);
        textoAlternar.innerHTML=modoCadastro?'Já possui uma conta? <a href="#" id="linkCadastro">Fazer Login</a>':'Não tem uma conta? <a href="#" id="linkCadastro">Criar conta</a>';
    }
});
formLogin.addEventListener('submit',async e=>{
    e.preventDefault();
    try { const d=await api('login',{usuario:document.getElementById('usuario').value.trim(),senha:document.getElementById('senha').value});
        location.href=d.perfil==='ADMINISTRADOR'?'admin.php':'aluno.php';
    } catch(err){alert(err.message);}
});
formCadastro.addEventListener('submit',async e=>{
    e.preventDefault();
    try { await api('cadastro',{nome:document.getElementById('novoNome').value.trim(),usuario:document.getElementById('novoUsuario').value.trim(),senha:document.getElementById('novaSenha').value});
        alert('Cadastro realizado! Faça login com seu usuário e senha.'); formCadastro.reset(); modoCadastro=false; formCadastro.classList.add('escondido');formLogin.classList.remove('escondido');
        textoAlternar.innerHTML='Não tem uma conta? <a href="#" id="linkCadastro">Criar conta</a>';
    } catch(err){alert(err.message);}
});
