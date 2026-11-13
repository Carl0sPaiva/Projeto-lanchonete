let produtos=[]; let carrinho=[]; let categoriaAtiva='Todos';
async function api(acao,dados={},metodo='POST'){
 const opt={method:metodo,headers:{'Content-Type':'application/json'}}; if(metodo!=='GET')opt.body=JSON.stringify(dados);
 const r=await fetch(`api.php?acao=${acao}`,opt); const j=await r.json(); if(!r.ok)throw new Error(j.erro||'Erro na operação.');return j;
}
function moeda(v){return Number(v).toFixed(2).replace('.',',');}
async function carregarProdutos(){
 try{produtos=await api('produtos_listar');const grid=document.getElementById('listaProdutos');grid.innerHTML='';
 const filtrados=categoriaAtiva==='Todos'?produtos:produtos.filter(p=>p.categoria===categoriaAtiva);
 if(!filtrados.length){grid.innerHTML='<p class="vazio">Nenhum produto encontrado nesta categoria.</p>';return;}
 filtrados.forEach(p=>{const card=document.createElement('div');card.className='card-produto';card.innerHTML=`<h3>${esc(p.nome)}</h3><p class="categoria">${esc(p.categoria)}</p><p class="preco">R$ ${moeda(p.preco)}</p>${Number(p.quantidade_estoque)>0?`<button class="btn-add" onclick="adicionarAoCarrinho(${p.id})">+ Adicionar</button>`:'<span class="indisponivel">Indisponível</span>'}`;grid.appendChild(card);});
 }catch(e){alert(e.message);}
}
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function adicionarAoCarrinho(id){const p=produtos.find(x=>x.id===id);if(!p)return;const item=carrinho.find(x=>x.id===id);if(item)item.qtd++;else carrinho.push({id:p.id,nome:p.nome,preco:Number(p.preco),qtd:1});atualizarCarrinho();}
function alterarQtd(id,delta){const i=carrinho.find(x=>x.id===id);if(!i)return;i.qtd+=delta;if(i.qtd<=0)carrinho=carrinho.filter(x=>x.id!==id);atualizarCarrinho();}
function atualizarCarrinho(){const container=document.getElementById('itensCarrinho');const qtd=carrinho.reduce((a,x)=>a+x.qtd,0);const total=carrinho.reduce((a,x)=>a+x.preco*x.qtd,0);document.getElementById('qtdCarrinho').textContent=qtd;document.getElementById('valorTotal').textContent=`R$ ${moeda(total)}`;container.innerHTML='';if(!carrinho.length){container.innerHTML='<p class="vazio">Seu carrinho está vazio.</p>';return;}
 carrinho.forEach(i=>{const d=document.createElement('div');d.className='item-carrinho';d.innerHTML=`<div><strong>${esc(i.nome)}</strong><br><small>R$ ${moeda(i.preco)} un.</small></div><div class="qtd-controles"><button onclick="alterarQtd(${i.id},-1)">-</button><span>${i.qtd}</span><button onclick="alterarQtd(${i.id},1)">+</button></div>`;container.appendChild(d);});}
document.querySelectorAll('.btn-filtro').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('.btn-filtro').forEach(x=>x.classList.remove('ativo'));b.classList.add('ativo');categoriaAtiva=b.dataset.cat;carregarProdutos();}));
document.getElementById('btnCarrinho').addEventListener('click',()=>document.getElementById('painelCarrinho').classList.remove('escondido'));
document.getElementById('btnFecharCarrinho').addEventListener('click',()=>document.getElementById('painelCarrinho').classList.add('escondido'));
document.querySelectorAll('input[name="tipoPedido"]').forEach(r=>r.addEventListener('change',function(){document.getElementById('campoPagamento').style.display=this.value==='Compra'?'block':'none';}));
document.getElementById('formCheckout').addEventListener('submit',async e=>{e.preventDefault();if(!carrinho.length){alert('Adicione ao menos um item ao carrinho!');return;}try{const tipo=document.querySelector('input[name="tipoPedido"]:checked').value;await api('reserva_criar',{itens:carrinho.map(i=>({id:i.id,qtd:i.qtd})),tipo,horario:document.getElementById('horarioRetirada').value,pagamento:tipo==='Compra'?document.getElementById('formaPagamento').value:'Dinheiro'});carrinho=[];atualizarCarrinho();document.getElementById('painelCarrinho').classList.add('escondido');alert('Pedido/reserva enviado com sucesso!');await carregarProdutos();}catch(err){alert(err.message);}});
document.getElementById('btnSair').addEventListener('click',async()=>{try{await api('sair');location.href='index.php';}catch(e){alert(e.message);}});
carregarProdutos();atualizarCarrinho();
