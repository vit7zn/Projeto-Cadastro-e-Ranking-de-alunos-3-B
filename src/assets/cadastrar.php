<?php
// ══════════════════════════════════════════════════════════════
//  cadastrar.php — Cadastro de usuários do sistema
//  Correções: email duplicado, redirects com toast, sem die()
// ══════════════════════════════════════════════════════════════

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "sistema_login";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    header("Location: index.HTML?erro=erro_interno");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.HTML");
    exit();
}

$nome           = trim($_POST['nome']           ?? '');
$email          = trim($_POST['email']          ?? '');
$senha          = $_POST['senha']               ?? '';
$confirma_senha = $_POST['confirma_senha']      ?? '';

// ── Validações básicas ──
if (empty($nome) || empty($email) || empty($senha)) {
    header("Location: index.HTML?erro=campos_vazios");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.HTML?erro=email_invalido");
    exit();
}

if ($senha !== $confirma_senha) {
    header("Location: index.HTML?erro=senhas_nao_coincidem");
    exit();
}

if (strlen($senha) < 6) {
    header("Location: index.HTML?erro=senha_curta");
    exit();
}

// ── Verifica se email já está cadastrado ──
$stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
$stmtCheck->bind_param("s", $email);
$stmtCheck->execute();
$stmtCheck->store_result();

if ($stmtCheck->num_rows > 0) {
    $stmtCheck->close();
    $conn->close();
    header("Location: index.HTML?erro=email_ja_cadastrado");
    exit();
}
$stmtCheck->close();

// ── Insere novo usuário ──
$senha_segura = password_hash($senha, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");

if (!$stmt) {
    header("Location: index.HTML?erro=erro_interno");
    exit();
}

$stmt->bind_param("sss", $nome, $email, $senha_segura);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: index.HTML?msg=cadastro_ok");
    exit();
} else {
    $stmt->close();
    $conn->close();
    header("Location: index.HTML?erro=erro_interno");
    exit();
}
?>
