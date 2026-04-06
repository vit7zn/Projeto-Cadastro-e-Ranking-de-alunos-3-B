<?php
// 1. Ativar exibição de erros para descobrir problemas (Debug)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Configurações de Conexão
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "sistema_login"; 

// Criar a conexão
$conn = new mysqli($host, $user, $pass, $dbname);

// Verificar se a conexão falhou
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// 3. Verificar se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Capturar os dados do formulário
    // O operador ?? '' evita erros se o campo estiver vazio
    $nome           = $_POST['nome'] ?? '';
    $email          = $_POST['email'] ?? '';
    $senha          = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    // 4. Validações básicas de segurança
    if (empty($nome) || empty($email) || empty($senha)) {
        die("Por favor, preencha todos os campos.");
    }

    if ($senha !== $confirma_senha) {
        die("As senhas não coincidem! Volte e tente novamente.");
    }

    // 5. Criptografar a senha (Hash de 60+ caracteres)
    $senha_segura = password_hash($senha, PASSWORD_DEFAULT);

    // 6. Preparar o comando SQL
    // O ID não entra aqui porque é AUTO_INCREMENT
    $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    // Verificar se houve erro na preparação (Geralmente nome de tabela ou coluna errado)
    if (!$stmt) {
        die("Erro na preparação do SQL: " . $conn->error);
    }

    // 7. Vincular os parâmetros ("sss" significa 3 strings)
    // A ordem deve ser EXATAMENTE: nome, email, senha
    $stmt->bind_param("sss", $nome, $email, $senha_segura);

    // 8. Executar e dar o feedback
    if ($stmt->execute()) {
        echo "<script>
                alert('Usuário $nome cadastrado com sucesso!');
                window.location.href='index.html';
              </script>";
    } else {
        echo "Erro ao salvar no banco de dados: " . $stmt->error;
    }

    // Fechar a declaração e a conexão
    $stmt->close();
}

$conn->close();
?>