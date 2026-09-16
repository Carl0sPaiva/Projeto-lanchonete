// Produtos padrões caso o localStorage esteja vazio
const produtosIniciais = [
  { id: 1, nome: "Salgado Assado", categoria: "Comida", preco: 5.50, disponivel: true },
  { id: 2, nome: "Coxinha de Frango", categoria: "Comida", preco: 6.00, disponivel: true },
  { id: 3, nome: "Pão de Queijo", categoria: "Comida", preco: 3.50, disponivel: true },
  { id: 4, nome: "Refrigerante Lata", categoria: "Bebida", preco: 5.00, disponivel: true },
  { id: 5, nome: "Suco Natural (300ml)", categoria: "Bebida", preco: 4.50, disponivel: true },
  { id: 6, nome: "Café Expresso", categoria: "Bebida", preco: 2.50, disponivel: true },
  { id: 7, nome: "Brigadeiro", categoria: "Doces", preco: 2.00, disponivel: true },
  { id: 8, nome: "Bolo de Pote", categoria: "Doces", preco: 6.50, disponivel: true }
];

let carrinho = JSON.parse(localStorage.getItem('carrinhoLanchonete')) || [];
let categoriaAtiva = 'Todos';

function carregarProdutos() {
  let produtos = JSON.parse(localStorage.getItem('produtosLanchonete'));
  
  if (!produtos) {
    produtos = produtosIniciais;
    localStorage.setItem('produtosLanchonete', JSON.stringify(produtos));
  }

  const grid = document.getElementById('listaProdutos');
  grid.innerHTML = '';

  const filtrados = categoriaAtiva === 'Todos' 
    ? produtos 
    : produtos.filter(p => p.categoria === categoriaAtiva);

  if (filtrados.length === 0) {
    grid.innerHTML = '<p class="vazio">Nenhum produto encontrado nesta categoria.</p>';
    return;
  }

  filtrados.forEach(prod => {
    const card = document.createElement('div');
    card.className = 'card-produto';
    card.innerHTML = `
      <h3>${prod.nome}</h3>
      <p class="categoria">${prod.categoria}</p>
      <p class="preco">R$ ${prod.preco.toFixed(2).replace('.', ',')}</p>
      ${prod.disponivel 
        ? `<button onclick="adicionarAoCarrinho(${prod.id})" class="btn-add">+ Adicionar</button>` 
        : `<span class="indisponivel">Indisponível</span>`}
    `;
    grid.appendChild(card);
  });
}

function adicionarAoCarrinho(id) {
  const produtos = JSON.parse(localStorage.getItem('produtosLanchonete'));
  const prod = produtos.find(p => p.id === id);

  if (!prod) return;

  const itemExistente = carrinho.find(item => item.id === id);
  if (itemExistente) {
    itemExistente.qtd++;
  } else {
    carrinho.push({ id: prod.id, nome: prod.nome, preco: prod.preco, qtd: 1 });
  }

  atualizarCarrinho();
}

function alterarQtd(id, delta) {
  const item = carrinho.find(i => i.id === id);
  if (!item) return;

  item.qtd += delta;
  if (item.qtd <= 0) {
    carrinho = carrinho.filter(i => i.id !== id);
  }

  atualizarCarrinho();
}

function atualizarCarrinho() {
  localStorage.setItem('carrinhoLanchonete', JSON.stringify(carrinho));
  
  const container = document.getElementById('itensCarrinho');
  const qtdTotalEl = document.getElementById('qtdCarrinho');
  const totalEl = document.getElementById('valorTotal');

  const totalItens = carrinho.reduce((acc, cur) => acc + cur.qtd, 0);
  const valorTotal = carrinho.reduce((acc, cur) => acc + (cur.preco * cur.qtd), 0);

  qtdTotalEl.textContent = totalItens;
  totalEl.textContent = `R$ ${valorTotal.toFixed(2).replace('.', ',')}`;

  container.innerHTML = '';

  if (carrinho.length === 0) {
    container.innerHTML = '<p class="vazio">Seu carrinho está vazio.</p>';
    return;
  }

  carrinho.forEach(item => {
    const div = document.createElement('div');
    div.className = 'item-carrinho';
    div.innerHTML = `
      <div>
        <strong>${item.nome}</strong>
        <br><small>R$ ${item.preco.toFixed(2).replace('.', ',')} un.</small>
      </div>
      <div class="qtd-controles">
        <button onclick="alterarQtd(${item.id}, -1)">-</button>
        <span>${item.qtd}</span>
        <button onclick="alterarQtd(${item.id}, 1)">+</button>
      </div>
    `;
    container.appendChild(div);
  });
}

// Eventos de Filtro
document.querySelectorAll('.btn-filtro').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('ativo'));
    this.classList.add('ativo');
    categoriaAtiva = this.dataset.cat;
    carregarProdutos();
  });
});

// Abrir e fechar carrinho
document.getElementById('btnCarrinho').addEventListener('click', () => {
  document.getElementById('painelCarrinho').classList.remove('escondido');
});

document.getElementById('btnFecharCarrinho').addEventListener('click', () => {
  document.getElementById('painelCarrinho').classList.add('escondido');
});

// Alternar campo de pagamento baseado no tipo de pedido
document.querySelectorAll('input[name="tipoPedido"]').forEach(radio => {
  radio.addEventListener('change', function() {
    const campo = document.getElementById('campoPagamento');
    campo.style.display = this.value === 'Compra' ? 'block' : 'none';
  });
});

// Envio do Pedido
document.getElementById('formCheckout').addEventListener('submit', function(e) {
  e.preventDefault();

  if (carrinho.length === 0) {
    alert('Adicione ao menos um item ao carrinho!');
    return;
  }

  const horario = document.getElementById('horarioRetirada').value;
  const tipo = document.querySelector('input[name="tipoPedido"]:checked').value;
  const pagamento = tipo === 'Compra' ? document.getElementById('formaPagamento').value : 'A combinar';

  const pedidos = JSON.parse(localStorage.getItem('pedidosLanchonete')) || [];
  const novoPedido = {
    id: Date.now(),
    cliente: 'Aluno',
    itens: carrinho,
    total: carrinho.reduce((acc, c) => acc + (c.preco * c.qtd), 0),
    tipo: tipo,
    horario: horario,
    pagamento: pagamento,
    status: 'Pendente',
    data: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
  };

  pedidos.push(novoPedido);
  localStorage.setItem('pedidosLanchonete', JSON.stringify(pedidos));

  carrinho = [];
  atualizarCarrinho();
  
  document.getElementById('painelCarrinho').classList.add('escondido');
  alert('Pedido enviado com sucesso!');
});

// Logout
document.getElementById('btnSair').addEventListener('click', () => {
  localStorage.removeItem('usuarioLogado');
  window.location.href = 'index.html';
});

// Inicialização
carregarProdutos();
atualizarCarrinho();