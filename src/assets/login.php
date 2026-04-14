<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Configurações de Conexão
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "sistema_login"; 

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// 2. Verificar requisição
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        die("Por favor, preencha todos os campos.");
    }

    // 3. Buscar usuário no banco
    $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Erro SQL: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // 4. Validar e Logar
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        
        if (password_verify($senha, $usuario['senha'])) {
            // Sucesso! Cria uma sessão e redireciona.
            $_SESSION['usuario_logado'] = true;
            $_SESSION['usuario_nome'] = $usuario['nome'];
            
            header("Location: cadastro.html");
            exit();
        } else {
            echo "<script>alert('Senha incorreta.'); window.location.href='index.html';</script>";
        }
    } else {
        echo "<script>alert('E-mail não encontrado.'); window.location.href='index.html';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
