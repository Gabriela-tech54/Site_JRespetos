// Função de listar banners e promoções em tabela
function listarBanners(tabelaBN) {
    document.addEventListener('DOMContentLoaded', () => {
        const tbody = document.getElementById(tabelaBN);
        const url = '../PHP/banners.php?listar=1&format=json';

        const esc = s => (s || '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));

        const row = b => `
            <tr>
                <td>${Number(b.idBanners) || ''}</td>
                <td>${esc(b.titulo || '-')}</td>
                <td class="text-center">
                    <img src="../IMG/${esc(b.imagem)}" alt="${esc(b.titulo)}" 
                         style="width:80px; height:auto; border-radius:8px;">
                </td>
                <td class="text-center">
                    ${b.situacao === 1 
                        ? '<span class="badge bg-danger">Sim</span>' 
                        : '<span class="badge bg-secondary">Não</span>'}
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-warning" data-id="${b.idBanners}">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                    <button class="btn btn-sm btn-danger" data-id="${b.idBanners}">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
                </td>
            </tr>
        `;

        fetch(url, { cache: 'no-store' })
            .then(r => r.json())
            .then(d => {
                if (!d.ok) throw new Error(d.error || 'Erro ao listar banners');
                const banners = d.banners || [];
                tbody.innerHTML = banners.length
                    ? banners.map(row).join('')
                    : `<tr><td colspan="5" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">
                    Falha ao carregar: ${esc(err.message)}
                </td></tr>`;
            });
    });
}

// Chama a função para listar os banners
listarBanners("tbBanerrs");
