document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.getElementById("tbBanners");
    const modal = new bootstrap.Modal(document.getElementById('modalEditarBanner'));
    const formEditar = document.getElementById('formEditarBanner');
    const esc = s => (s || '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));

    // Função de listar banners
    async function listarBanners() {
        try {
            const res = await fetch('../PHP/banners.php?listar=1&format=json', { cache: 'no-store' });
            const d = await res.json();
            if (!d.ok) throw new Error(d.error || "Erro ao listar banners");

            const banners = d.banners || [];
            tbody.innerHTML = banners.length ? banners.map(b => `
                <tr>
                    <td>${b.idBanners}</td>
                    <td>${esc(b.nome)}</td>
                    <td class="text-center">
                        ${b.imagem ? `<img src="data:image/jpeg;base64,${b.imagem}" alt="${esc(b.nome)}" style="width:80px; height:auto; border-radius:8px;">` : "Sem imagem"}
                    </td>
                    <td class="text-center">${b.situacao === "sim" ? '<span class="badge bg-danger">Sim</span>' : '<span class="badge bg-secondary">Não</span>'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-warning" onclick="editarBanner(${b.idBanners})"><i class="bi bi-pencil"></i> Editar</button>
                        <button class="btn btn-sm btn-danger" onclick="excluirBanner(${b.idBanners})"><i class="bi bi-trash"></i> Excluir</button>
                    </td>
                </tr>
            `).join('') : `<tr><td colspan="5" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;

        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
        }
    }

    // Função para abrir o modal e preencher os campos
    window.editarBanner = async function(id) {
        try {
            const res = await fetch(`../PHP/banners.php?listar=1&format=json`);
            const data = await res.json();
            const banner = (data.banners || []).find(b => b.idBanners == id);
            if (!banner) throw new Error("Banner não encontrado");

            document.getElementById('edit-id').value = banner.idBanners;
            document.getElementById('edit-titulo').value = banner.nome;
            document.getElementById('edit-imagem-preview').src = banner.imagem ? `data:image/jpeg;base64,${banner.imagem}` : '';
            if (banner.situacao === "sim") {
                document.getElementById('edit-promocao-sim').checked = true;
            } else {
                document.getElementById('edit-promocao-nao').checked = true;
            }

            modal.show();

        } catch (err) {
            alert("Erro ao carregar dados do banner: " + err.message);
        }
    }

    // Função para excluir banner
    window.excluirBanner = async function(id) {
        if (!confirm("Deseja realmente excluir este banner?")) return;
        try {
            const res = await fetch('../PHP/banners.php', { method: 'POST', body: new URLSearchParams({ acao: 'excluir', id }) });
            const data = await res.json();
            if (data.ok) {
                alert('Banner excluído com sucesso!');
                listarBanners();
            } else {
                throw new Error(data.error || 'Erro desconhecido');
            }
        } catch (err) {
            alert("Falha ao excluir banner: " + err.message);
        }
    }

    // Submissão do formulário de edição
    formEditar.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(formEditar);
        formData.append('acao', 'atualizar');

        try {
            const res = await fetch('../PHP/banners.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.ok !== false) {
                alert("Banner atualizado com sucesso!");
                modal.hide();
                listarBanners();
            } else {
                throw new Error(data.error || "Erro ao atualizar banner");
            }
        } catch (err) {
            alert("Falha ao atualizar banner: " + err.message);
        }
    });

    listarBanners();
});
