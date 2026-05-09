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
<<<<<<< HEAD
});
=======

    // Objeto Final guardado na variável
    const fichaAluno = {
        nome: document.getElementsByName('nome_aluno')[0].value,
        media: parseFloat(mediaFinal),
        categoria: categoriaRanking,
        bairro: bairroDigitado
    };

    console.log("Variável pronta para o Banco/Ranking:", fichaAluno);
    alert(`Aluno cadastrado na categoria: ${categoriaRanking}\nMédia Final: ${mediaFinal}`);
});
document.addEventListener('DOMContentLoaded', function() {
    const btnMenu = document.getElementById('btn-menu-hamburguer');
    const sidebar = document.getElementById('sidebar-lateral');
    const overlay = document.getElementById('overlay-menu');

    if (btnMenu && sidebar && overlay) {

        // Abre/fecha o menu lateral
        btnMenu.addEventListener('click', () => {
            const isOpen = sidebar.classList.toggle('active');
            overlay.classList.toggle('active', isOpen);
            btnMenu.classList.toggle('open', isOpen);
            btnMenu.setAttribute('aria-expanded', isOpen);
        });

        // Fecha ao clicar no overlay
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            btnMenu.classList.remove('open');
            btnMenu.setAttribute('aria-expanded', false);
        });

        // Fecha ao pressionar ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                btnMenu.classList.remove('open');
                btnMenu.setAttribute('aria-expanded', false);
            }
        });

        // Marca o link ativo com base na página atual
        const paginaAtual = window.location.pathname.split('/').pop().toLowerCase();
        const links = sidebar.querySelectorAll('a');
        links.forEach(link => {
            const href = link.getAttribute('href').toLowerCase();
            if (href === paginaAtual || (paginaAtual === '' && href === 'index.html')) {
                link.classList.add('active-link');
            }
        });
    }
});
>>>>>>> 5ad12f0179e6cc54cf60c8698ac7edc0551d024e
