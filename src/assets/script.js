document.addEventListener('DOMContentLoaded', () => {
    const studentForm = document.querySelector('.student-form');

    if (studentForm) {
        studentForm.addEventListener('submit', function(e) {
            // 1. CAPTURA DOS CAMPOS PARA LÓGICA DE COTAS
            const bairro = document.getElementById('bairro').value.toLowerCase();
            const optouCota = document.getElementById('optou_cota_local').value;
            const procedencia = document.getElementById('procedencia').value;
            const pcd = document.getElementById('pcd').value;
            
            let categoria = "";

            // 2. LÓGICA DE DEFINIÇÃO DA CATEGORIA (RANKING)
            if (pcd === 'sim') {
                categoria = "Cota PCD";
            } else if (procedencia === 'publica' && optouCota === 'sim' && (bairro.includes('venancio') || bairro.includes('venâncio'))) {
                categoria = "Cota Local (Venâncios)";
            } else if (procedencia === 'privada') {
                categoria = "Ampla Concorrência Privado";
            } else {
                categoria = "Ampla Concorrência Pública";
            }

            // Atribui a categoria ao campo oculto
            document.getElementById('categoria_ranking').value = categoria;


            // 3. LÓGICA DE CÁLCULO DA MÉDIA
            const camposNotas = document.querySelectorAll('.input-nota');
            let somaTotal = 0;
            let quantidadeNotas = 0;

            camposNotas.forEach(input => {
                if (input.value !== "" && !isNaN(input.value)) {
                    somaTotal += parseFloat(input.value);
                    quantidadeNotas++;
                }
            });

            // Cálculo com 5 casas decimais (padrão de desempate de EEEPs)
            const mediaFinal = quantidadeNotas > 0 ? (somaTotal / quantidadeNotas).toFixed(5) : "0.00000";

            // Atribui a média ao campo oculto
            document.getElementById('media_final').value = mediaFinal;


            // LOG DE VERIFICAÇÃO (Aparece no F12 do navegador)
            console.log("--- Resumo do Cadastro ---");
            console.log("Categoria Definida: " + categoria);
            console.log("Média Final: " + mediaFinal);
            
            // O formulário prossegue para o 'salvar_cadastro.php' automaticamente
        });
    }

    // --- LÓGICA DE INTERAÇÃO DA NAVBAR (OPCIONAL/EXISTENTE NO SEU PROJETO) ---
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            window.location.href = 'index.html';
        });
    }
});