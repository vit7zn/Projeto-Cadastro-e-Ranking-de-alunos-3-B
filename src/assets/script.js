// ══════════════════════════════════════════════════════════════
//  script.js — EEEP Manoel Mano (versão limpa, sem conflitos)
// ══════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

    // ── Formulário de cadastro de aluno (cadastro.html) ──
    const studentForm = document.querySelector('.student-form');
    if (studentForm) {
        studentForm.addEventListener('submit', function() {
            const bairro      = (document.getElementById('bairro')?.value || '').toLowerCase();
            const optouCota   = document.getElementById('optou_cota_local')?.value;
            const procedencia = document.getElementById('procedencia')?.value;
            const pcd         = document.getElementById('pcd')?.value;

            let categoria = '';
            if (pcd === 'sim') {
                categoria = 'Cota PCD';
            } else if (procedencia === 'publica' && optouCota === 'sim' &&
                       (bairro.includes('venancio') || bairro.includes('venâncio'))) {
                categoria = 'Cota Local (Venâncios)';
            } else if (procedencia === 'privada') {
                categoria = 'Ampla Concorrência Privado';
            } else {
                categoria = 'Ampla Concorrência Pública';
            }

            const catInput = document.getElementById('categoria_ranking');
            if (catInput) catInput.value = categoria;

            // Cálculo da média
            const camposNotas = document.querySelectorAll('.input-nota');
            let soma = 0, qtd = 0;
            camposNotas.forEach(inp => {
                const v = parseFloat(inp.value);
                if (!isNaN(v) && inp.value.trim() !== '') { soma += v; qtd++; }
            });
            const mediaFinal = qtd > 0 ? (soma / qtd).toFixed(5) : '0.00000';
            const mediaInput = document.getElementById('media_final');
            if (mediaInput) mediaInput.value = mediaFinal;

            console.log('Categoria:', categoria, '| Média:', mediaFinal);
        });
    }

    // ── Botão de logout (se existir) ──
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => { window.location.href = 'index.HTML'; });
    }

});
