function listarProdutos(nometabelaprodutos) {
  // Espera o HTML carregar
  document.addEventListener('DOMContentLoaded', () => {
    // Captura o tbody da tabela onde os produtos serão listados
    const tbody = document.querySelector(`#${nometabelaprodutos} tbody`);
    // Endpoint PHP que retorna os produtos em JSON
    const url = "../php/cadastro_produtos.php?listar=1&format=json";

    // --- Função utilitária: escapa caracteres especiais (previne XSS)
    const esc = s => (s || '').replace(/[&<>"']/g, c => ({
      '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
    }[c]));

    // --- Função utilitária: cria placeholder SVG caso não tenha imagem
    const ph = n => 'data:image/svg+xml;base64,' + btoa(
      `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60">
         <rect width="100%" height="100%" fill="#eee"/>
         <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
               font-family="sans-serif" font-size="12" fill="#999">
           ${(n||'?').slice(0,2).toUpperCase()}
         </text>
       </svg>`
    );

    // --- Função que monta cada linha da tabela
    const row = p => `
      <tr>
        <td>${Number(p.idProduto) || ''}</td>
        <td>
          <img src="${p.imagem ? `data:image/jpeg;base64,${p.imagem}` : ph(p.nome)}"
               alt="${esc(p.nome||'-')}"
               style="width:60px;height:60px;object-fit:cover;border-radius:8px">
        </td>
        <td>${esc(p.descricao||'-')}</td>
        <td class="text-end">${Number(p.quantidade)||0}</td>
        <td class="text-end">R$ ${Number(p.preco).toFixed(2).replace('.',',')}</td>
        <td class="text-center">${p.situacao === '1' ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>'}</td>
        <td>${esc(p.tamanho||'-')}</td>
        <td>${esc(p.cor||'-')}</td>
        <td>${esc(p.codigo||'-')}</td>
      </tr>
    `;

    // --- Requisição ao PHP
    fetch(url, { cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        if(!d.ok) throw new Error(d.error || "Erro ao listar produtos");
        // Preenche a tabela com os produtos ou mensagem caso vazio
        tbody.innerHTML = d.produtos?.length
          ? d.produtos.map(row).join('')
          : `<tr><td colspan="9" class="text-center text-muted">Nenhum produto cadastrado.</td></tr>`;
      })
      .catch(err => {
        // Caso ocorra algum erro na requisição ou parsing JSON
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger">
          Falha ao carregar: ${esc(err.message)}
        </td></tr>`;
      });

  });
}

// Chamada da função: passa o ID da tabela
listarProdutos("produtos-tabela");