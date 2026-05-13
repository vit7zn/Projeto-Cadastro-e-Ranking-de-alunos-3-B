<?php
session_start();

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "sistema_login";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Erro de conexão: " . $conn->connect_error); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        header("Location: index.HTML?erro=campos_vazios");
        exit();
    }

    $sql  = "SELECT id, nome, senha FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        // Suporte a senha em texto puro (legado) e hash bcrypt
        $senhaValida = password_verify($senha, $usuario['senha']) || $senha === $usuario['senha'];

        if ($senhaValida) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome']       = $usuario['nome'];
            session_write_close(); // Garante que a sessão é gravada antes do redirect
            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: index.HTML?erro=senha_incorreta");
            exit();
        }
    } else {
        header("Location: index.HTML?erro=email_nao_encontrado");
        exit();
    }

    $stmt->close();
}

$conn->close();
header("Location: index.HTML");
exit();
?>