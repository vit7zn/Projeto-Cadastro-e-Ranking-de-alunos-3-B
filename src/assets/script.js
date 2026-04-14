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
