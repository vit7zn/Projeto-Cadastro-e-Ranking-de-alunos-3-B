// ... (dentro do seu login.php, após receber $email e $senha) ...

$sql = "SELECT nome, senha FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
    
    // O segredo está aqui: password_verify compara o texto com o hash
    if (password_verify($senha, $usuario['senha'])) {
        echo "Bem-vindo, " . $usuario['nome'] . "!";
        // header("Location: dashboard.php");
    } else {
        echo "Senha incorreta.";
    }
} else {
    echo "E-mail não encontrado.";
}