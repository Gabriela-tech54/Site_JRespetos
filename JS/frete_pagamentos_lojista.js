document.addEventListener('DOMContentLoaded', () => {
  const esc = s => (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const moeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

  // ================= LISTAR FORMAS DE PAGAMENTO =================
  function listarFormasPagamento() {
    const tbody = document.getElementById("tbPagamentos");
    fetch('../PHP/cadastro_forma_pagamento.php?listar=1&format=json', { cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || 'Erro ao listar formas de pagamento');
        const arr = d.Formas_pagamento || [];
        tbody.innerHTML = arr.length
          ? arr.map(f => `
            <tr>
              <td>${Number(f.id)}</td>
              <td>${esc(f.nome)}</td>
              <td class="text-end">
                <button class="btn btn-sm btn-warning" onclick="abrirModalPagamento(${f.id}, '${esc(f.nome)}')">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="excluirPagamento(${f.id})">Excluir</button>
              </td>
            </tr>`).join('')
          : `<tr><td colspan="3">Nenhuma forma de pagamento cadastrada.</td></tr>`;
      })
      .catch(err => { tbody.innerHTML = `<tr><td colspan="3">Falha ao carregar: ${esc(err.message)}</td></tr>`; });
  }

  // ================= LISTAR FRETES =================
  function listarFretes() {
    const tbody = document.getElementById("tbFretes");
    fetch('../PHP/cadastro_frete.php?listar=1&format=json', { cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || 'Erro ao listar fretes');
        const arr = d.fretes || [];
        tbody.innerHTML = arr.length
          ? arr.map(f => `
            <tr>
              <td>${Number(f.id)}</td>
              <td>${esc(f.bairro)}</td>
              <td>${esc(f.transportadora || '-')}</td>
              <td class="text-end">${moeda.format(parseFloat(f.valor ?? 0))}</td>
              <td class="text-end">
                <button class="btn btn-sm btn-warning" onclick="abrirModalFrete(${f.id}, '${esc(f.bairro)}', ${f.valor}, '${esc(f.transportadora||"")}')">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="excluirFrete(${f.id})">Excluir</button>
              </td>
            </tr>`).join('')
          : `<tr><td colspan="5" class="text-center text-muted">Nenhum frete cadastrado.</td></tr>`;
      })
      .catch(err => { tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`; });
  }

  listarFormasPagamento();
  listarFretes();

  // ================= MODAL PAGAMENTO =================
  window.abrirModalPagamento = (id, nome) => {
    document.getElementById("editIdPagamento").value = id;
    document.getElementById("editNomePagamento").value = nome;
    new bootstrap.Modal(document.getElementById('modalEditarPagamento')).show();
  };

  // ================= MODAL FRETE =================
  window.abrirModalFrete = (id, bairro, valor, transportadora) => {
    document.getElementById("editIdFrete").value = id;
    document.getElementById("editBairro").value = bairro;
    document.getElementById("editValor").value = valor;
    document.getElementById("editTransportadora").value = transportadora;
    new bootstrap.Modal(document.getElementById('modalEditarFrete')).show();
  };

  // ================= EDITAR PAGAMENTO =================
  document.getElementById("formEditarPagamento").addEventListener("submit", e => {
    e.preventDefault();
    const id = document.getElementById("editIdPagamento").value;
    const nome = document.getElementById("editNomePagamento").value;
    fetch('../PHP/cadastro_forma_pagamento.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `id=${id}&nomepagamento=${encodeURIComponent(nome)}&acao=editar`
    })
    .then(r => r.json())
    .then(d => {
      if(d.ok){
        listarFormasPagamento();
        bootstrap.Modal.getInstance(document.getElementById('modalEditarPagamento')).hide();
      } else alert(d.error || "Erro ao editar pagamento");
    });
  });

  // ================= EDITAR FRETE =================
  document.getElementById("formEditarFrete").addEventListener("submit", e => {
    e.preventDefault();
    const id = document.getElementById("editIdFrete").value;
    const bairro = document.getElementById("editBairro").value;
    const valor = document.getElementById("editValor").value;
    const transportadora = document.getElementById("editTransportadora").value;
    fetch('../PHP/cadastro_frete.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `id=${id}&bairro=${encodeURIComponent(bairro)}&valor=${valor}&transportadora=${encodeURIComponent(transportadora)}&acao=editar`
    })
    .then(r => r.json())
    .then(d => {
      if(d.ok){
        listarFretes();
        bootstrap.Modal.getInstance(document.getElementById('modalEditarFrete')).hide();
      } else alert(d.error || "Erro ao editar frete");
    });
  });

  // ================= EXCLUIR PAGAMENTO =================
  window.excluirPagamento = id => {
    if(!confirm("Deseja realmente excluir esta forma de pagamento?")) return;
    fetch('../PHP/cadastro_forma_pagamento.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `id=${id}&acao=excluir`
    })
    .then(r => r.json())
    .then(d => { if(d.ok) listarFormasPagamento(); else alert(d.error || "Erro ao excluir pagamento"); });
  };

  // ================= EXCLUIR FRETE =================
  window.excluirFrete = id => {
    if(!confirm("Deseja realmente excluir este frete?")) return;
    fetch('../PHP/cadastro_frete.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `id=${id}&acao=excluir`
    })
    .then(r => r.json())
    .then(d => { if(d.ok) listarFretes(); else alert(d.error || "Erro ao excluir frete"); });
  };
});
