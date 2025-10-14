
function listarProdutos(nometabelaprodutos){

    //espera o html carregar para só buscar a lista de produtos
    document.addEventListener('DOMContentLoaded',()=>{
        // captura o local onde sera listado os dados no html
        const tbody = document.getElementById(nometabelaprodutos);
        //Endpoint que devolve json
         const url = "../php/cadastro_produtos.php?listar=1";
      // --- util 1) esc(): escapa caracteres especiais no texto (evita quebrar o HTML)
   const esc = s => (s||'').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]))



   
 
  // --- util 2) ph(): gera um SVG base64 com as iniciais, usado quando não há imagem
  const ph  = n => 'data:image/svg+xml;base64,' + btoa(
    `<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60">
       <rect width="100%" height="100%" fill="#eee"/>
       <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
             font-family="sans-serif" font-size="12" fill="#999">
         ${(n||'?').slice(0,2).toUpperCase()}
       </text>
     </svg>`
  );

  // configurando a tabela com os dados
 const row = m => `
    <tr>
      <td>
        <img
          src="${m.imagem ? `data:${m.mime||'image/jpeg'};base64,${m.imagem}`
          : ph(m.nome)}"
          alt="${esc(m.nome||'Marca')}"
          style="width:60px;height:60px;object-fit:cover;border-radius:8px">
      </td>
      <td>${esc(m.nome||'-')}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-warning" data-id="${m.idMarcas}">Editar</button>
        <button class="btn btn-sm btn-danger"  data-id="${m.idMarcas}">Excluir</button>
      </td>
    </tr>`;


    // fazer a requisição ao php e preencher a tabela 
    fetch(url,{cache: 'no-store'}) 
    // converter o json e renderiza
    .then(r => r.json())
    //trata o json e renderiza 
    .then(d =>{
        if(!d.ok)throw new Error(d.Error || "Erro ao listar");
        // se houver marcas, monta as linhas; senão , mostra uma mensagem
        tbody.innerHTML = d.produtos?.length
        ? d.produtos.map(row).join('')
        :'<tr><td colspan="3">Nenhuma marca cadastrada.</td></tr>';

    })
    
    // qualquer erro ele executa este código
    .catch(err => {
        tbody.innerHTML= '<tr><td colspan="3">Falha ao carregar: ${esc(err.message)}</td></tr>';

    });


    });

    

}


