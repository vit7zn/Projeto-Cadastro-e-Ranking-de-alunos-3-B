<?php
// ══════════════════════════════════════════════════════════════════
//  salvar_cadastro.php — Salva cadastro do aluno com contato
//  EEEP Manoel Mano
// ══════════════════════════════════════════════════════════════════

$conn = new mysqli("localhost", "root", "", "sistema_login");
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastro.html");
    exit();
}

// ── Dados do aluno ──
$nome        = trim($_POST['nome_aluno']       ?? '');
$curso       = trim($_POST['curso']            ?? '');
$procedencia = trim($_POST['procedencia']      ?? 'publica');
$pcd         = trim($_POST['pcd']              ?? 'nao');
$bairro      = trim($_POST['bairro']           ?? '');
$optouCota   = trim($_POST['optou_cota_local'] ?? 'nao');
$media       = floatval($_POST['media_final']  ?? 0);
$categoria   = trim($_POST['categoria_ranking'] ?? '');

// ── Dados do responsável ──
$nomeResp      = trim($_POST['nome_responsavel']     ?? '');
$emailResp     = trim($_POST['email_responsavel']    ?? '');
$telefoneResp  = trim($_POST['telefone_responsavel'] ?? '');

// Remove formatação do telefone (mantém só dígitos)
$telefoneLimpo = preg_replace('/\D/', '', $telefoneResp);

// ── Fallback da categoria (caso JS não tenha rodado) ──
if (empty($categoria)) {
    if ($pcd === 'sim') {
        $categoria = 'Cota PCD';
    } elseif ($procedencia === 'publica' && $optouCota === 'sim' &&
              (stripos($bairro, 'venancio') !== false || stripos($bairro, 'venâncio') !== false)) {
        $categoria = 'Cota Local (Venâncios)';
    } elseif ($procedencia === 'privada') {
        $categoria = 'Ampla Concorrência Privado';
    } else {
        $categoria = 'Ampla Concorrência Pública';
    }
}

// ── Validação básica ──
if (empty($nome) || empty($curso)) {
    die("Erro: Nome e curso são obrigatórios.");
}

// ── INSERT ──
$sql = "INSERT INTO alunos 
            (nome_completo, curso, bairro, procedencia_escolar, pcd, cota_local,
             categoria_ranking, media_geral,
             nome_responsavel, email_responsavel, telefone_responsavel)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erro ao preparar query: " . $conn->error);
}

$cotaLocalVal = ($optouCota === 'sim') ? 'sim' : 'nao';

$stmt->bind_param(
    "sssssssdsss",
    $nome,
    $curso,
    $bairro,
    $procedencia,
    $pcd,
    $cotaLocalVal,
    $categoria,
    $media,
    $nomeResp,
    $emailResp,
    $telefoneLimpo
);

if ($stmt->execute()) {
    // Redireciona para o ranking do curso cadastrado
    header("Location: ranking.php?curso=" . urlencode($curso) . "&cadastro=ok");
    exit();
} else {
    echo "<p style='color:red; font-family:sans-serif; padding:20px;'>
            ❌ Erro ao cadastrar: " . htmlspecialchars($conn->error) . "
          </p>";
}

$stmt->close();
$conn->close();
