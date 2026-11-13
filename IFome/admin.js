const produtosIniciais = [
    { id: 1, nome: "Salgado Assado", categoria: "Salgados & Lanches", preco: 5.50, disponivel: true, imagem: null },
    { id: 2, nome: "Coxinha de Frango", categoria: "Salgados & Lanches", preco: 6.00, disponivel: true, imagem: null },
    { id: 3, nome: "Pão de Queijo", categoria: "Salgados & Lanches", preco: 3.50, disponivel: true, imagem: null },
    { id: 4, nome: "Refrigerante Lata", categoria: "Bebidas", preco: 5.00, disponivel: true, imagem: null },
    { id: 5, nome: "Suco Natural (300ml)", categoria: "Bebidas", preco: 4.50, disponivel: true, imagem: null },
    { id: 6, nome: "Café Expresso", categoria: "Bebidas", preco: 2.50, disponivel: true, imagem: null },
    { id: 7, nome: "Brigadeiro", categoria: "Doces", preco: 2.00, disponivel: true, imagem: null },
    { id: 8, nome: "Bolo de Pote", categoria: "Doces", preco: 6.50, disponivel: true, imagem: null }
];

let statusFiltroPedido = 'Todos';

function obterProdutos() {
    let produtos = JSON.parse(localStorage.getItem('produtosLanchonete'));
    if (!produtos) {
        produtos = produtosIniciais;
        localStorage.setItem('produtosLanchonete', JSON.stringify(produtos));
    }
    return produtos;
}

function renderizarAdminProdutos() {
    const produtos = obterProdutos();
    const container = document.getElementById('tabelaProdutos');
    if (!container) return;

    container.innerHTML = '';

    if (produtos.length === 0) {
        container.innerHTML = '<p class="vazio">Nenhum produto cadastrado.</p>';
        return;
    }

    produtos.forEach(p => {
        const item = document.createElement('div');
        item.className = 'item-admin';
        item.innerHTML = `
      <div style="display: flex; align-items: center; gap: 12px;">
        ${p.imagem ? `<img src="${p.imagem}" alt="${p.nome}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">` : ''}
        <div>
          <strong>${p.nome}</strong> (${p.categoria})
          <br>R$ ${p.preco.toFixed(2).replace('.', ',')} - <em>${p.disponivel ? 'Disponível' : 'Indisponível'}</em>
        </div>
      </div>
      <div>
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
    if (!container) return;

    container.innerHTML = '';

    const pedidosFiltrados = statusFiltroPedido === 'Todos' ?
        pedidos :
        pedidos.filter(p => p.status === statusFiltroPedido);

    if (pedidosFiltrados.length === 0) {
        container.innerHTML = '<p class="vazio">Nenhum pedido encontrado neste status.</p>';
        return;
    }

    pedidosFiltrados.forEach(p => {
        const resumoItens = p.itens ? p.itens.map(i => `${i.qtd}x ${i.nome}`).join(', ') : 'Sem itens';
        const card = document.createElement('div');
        card.className = 'item-admin';
        card.innerHTML = `
      <div>
        <strong>#${p.id.toString().slice(-4)} - ${p.cliente || 'Cliente'} (${p.tipo || 'Pedido'} às ${p.data || ''})</strong>
        <p><strong>Itens:</strong> ${resumoItens}</p>
        <p><strong>Horário de Retirada:</strong> ${p.horario || 'N/A'} | <strong>Total:</strong> R$ ${(p.total || 0).toFixed(2).replace('.', ',')}</p>
        <p><strong>Pagamento:</strong> ${p.pagamento || 'A combinar'} | <strong>Status:</strong> <span class="badge">${p.status || 'Pendente'}</span></p>
      </div>
      <div style="display: flex; gap: 8px; flex-direction: column;">
        <button onclick="avancarStatus(${p.id})">Avançar Status</button>
        <button onclick="excluirPedidoAdmin(${p.id})" class="btn-perigo">Excluir Pedido</button>
      </div>
    `;
        container.appendChild(card);
    });
}

function alternarStatus(id) {
    const produtos = obterProdutos();
    const prod = produtos.find(p => p.id == id);
    if (prod) {
        prod.disponivel = !prod.disponivel;
        localStorage.setItem('produtosLanchonete', JSON.stringify(produtos));
        renderizarAdminProdutos();
    }
}

function excluirProduto(id) {
    if (confirm('Deseja realmente excluir este produto?')) {
        let produtos = obterProdutos();
        produtos = produtos.filter(p => p.id != id);
        localStorage.setItem('produtosLanchonete', JSON.stringify(produtos));
        renderizarAdminProdutos();
    }
}

function editarProduto(id) {
    const produtos = obterProdutos();
    const p = produtos.find(prod => prod.id == id);

    if (p) {
        document.getElementById('prodId').value = p.id;
        document.getElementById('prodNome').value = p.nome;
        document.getElementById('prodCategoria').value = p.categoria;
        document.getElementById('prodPreco').value = p.preco;
        document.getElementById('prodDisponivel').checked = p.disponivel;

        document.getElementById('prodImagem').value = '';
        const preview = document.getElementById('prodImagemPreview');
        const container = document.getElementById('previewContainer');
        if (p.imagem) {
            preview.src = p.imagem;
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }

        document.getElementById('tituloModal').textContent = 'Editar Produto';
        document.getElementById('modalProduto').classList.remove('escondido');
    }
}

function converterParaBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
    });
}

function avancarStatus(pedidoId) {
    const pedidos = JSON.parse(localStorage.getItem('pedidosLanchonete')) || [];
    const p = pedidos.find(item => item.id == pedidoId);

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

function excluirPedidoAdmin(pedidoId) {
    if (confirm('Deseja realmente excluir este pedido do sistema?')) {
        let pedidos = JSON.parse(localStorage.getItem('pedidosLanchonete')) || [];
        pedidos = pedidos.filter(p => p.id != pedidoId);
        localStorage.setItem('pedidosLanchonete', JSON.stringify(pedidos));
        renderizarPedidos();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const btnAbaProdutos = document.getElementById('abaProdutos');
    const btnAbaPedidos = document.getElementById('abaPedidos');

    if (btnAbaProdutos && btnAbaPedidos) {
        btnAbaProdutos.addEventListener('click', function() {
            this.classList.add('ativa');
            btnAbaPedidos.classList.remove('ativa');
            document.getElementById('secaoProdutos').classList.remove('escondido');
            document.getElementById('secaoPedidos').classList.add('escondido');
        });

        btnAbaPedidos.addEventListener('click', function() {
            this.classList.add('ativa');
            btnAbaProdutos.classList.remove('ativa');
            document.getElementById('secaoPedidos').classList.remove('escondido');
            document.getElementById('secaoProdutos').classList.add('escondido');
            renderizarPedidos();
        });
    }

    const btnNovo = document.getElementById('btnNovoProduto');
    if (btnNovo) {
        btnNovo.addEventListener('click', () => {
            document.getElementById('formProduto').reset();
            document.getElementById('prodId').value = '';
            document.getElementById('previewContainer').style.display = 'none';
            document.getElementById('tituloModal').textContent = 'Novo Produto';
            document.getElementById('modalProduto').classList.remove('escondido');
        });
    }

    const btnFechar = document.getElementById('btnFecharModal');
    if (btnFechar) {
        btnFechar.addEventListener('click', () => {
            document.getElementById('modalProduto').classList.add('escondido');
        });
    }

    const formProd = document.getElementById('formProduto');
    if (formProd) {
        formProd.addEventListener('submit', async function(e) {
            e.preventDefault();

            const id = document.getElementById('prodId').value;
            const nome = document.getElementById('prodNome').value.trim();
            const categoria = document.getElementById('prodCategoria').value;
            const preco = parseFloat(document.getElementById('prodPreco').value);
            const disponivel = document.getElementById('prodDisponivel').checked;
            const fileInput = document.getElementById('prodImagem');

            let produtos = obterProdutos();
            let imagemBase64 = null;

            if (id) {
                const prodExistente = produtos.find(p => p.id == id);
                if (prodExistente) imagemBase64 = prodExistente.imagem || null;
            }

            if (fileInput.files && fileInput.files[0]) {
                imagemBase64 = await converterParaBase64(fileInput.files[0]);
            }

            if (id) {
                const prod = produtos.find(p => p.id == id);
                if (prod) {
                    prod.nome = nome;
                    prod.categoria = categoria;
                    prod.preco = preco;
                    prod.disponivel = disponivel;
                    prod.imagem = imagemBase64;
                }
            } else {
                produtos.push({
                    id: Date.now(),
                    nome,
                    categoria,
                    preco,
                    disponivel,
                    imagem: imagemBase64
                });
            }

            localStorage.setItem('produtosLanchonete', JSON.stringify(produtos));
            document.getElementById('modalProduto').classList.add('escondido');
            renderizarAdminProdutos();
        });
    }

    const btnSair = document.getElementById('btnSairAdmin');
    if (btnSair) {
        btnSair.addEventListener('click', () => {
            localStorage.removeItem('usuarioLogado');
            window.location.href = 'index.html';
        });
    }

    renderizarAdminProdutos();
    renderizarPedidos();
});