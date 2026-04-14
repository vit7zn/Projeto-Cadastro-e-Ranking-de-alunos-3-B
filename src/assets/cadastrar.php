<?php
// 1. Ativar exibição de erros (Debug)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Configurações de Conexão
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "sistema_login"; 

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// 3. Verificar se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
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

    // 5. Criptografar a senha
    $senha_segura = password_hash($senha, PASSWORD_DEFAULT);

    // 6. Preparar o comando SQL
    $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erro na preparação do SQL: " . $conn->error);
    }

    // 7. Vincular os parâmetros e executar
    $stmt->bind_param("sss", $nome, $email, $senha_segura);

    if ($stmt->execute()) {
        echo "<script>
                alert('Usuário $nome cadastrado com sucesso! Faça seu login.');
                window.location.href='index.html';
              </script>";
    } else {
        echo "Erro ao salvar no banco de dados: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
