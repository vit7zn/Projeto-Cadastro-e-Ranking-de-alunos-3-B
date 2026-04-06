document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Pegamos os campos de senha
            const senha = document.getElementById('senha').value;
            const confirmaSenha = document.getElementById('confirma_senha');

            // Verificamos se o campo de confirmação existe (só existe no cadastro)
            if (confirmaSenha) {
                if (senha !== confirmaSenha.value) {
                    // Cancela o envio do formulário
                    e.preventDefault();
                    alert('As senhas não coincidem! Por favor, verifique.');
                    confirmaSenha.focus();
                    return;
                }
            }

            // Se chegou aqui, as senhas coincidem ou é apenas a tela de login
            console.log('Formulário validado, enviando dados...');
        });
    }
});