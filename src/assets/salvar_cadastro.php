<?php
// ══════════════════════════════════════════════════════════════════
//  salvar_cadastro.php — Salva cadastro do aluno com contato
//  EEEP Manoel Mano
// ══════════════════════════════════════════════════════════════════

session_start();

$conn = new mysqli("localhost", "root", "", "sistema_login");
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastro.php");
    exit();
}

// ── Modo de envio ──
// 'salvar'  → salva só os dados, volta para cadastro.php
// 'ranking' → salva com notas e vai para ranking.php
$modoEnvio = trim($_POST['modo_envio'] ?? 'ranking');
$soSalvar  = ($modoEnvio === 'salvar');

// ── Dados do aluno ──
$nome        = trim($_POST['nome_aluno']        ?? '');
$curso       = trim($_POST['curso']             ?? '');
$procedencia = trim($_POST['procedencia']       ?? 'publica');
$pcd         = trim($_POST['pcd']              ?? 'nao');
$bairro      = trim($_POST['bairro']           ?? '');
$optouCota   = trim($_POST['optou_cota_local'] ?? 'nao');

// Notas e categoria: ignoradas no modo "salvar"
$media     = $soSalvar ? 0.0 : floatval($_POST['media_final']      ?? 0);
$categoria = $soSalvar ? ''  : trim($_POST['categoria_ranking']    ?? '');

// ── Dados do responsável ──
$nomeResp     = trim($_POST['nome_responsavel']     ?? '');
$emailResp    = trim($_POST['email_responsavel']    ?? '');
$telefoneResp = trim($_POST['telefone_responsavel'] ?? '');
$telefoneLimpo = preg_replace('/\D/', '', $telefoneResp);

// ── Fallback da categoria (só no modo ranking, caso JS não tenha rodado) ──
if (!$soSalvar && empty($categoria)) {
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
    $novoAlunoId = $conn->insert_id;

    // ── Vincular boletins sem aluno_id que têm o nome do aluno na descrição ──
    // Cobre o caso: boletim salvo antes do cadastro (aluno_id = NULL)
    if ($novoAlunoId && isset($_SESSION['usuario_id'])) {
        $usuarioId = (int) $_SESSION['usuario_id'];
        $stmtVinc = $conn->prepare("
            UPDATE boletins_salvos
            SET aluno_id = ?
            WHERE usuario_id = ?
              AND aluno_id IS NULL
              AND descricao = ?
        ");
        if ($stmtVinc) {
            $stmtVinc->bind_param('iis', $novoAlunoId, $usuarioId, $nome);
            $stmtVinc->execute();
            $stmtVinc->close();
        }
    }

    $stmt->close();
    $conn->close();

    if ($soSalvar) {
        header("Location: cadastro.php?cadastro=ok");
    } else {
        header("Location: ranking.php?curso=" . urlencode($curso) . "&cadastro=ok");
    }
    exit();
} else {
    echo "<p style='color:red; font-family:sans-serif; padding:20px;'>
            ❌ Erro ao cadastrar: " . htmlspecialchars($conn->error) . "
          </p>";
}

$stmt->close();
$conn->close();