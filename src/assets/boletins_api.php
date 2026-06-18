<?php
// ══════════════════════════════════════════════════════════════
//  boletins_api.php — Biblioteca de Boletins (upload/listar/excluir/get)
//  Usa mysqli, no mesmo padrão de dashboard.php / cadastro.php
// ══════════════════════════════════════════════════════════════

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erro' => 'Não autenticado.']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

// ── Conexão (mesmo padrão usado em dashboard.php) ───────────────
$host = "localhost"; $user = "root"; $pass = ""; $dbname = "sistema_login";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro de conexão com o banco: ' . $conn->connect_error]);
    exit();
}
$conn->set_charset('utf8mb4');

// ── Diretório de armazenamento dos PDFs ──────────────────────────
define('BOLETINS_DIR', __DIR__ . '/uploads/boletins/');
define('MAX_TAMANHO_MB', 10);
define('MAX_BOLETINS_POR_USUARIO', 30);

if (!is_dir(BOLETINS_DIR)) {
    mkdir(BOLETINS_DIR, 0755, true);
}

// Migração: adiciona aluno_id se a tabela já existia sem ela
$conn->query("ALTER TABLE `boletins_salvos` ADD COLUMN IF NOT EXISTS `aluno_id` INT(11) DEFAULT NULL");
$conn->query("ALTER TABLE `boletins_salvos` ADD INDEX IF NOT EXISTS `aluno_id` (`aluno_id`)");

// Garante que a tabela existe (idempotente — não derruba dados existentes)
$conn->query("
    CREATE TABLE IF NOT EXISTS `boletins_salvos` (
        `id`            INT(11) NOT NULL AUTO_INCREMENT,
        `usuario_id`    INT(11) NOT NULL,
        `nome_arquivo`  VARCHAR(255) NOT NULL,
        `nome_original` VARCHAR(255) NOT NULL,
        `descricao`     VARCHAR(200) DEFAULT '',
        `tamanho_bytes` INT(11) DEFAULT 0,
        `aluno_id`      INT(11) DEFAULT NULL,
        `criado_em`     DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `usuario_id` (`usuario_id`),
        KEY `aluno_id` (`aluno_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

$acao      = $_GET['acao'] ?? $_POST['acao'] ?? '';
$usuarioId = (int) $_SESSION['usuario_id'];

// ════════════════════════════════════════════════════════════
//  AÇÃO: listar
// ════════════════════════════════════════════════════════════
if ($acao === 'listar') {
    $stmt = $conn->prepare("
        SELECT id, aluno_id, nome_original, descricao, tamanho_bytes, criado_em
        FROM boletins_salvos
        WHERE usuario_id = ?
        ORDER BY criado_em DESC
    ");
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();

    $boletins = [];
    while ($row = $result->fetch_assoc()) {
        $kb = round($row['tamanho_bytes'] / 1024, 1);
        $row['tamanho_legivel']   = $kb >= 1024 ? round($kb / 1024, 1) . ' MB' : $kb . ' KB';
        $row['criado_formatado']  = date('d/m/Y H:i', strtotime($row['criado_em']));
        $boletins[] = $row;
    }
    $stmt->close();

    echo json_encode(['ok' => true, 'boletins' => $boletins]);
    exit();
}

// ════════════════════════════════════════════════════════════
//  AÇÃO: upload
// ════════════════════════════════════════════════════════════
if ($acao === 'upload') {

    if (!isset($_FILES['boletim']) || $_FILES['boletim']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['erro' => 'Nenhum arquivo recebido ou erro no upload.']);
        exit();
    }

    $arquivo      = $_FILES['boletim'];
    $nomeOriginal = htmlspecialchars(basename($arquivo['name']));
    $descricao    = htmlspecialchars(trim($_POST['descricao'] ?? ''));
    $alunoId      = !empty($_POST['aluno_id']) ? (int)$_POST['aluno_id'] : null;
    $tamanho      = $arquivo['size'];
    $tmpPath      = $arquivo['tmp_name'];

    // Aceita PDF ou imagem (já que o cadastro envia o arquivo original)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    $tiposAceitos = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mimeType, $tiposAceitos)) {
        echo json_encode(['erro' => 'Apenas PDF, JPG, PNG ou WEBP são aceitos.']);
        exit();
    }

    if ($tamanho > MAX_TAMANHO_MB * 1024 * 1024) {
        echo json_encode(['erro' => 'Arquivo muito grande. Máximo: ' . MAX_TAMANHO_MB . ' MB.']);
        exit();
    }

    $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM boletins_salvos WHERE usuario_id = ?");
    $stmtCount->bind_param('i', $usuarioId);
    $stmtCount->execute();
    $totalSalvos = (int) ($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    if ($totalSalvos >= MAX_BOLETINS_POR_USUARIO) {
        echo json_encode(['erro' => 'Limite de ' . MAX_BOLETINS_POR_USUARIO . ' boletins atingido. Exclua um para continuar.']);
        exit();
    }

    $extMap   = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $extensao = $extMap[$mimeType] ?? 'bin';
    $nomeUnico   = 'boletim_u' . $usuarioId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;
    $destinoPath = BOLETINS_DIR . $nomeUnico;

    if (!move_uploaded_file($tmpPath, $destinoPath)) {
        echo json_encode(['erro' => 'Falha ao salvar o arquivo no servidor.']);
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO boletins_salvos (usuario_id, aluno_id, nome_arquivo, nome_original, descricao, tamanho_bytes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('iisssi', $usuarioId, $alunoId, $nomeUnico, $nomeOriginal, $descricao, $tamanho);
    $stmt->execute();
    $novoId = $conn->insert_id;
    $stmt->close();

    echo json_encode(['ok' => true, 'id' => $novoId, 'msg' => 'Boletim salvo com sucesso!']);
    exit();
}

// ════════════════════════════════════════════════════════════
//  AÇÃO: excluir
// ════════════════════════════════════════════════════════════
if ($acao === 'excluir') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['erro' => 'ID inválido.']);
        exit();
    }

    $stmt = $conn->prepare("SELECT nome_arquivo FROM boletins_salvos WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param('ii', $id, $usuarioId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['erro' => 'Boletim não encontrado.']);
        exit();
    }

    $filePath = BOLETINS_DIR . $row['nome_arquivo'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $stmtDel = $conn->prepare("DELETE FROM boletins_salvos WHERE id = ? AND usuario_id = ?");
    $stmtDel->bind_param('ii', $id, $usuarioId);
    $stmtDel->execute();
    $stmtDel->close();

    echo json_encode(['ok' => true, 'msg' => 'Boletim excluído.']);
    exit();
}

// ════════════════════════════════════════════════════════════
//  AÇÃO: get (retorna base64 do arquivo, para o OCR)
// ════════════════════════════════════════════════════════════
if ($acao === 'get') {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['erro' => 'ID inválido.']);
        exit();
    }

    $stmt = $conn->prepare("SELECT nome_arquivo, nome_original FROM boletins_salvos WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param('ii', $id, $usuarioId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['erro' => 'Boletim não encontrado.']);
        exit();
    }

    $filePath = BOLETINS_DIR . $row['nome_arquivo'];
    if (!file_exists($filePath)) {
        echo json_encode(['erro' => 'Arquivo não encontrado no servidor.']);
        exit();
    }

    $extensao = strtolower(pathinfo($row['nome_arquivo'], PATHINFO_EXTENSION));
    $tipoMime = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ][$extensao] ?? 'application/octet-stream';

    $base64 = base64_encode(file_get_contents($filePath));

    echo json_encode([
        'ok'            => true,
        'base64'        => $base64,
        'nome_original' => $row['nome_original'],
        'tipo'          => $tipoMime
    ]);
    exit();
}

// ── Ação não reconhecida ─────────────────────────────────────
http_response_code(400);
echo json_encode(['erro' => 'Ação inválida.']);