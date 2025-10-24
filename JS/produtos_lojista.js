document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.querySelector("#produtos-tabela tbody");
  const urlListar = "../php/cadastrar_produto.php?listar=1&format=json";

  const esc = s => (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const ph = n => 'data:image/svg+xml;base64,' + btoa(
    `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60">
      <rect width="100%" height="100%" fill="#eee"/>
      <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
            font-family="sans-serif" font-size="12" fill="#999">
        ${(n||'?').slice(0,2).toUpperCase()}
      </text>
    </svg>`
  );

  const renderRow = p => `
    <tr data-id="${p.idProduto}">
      <td>${Number(p.idProduto)||''}</td>
      <td><img src="${ph(p.nome)}" alt="${esc(p.nome)}" style="width:60px;height:60px;object-fit:cover;border-radius:8px"></td>
      <td>${esc(p.nome)}</td>
      <td>${esc(p.descricao)}</td>
      <td class="text-end">${Number(p.quantidade)||0}</td>
      <td class="text-end">R$ ${Number(p.preco).toFixed(2).replace('.',',')}</td>
      <td class="text-center">${p.situacao=="1"?'<span class="badge bg-success">Ativo</span>':'<span class="badge bg-secondary">Inativo</span>'}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-warning btn-editar" data-id="${p.idProduto}">Editar</button>
        <button class="btn btn-sm btn-danger btn-excluir" data-id="${p.idProduto}">Excluir</button>
      </td>
    </tr>
  `;

  const carregarTabela = () => {
    fetch(urlListar, { cache:'no-store' })
      .then(r => r.json())
      .then(d => {
        tbody.innerHTML = d.produtos?.length ? d.produtos.map(renderRow).join('')
          : `<tr><td colspan="8" class="text-center text-muted">Nenhum produto cadastrado.</td></tr>`;
      });
  };

  carregarTabela();

  // Modal de edição
  const modal = new bootstrap.Modal(document.getElementById('editarProdutoModal'));
  const formEditar = document.getElementById('formEditarProduto');

  tbody.addEventListener('click', e => {
    const tr = e.target.closest('tr');
    if(!tr) return;
    const id = tr.dataset.id;

    // Editar
    if(e.target.classList.contains('btn-editar')) {
      document.getElementById('editarIdProduto').value = id;
      document.getElementById('editarNomeProduto').value = tr.children[2].innerText;
      document.getElementById('editarDescricao').value = tr.children[3].innerText;
      document.getElementById('editarQuantidade').value = tr.children[4].innerText;
      document.getElementById('editarPreco').value = tr.children[5].innerText.replace('R$ ','').replace(',','.');
      const situacao = tr.children[6].innerText.includes('Ativo') ? '1' : '0';
      document.getElementById('editarAtivo').checked = situacao==='1';
      document.getElementById('editarInativo').checked = situacao==='0';
      modal.show();
    }

    // Excluir
    if(e.target.classList.contains('btn-excluir')) {
      if(confirm('Deseja realmente excluir este produto?')) {
        fetch('../PHP/cadastrar_produto.php', {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:`acao=excluir&id=${id}`
        }).then(r=>r.json()).then(res=>{
          if(res.ok) {
            tr.remove();
            alert('Produto excluído com sucesso!');
          } else {
            alert('Erro: ' + res.error);
          }
        });
      }
    }
  });

  // Salvar edição
  formEditar.addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(formEditar);
    fd.append('acao','atualizar');

    fetch('../PHP/cadastrar_produto.php', { method:'POST', body:fd })
      .then(r=>r.json())
      .then(res=>{
        if(res.ok){
          modal.hide();
          carregarTabela();
          alert('Produto atualizado com sucesso!');
        } else {
          alert('Erro: ' + (res.error||'Não foi possível atualizar'));
        }
      });
  });
});
