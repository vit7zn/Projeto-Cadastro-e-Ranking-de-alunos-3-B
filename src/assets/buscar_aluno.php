<?php
// ══════════════════════════════════════════════════════════════
//  buscar_aluno.php — API de busca de aluno por nome
//  Retorna: dados pessoais, notas detalhadas e boletim salvo
// ══════════════════════════════════════════════════════════════

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erro' => 'Não autenticado.']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "sistema_login";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro de conexão: ' . $conn->connect_error]);
    exit();
}
$conn->set_charset('utf8mb4');

$acao = $_GET['acao'] ?? '';

// ════════════════════════════════════════════════
//  AÇÃO: buscar — lista alunos pelo nome (LIKE)
// ════════════════════════════════════════════════
if ($acao === 'buscar') {
    $termo = trim($_GET['q'] ?? '');

    if (strlen($termo) < 2) {
        echo json_encode(['ok' => true, 'alunos' => []]);
        exit();
    }

    $like  = '%' . $termo . '%';
    $stmt  = $conn->prepare("
        SELECT id, nome_completo, curso, categoria_ranking, media_geral, data_cadastro
        FROM alunos
        WHERE nome_completo LIKE ?
        ORDER BY nome_completo ASC
        LIMIT 20
    ");
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $res   = $stmt->get_result();
    $lista = [];
    while ($row = $res->fetch_assoc()) {
        $row['data_cadastro_fmt'] = date('d/m/Y', strtotime($row['data_cadastro']));
        $lista[] = $row;
    }
    $stmt->close();

    echo json_encode(['ok' => true, 'alunos' => $lista]);
    exit();
}

// ════════════════════════════════════════════════
//  AÇÃO: carregar — dados completos de um aluno
// ════════════════════════════════════════════════
if ($acao === 'carregar') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['erro' => 'ID inválido.']);
        exit();
    }

    // Dados pessoais
    $stmt = $conn->prepare("SELECT * FROM alunos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $aluno = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$aluno) {
        echo json_encode(['erro' => 'Aluno não encontrado.']);
        exit();
    }

    // Notas detalhadas
    $stmtN = $conn->prepare("SELECT * FROM notas_detalhadas WHERE aluno_id = ?");
    $stmtN->bind_param('i', $id);
    $stmtN->execute();
    $resN  = $stmtN->get_result();
    $notas = [];
    while ($row = $resN->fetch_assoc()) {
        $notas[] = $row;
    }
    $stmtN->close();

    $usuarioId = (int)$_SESSION['usuario_id'];
    $nomeAluno = $aluno['nome_completo'];

    // ── Auto-vincular boletins órfãos que têm o nome do aluno na descrição ──
    // Cobre o caso: boletim salvo antes de o aluno existir no banco (aluno_id = NULL)
    $stmtVinc = $conn->prepare("
        UPDATE boletins_salvos
        SET aluno_id = ?
        WHERE usuario_id = ?
          AND aluno_id IS NULL
          AND descricao = ?
    ");
    if ($stmtVinc) {
        $stmtVinc->bind_param('iis', $id, $usuarioId, $nomeAluno);
        $stmtVinc->execute();
        $stmtVinc->close();
    }

    // ── Busca boletins vinculados ao aluno (por aluno_id) ──
    $stmtB = $conn->prepare("
        SELECT id, aluno_id, nome_original, descricao, tamanho_bytes, criado_em
        FROM boletins_salvos
        WHERE usuario_id = ? AND aluno_id = ?
        ORDER BY criado_em DESC
        LIMIT 10
    ");
    $stmtB->bind_param('ii', $usuarioId, $id);
    $stmtB->execute();
    $resB     = $stmtB->get_result();
    $boletins = [];
    while ($row = $resB->fetch_assoc()) {
        $kb = round($row['tamanho_bytes'] / 1024, 1);
        $row['tamanho_legivel']  = $kb >= 1024 ? round($kb / 1024, 1) . ' MB' : $kb . ' KB';
        $row['criado_formatado'] = date('d/m/Y H:i', strtotime($row['criado_em']));
        $boletins[] = $row;
    }
    $stmtB->close();

    // ── Todos os boletins do usuário (para o modal poder mostrar como fallback) ──
    $stmtTodos = $conn->prepare("
        SELECT id, aluno_id, nome_original, descricao, tamanho_bytes, criado_em
        FROM boletins_salvos
        WHERE usuario_id = ?
        ORDER BY criado_em DESC
        LIMIT 30
    ");
    $stmtTodos->bind_param('i', $usuarioId);
    $stmtTodos->execute();
    $resTodo       = $stmtTodos->get_result();
    $todosBoletins = [];
    while ($row = $resTodo->fetch_assoc()) {
        $kb = round($row['tamanho_bytes'] / 1024, 1);
        $row['tamanho_legivel']  = $kb >= 1024 ? round($kb / 1024, 1) . ' MB' : $kb . ' KB';
        $row['criado_formatado'] = date('d/m/Y H:i', strtotime($row['criado_em']));
        $todosBoletins[] = $row;
    }
    $stmtTodos->close();

    echo json_encode([
        'ok'            => true,
        'aluno'         => $aluno,
        'notas'         => $notas,
        'boletins'      => $boletins,
        'todos_boletins'=> $todosBoletins
    ]);
    exit();
}

http_response_code(400);
echo json_encode(['erro' => 'Ação inválida.']);