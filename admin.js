function renderizarAdminProdutos() {
  const produtos = JSON.parse(localStorage.getItem('produtosLanchonete')) || [];
  const container = document.getElementById('tabelaProdutos');
  container.innerHTML = '';

  produtos.forEach(p => {
    const item = document.createElement('div');
    item.className = 'item-admin';
    item.innerHTML = `
      <div>
        <strong>${p.nome}</strong> (${p.categoria})
        <br>R$ ${p.preco.toFixed(2).replace('.', ',')} - <em>${p.disponivel ? 'Disponível' : 'Indisponível'}</em>
      </div>
      <div>s
        <button onclick="editarProduto(${p.id})">Editar</button>
        <button onclick="alternarStatus(${p.id})">${p.disponivel ? 'Desativar' : 'Ativar'}</button>
        <button onclick="excluirProduto(${p.id})" class="btn-perigo">Excluir</button>
      </div>
    `;
    container.appendChild(item);
  });
}

function renderizarPedidos() {
  const pedidos = JSON.parse(localStorage.getItem('pedidosLanchonete')) || [];
  const container = document.getElementById('listaPedidos');
  container.innerHTML = '';

  if (pedidos.length === 0) {
    container.innerHTML = '<p class="vazio">Nenhum pedido registrado no momento.</p>';
    return;
  }

  pedidos.forEach(p => {
    const resumoItens = p.itens.map(i => `${i.qtd}x ${i.nome}`).join(', ');
    const card = document.createElement('div');
    card.className = 'item-admin';
    card.innerHTML = `
      <div>
        <strong>#${p.id.toString().slice(-4)} - ${p.tipo} (${p.data})</strong>
        <p><strong>Itens:</strong> ${resumoItens}</p>
        <p><strong>Horário:</strong> ${p.horario} | <strong>Total:</strong> R$ ${p.total.toFixed(2).replace('.', ',')}</p>
        <p><strong>Status:</strong> <span class="badge">${p.status}</span></p>
      </div>
      <div>
        <button onclick="avancarStatus(${p.id})">Avançar Status</button>
      </div>
    `;
    container.appendChild(card);
  });
}

function alternarStatus(id) {
  const produtos = JSON.parse(localStorage.getItem('produtosLanchonete'));
  const prod = produtos.find(p => p.id === id);
  if (prod) {
    prod.disponivel = !prod.disponivel;
    localStorage.setItem('produtosLanchonete', JSON.stringify(produtos));
    renderizarAdminProdutos();
  }
}

function excluirProduto(id) {
  if (confirm('Deseja realmente excluir este produto?')) {
    let produtos = JSON.parse(localStorage.getItem('produtosLanchonete'));
    produtos = produtos.filter(p => p.id !== id);
    localStorage.setItem('produtosLanchonete', JSON.stringify(produtos));
    renderizarAdminProdutos();
  }
}

function editarProduto(id) {
  const produtos = JSON.parse(localStorage.getItem('produtosLanchonete'));
  const p = produtos.find(prod => prod.id === id);

  if (p) {
    document.getElementById('prodId').value = p.id;
    document.getElementById('prodNome').value = p.nome;
    document.getElementById('prodCategoria').value = p.categoria;
    document.getElementById('prodPreco').value = p.preco;
    document.getElementById('prodDisponivel').checked = p.disponivel;

    document.getElementById('tituloModal').textContent = 'Editar Produto';
    document.getElementById('modalProduto').classList.remove('escondido');
  }
}

function avancarStatus(pedidoId) {
  const pedidos = JSON.parse(localStorage.getItem('pedidosLanchonete')) || [];
  const p = pedidos.find(item => item.id === pedidoId);
  
  if (!p) return;

  const sequencia = ['Pendente', 'Em Preparo', 'Pronto', 'Entregue'];
  const indexAtual = sequencia.indexOf(p.status);

  if (indexAtual < sequencia.length - 1) {
    p.status = sequencia[indexAtual + 1];
  } else {
    p.status = sequencia[0];
  }

  localStorage.setItem('pedidosLanchonete', JSON.stringify(pedidos));
  renderizarPedidos();
}

// Controle de Abas
document.getElementById('abaProdutos').addEventListener('click', function() {
  this.classList.add('ativa');
  document.getElementById('abaPedidos').classList.remove('ativa');
  document.getElementById('secaoProdutos').classList.remove('escondido');
  document.getElementById('secaoPedidos').classList.add('escondido');
});

document.getElementById('abaPedidos').addEventListener('click', function() {
  this.classList.add('ativa');
  document.getElementById('abaProdutos').classList.remove('ativa');
  document.getElementById('secaoPedidos').classList.remove('escondido');
  document.getElementById('secaoProdutos').classList.add('escondido');
  renderizarPedidos();
});

// Modal de Produto
document.getElementById('btnNovoProduto').addEventListener('click', () => {
  document.getElementById('formProduto').reset();
  document.getElementById('prodId').value = '';
  document.getElementById('tituloModal').textContent = 'Novo Produto';
  document.getElementById('modalProduto').classList.remove('escondido');
});

document.getElementById('btnFecharModal').addEventListener('click', () => {
  document.getElementById('modalProduto').classList.add('escondido');
});

document.getElementById('formProduto').addEventListener('submit', function(e) {
  e.preventDefault();

  const id = document.getElementById('prodId').value;
  const nome = document.getElementById('prodNome').value.trim();
  const categoria = document.getElementById('prodCategoria').value;
  const preco = parseFloat(document.getElementById('prodPreco').value);
  const disponivel = document.getElementById('prodDisponivel').checked;

  let produtos = JSON.parse(localStorage.getItem('produtosLanchonete')) || [];

  if (id) {
    const prod = produtos.find(p => p.id === parseInt(id));
    if (prod) {
      prod.nome = nome;
      prod.categoria = categoria;
      prod.preco = preco;
      prod.disponivel = disponivel;
    }
  } else {
    produtos.push({
      id: Date.now(),
      nome,
      categoria,
      preco,
      disponivel
    });
  }

  localStorage.setItem('produtosLanchonete', JSON.stringify(produtos));
  document.getElementById('modalProduto').classList.add('escondido');
  renderizarAdminProdutos();
});

// Logout
document.getElementById('btnSairAdmin').addEventListener('click', () => {
  localStorage.removeItem('usuarioLogado');
  window.location.href = 'index.html';
});

// Inicialização
renderizarAdminProdutos();