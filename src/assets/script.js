document.addEventListener('DOMContentLoaded', () => {
    
    // --- LÓGICA DE ALTERNÂNCIA (LOGIN / CADASTRO) ---
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const linkCadastro = document.getElementById('linkCadastro');
    const linkLogin = document.getElementById('linkLogin');
    
    const welcomeTitle = document.getElementById('welcome-title');
    const welcomeSubtitle = document.getElementById('welcome-subtitle');

    if (linkCadastro && linkLogin) {
        // Mostrar form de Cadastro
        linkCadastro.addEventListener('click', (e) => {
            e.preventDefault();
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
            welcomeTitle.innerText = "Crie sua Conta";
            welcomeSubtitle.innerText = "Preencha os dados abaixo para se cadastrar";
        });

        // Mostrar form de Login
        linkLogin.addEventListener('click', (e) => {
            e.preventDefault();
            registerForm.style.display = 'none';
            loginForm.style.display = 'block';
            welcomeTitle.innerText = "Olá, Seja bem-vindo!";
            welcomeSubtitle.innerText = "Faça o seu login para acessar a sua conta";
        });
    }

    // --- VALIDAÇÃO DO FORMULÁRIO DE CADASTRO ---
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const senha = document.getElementById('senha_cadastro').value;
            const confirmaSenha = document.getElementById('confirma_senha').value;

            if (senha !== confirmaSenha) {
                e.preventDefault(); // Impede o envio do formulário
                alert('As senhas não coincidem! Por favor, verifique.');
                document.getElementById('confirma_senha').focus();
            }
        });
    }
});

document.querySelector('.student-form').addEventListener('submit', function(e) {
    e.preventDefault();

    // Captura o bairro e as opções de cota
    const bairroDigitado = document.getElementById('bairro').value.toLowerCase();
    const selecionouCotaLocal = document.getElementsByName('cota_local')[0].value;
    const procedencia = document.getElementsByName('procedencia')[0].value;
    const ehPCD = document.getElementsByName('pcd')[0].value === 'sim';

    // Regra de negócio: Cota Local só vale se morar no Bairro dos Venâncios
    const temDireitoCotaLocal = (selecionouCotaLocal === 'sim' && bairroDigitado.includes('venancio'));

    // Cálculo da Média
    const camposNotas = document.querySelectorAll('.input-nota');
    let somaTotal = 0;
    let quantidadeNotas = 0;

    camposNotas.forEach(input => {
        if (input.value !== "") {
            somaTotal += parseFloat(input.value);
            quantidadeNotas++;
        }
    });

    const mediaFinal = quantidadeNotas > 0 ? (somaTotal / quantidadeNotas).toFixed(2) : 0;

    // DEFINIÇÃO DA CATEGORIA PARA O RANKING
    let categoriaRanking = "";

    if (ehPCD) {
        categoriaRanking = "Cota PCD";
    } else if (procedencia === 'privada' && temDireitoCotaLocal) {
        categoriaRanking = "Privada - Cota Local (Venâncios)";
    } else if (procedencia === 'privada') {
        categoriaRanking = "Privada - Ampla Concorrência";
    } else {
        categoriaRanking = "Escola Pública";
    }

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
