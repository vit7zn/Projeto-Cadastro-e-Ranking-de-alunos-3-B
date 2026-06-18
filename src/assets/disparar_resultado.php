<?php
session_start();

// ── Guard de sessão: redireciona para login se não autenticado ──
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.HTML?erro=acesso_negado");
    exit();
}

require_once 'enviar_whatsapp.php';

// ── Conexão ──
$conn = new mysqli("localhost", "root", "", "sistema_login");
if ($conn->connect_error) die("Erro de conexão: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$log        = [];      // resultados desta execução
$totalOk    = 0;
$totalErro  = 0;
$totalSemTel = 0;
$executou   = false;

// ── Filtro de curso (opcional) ──
$cursoFiltro = $_GET['curso'] ?? '';

// ── Conta pendentes ──
$sqlCount = "SELECT COUNT(*) as total FROM alunos 
             WHERE telefone_responsavel IS NOT NULL 
             AND telefone_responsavel != ''
             AND whatsapp_enviado = 0";
if ($cursoFiltro) $sqlCount .= " AND curso LIKE '%" . $conn->real_escape_string($cursoFiltro) . "%'";
$totalPendentes = $conn->query($sqlCount)->fetch_assoc()['total'] ?? 0;

// ── Execução do disparo ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_disparo'])) {
    $executou = true;

    $cursoPost = $_POST['curso_filtro'] ?? '';
    $sql = "SELECT * FROM alunos WHERE telefone_responsavel IS NOT NULL AND telefone_responsavel != ''";
    if ($cursoPost) $sql .= " AND curso LIKE '%" . $conn->real_escape_string($cursoPost) . "%'";
    if (isset($_POST['apenas_pendentes'])) $sql .= " AND whatsapp_enviado = 0";
    $sql .= " ORDER BY curso, categoria_ranking, media_geral DESC";

    $result = $conn->query($sql);

    while ($aluno = $result->fetch_assoc()) {
        // Calcula situação no ranking
        $sit = calcularSituacao($conn, $aluno);
        $aluno['posicao'] = $sit['posicao'];
        $aluno['situacao'] = $sit['situacao'];

        // Monta e envia
        $mensagem  = montarMensagem($aluno);
        $resultado = enviarWhatsappComPdf($conn, $aluno['telefone_responsavel'], $mensagem, $aluno['curso']);
        registrarLog($conn, $aluno['id'], $aluno['telefone_responsavel'], $mensagem, $resultado);

        $log[] = [
            'nome'     => $aluno['nome_completo'],
            'telefone' => $aluno['telefone_responsavel'],
            'situacao' => $aluno['situacao'],
            'curso'    => $aluno['curso'],
            'ok'       => $resultado['ok'],
            'erro'     => $resultado['erro'] ?? '',
        ];

        if ($resultado['ok']) $totalOk++;
        else $totalErro++;

        // Pausa de 1s entre envios para evitar bloqueio
        sleep(1);
    }
}

// ── Histórico de logs ──
$histLogs = [];
$resLogs = $conn->query(
    "SELECT l.*, a.nome_completo, a.curso 
     FROM log_whatsapp l 
     JOIN alunos a ON a.id = l.aluno_id 
     ORDER BY l.enviado_em DESC 
     LIMIT 50"
);
if ($resLogs) while ($r = $resLogs->fetch_assoc()) $histLogs[] = $r;

// ── Cursos disponíveis ──
$cursos = [];
$rc = $conn->query("SELECT DISTINCT curso FROM alunos ORDER BY curso");
if ($rc) while ($r = $rc->fetch_assoc()) $cursos[] = $r['curso'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disparar Resultado WhatsApp — SIPS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .disparo-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(0,0,0,.09);
            padding: 28px 32px;
            margin-bottom: 22px;
        }
        .disparo-title {
            font-family: 'Lora', Georgia, serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: #0e5200;
            border-bottom: 2px solid #e8f5e9;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .stats-mini {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }
        .stat-mini-card {
            flex: 1;
            min-width: 130px;
            background: #f5f7f5;
            border-radius: 12px;
            padding: 16px 18px;
            border-left: 4px solid #1b8a00;
        }
        .stat-mini-card.orange { border-left-color: #e67e22; }
        .stat-mini-card.red    { border-left-color: #e53935; }
        .stat-mini-label { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: #6b7280; }
        .stat-mini-value { font-size: 2rem; font-weight: 700; color: #1a1a1a; line-height: 1.1; margin-top: 4px; }

        .form-opcoes { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; margin-bottom: 20px; }
        .form-opcoes select, .form-opcoes label { font-family: 'DM Sans', Arial, sans-serif; }
        .form-opcoes select {
            padding: 10px 14px;
            border: 1.5px solid #e0e7e0;
            border-radius: 9px;
            font-size: .9rem;
            color: #1a1a1a;
            background: #fff;
        }
        .form-opcoes select:focus { outline: none; border-color: #1b8a00; box-shadow: 0 0 0 3px rgba(27,138,0,.12); }

        .opcao-check {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f5f7f5;
            border-radius: 9px;
            padding: 10px 16px;
            cursor: pointer;
            font-size: .9rem;
            font-weight: 500;
            color: #1a1a1a;
            border: 1.5px solid #e0e7e0;
            transition: background .2s, border-color .2s;
        }
        .opcao-check:hover { background: #e8f5e9; border-color: #1b8a00; }
        .opcao-check input { accent-color: #1b8a00; width: 16px; height: 16px; }

        .btn-disparar {
            background: linear-gradient(135deg, #1b8a00, #0e5200);
            color: #fff;
            border: none;
            padding: 14px 36px;
            border-radius: 30px;
            font-family: 'DM Sans', Arial, sans-serif;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(27,138,0,.3);
            transition: opacity .2s, transform .15s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-disparar:hover { opacity: .88; transform: translateY(-1px); }

        /* Confirmação modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); backdrop-filter: blur(3px); z-index: 9999; align-items: center; justify-content: center; }
        .modal-overlay.aberto { display: flex; }
        .modal-box { background: #fff; border-radius: 18px; padding: 32px; max-width: 460px; width: 95%; box-shadow: 0 20px 60px rgba(0,0,0,.25); text-align: center; }
        .modal-icon { font-size: 3rem; margin-bottom: 14px; }
        .modal-box h2 { font-family: 'Lora', Georgia, serif; font-size: 1.3rem; color: #0e5200; margin-bottom: 10px; }
        .modal-box p  { color: #6b7280; font-size: .92rem; margin-bottom: 24px; line-height: 1.6; }
        .modal-btns   { display: flex; gap: 12px; justify-content: center; }
        .btn-cancelar { background: #f0f0f0; color: #555; border: none; padding: 12px 28px; border-radius: 30px; font-weight: 600; font-size: .92rem; cursor: pointer; transition: background .2s; }
        .btn-cancelar:hover { background: #e0e0e0; }
        .btn-confirmar { background: #1b8a00; color: #fff; border: none; padding: 12px 28px; border-radius: 30px; font-weight: 700; font-size: .92rem; cursor: pointer; box-shadow: 0 4px 12px rgba(27,138,0,.3); transition: background .2s; }
        .btn-confirmar:hover { background: #0e5200; }

        /* Resultado do disparo */
        .resultado-lista { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 8px; max-height: 400px; overflow-y: auto; }
        .resultado-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: .88rem;
        }
        .resultado-item.ok    { background: #e8f5e9; border-left: 4px solid #1b8a00; }
        .resultado-item.erro  { background: #ffebee; border-left: 4px solid #e53935; }
        .resultado-item .ri-nome { font-weight: 700; color: #1a1a1a; flex: 1; }
        .resultado-item .ri-tel  { font-family: monospace; color: #6b7280; font-size: .82rem; }
        .resultado-item .ri-sit  { font-size: .78rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
        .classificado   { background: #c8e6c9; color: #1b5e20; }
        .nao-classificado { background: #ffcdd2; color: #b71c1c; }

        /* Tabela de histórico */
        .hist-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .hist-table th { background: #e8f5e9; color: #0e5200; padding: 10px 12px; text-align: left; font-size: .78rem; text-transform: uppercase; letter-spacing: .3px; }
        .hist-table td { padding: 10px 12px; border-bottom: 1px solid #e0e7e0; }
        .hist-table tr:last-child td { border-bottom: none; }
        .badge-enviado  { background: #e8f5e9; color: #1b5e20; padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
        .badge-erro     { background: #ffebee; color: #c62828; padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
        .badge-pendente { background: #fff8e1; color: #795548; padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }

        .aviso-configurar {
            background: #fff8e1;
            border: 2px solid #ffe082;
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 22px;
            font-size: .9rem;
            color: #5d4037;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .aviso-configurar span { font-size: 1.4rem; flex-shrink: 0; }
    </style>
</head>
<body>

<!-- OVERLAY -->
<div id="overlay-menu"></div>

<!-- SIDEBAR LATERAL -->
<nav id="sidebar-lateral">
    <a href="dashboard.php">📊 Painel</a>
    <a href="cadastro.php">📋 Cadastro</a>
    <a href="ranking.php">🏆 Ranking</a>
    <a href="disparar_resultado.php" class="active-link">📲 WhatsApp</a>
    <a href="logout.php">🚪 Sair</a>
    <div class="sidebar-footer">SIPS © 2026</div>
</nav>

<header class="navbar">
    <div class="navbar-left">
        <button class="btn-hamburguer" id="btn-hamburguer" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <a class="navbar-brand" href="index.HTML">
            <img src="logo_sips.svg" alt="Logo">
            SIPS
        </a>
    </div>
    <nav class="navbar-cards">
        <a href="dashboard.php" class="nav-card">
          <span class="nav-card-icon">📊</span><span>Painel</span>
        </a>
        <a href="cadastro.php" class="nav-card">
          <span class="nav-card-icon">📋</span><span>Cadastro</span>
        </a>
        <a href="ranking.php" class="nav-card">
          <span class="nav-card-icon">🏆</span><span>Ranking</span>
        </a>
        <a href="disparar_resultado.php" class="nav-card active" style="background:rgba(37,211,102,.15);border-color:rgba(37,211,102,.35);color:rgba(37,211,102,.9);">
          <span class="nav-card-icon">📲</span><span>WhatsApp</span>
        </a>
    </nav>
    <div class="navbar-right">
        <a href="logout.php" class="btn-sair">Sair</a>
    </div>
</header>

<script>
(function(){
    const btn=document.getElementById('btn-hamburguer'),
          sb=document.getElementById('sidebar-lateral'),
          ov=document.getElementById('overlay-menu');
    function f(){btn.classList.remove('aberto');sb.classList.remove('aberta');ov.classList.remove('aberto');}
    btn.addEventListener('click',()=>sb.classList.contains('aberta')?f():(btn.classList.add('aberto'),sb.classList.add('aberta'),ov.classList.add('aberto')));
    ov.addEventListener('click',f);
})();
</script>

<main class="registration-container">
    <h1>📲 Disparar Resultado via WhatsApp</h1>

    <!-- AVISO DE CONFIGURAÇÃO -->
    <?php if (ULTRAMSG_INSTANCE === 'SUA_INSTANCIA_AQUI'): ?>
    <div class="aviso-configurar">
        <span>⚠️</span>
        <div>
            <strong>Configure suas credenciais antes de usar!</strong><br>
            Abra o arquivo <code>enviar_whatsapp.php</code> e substitua <code>SUA_INSTANCIA_AQUI</code> e <code>SEU_TOKEN_AQUI</code> com os dados do seu painel em
            <a href="https://app.ultramsg.com" target="_blank" style="color:#0e5200; font-weight:700;">app.ultramsg.com</a>.
        </div>
    </div>
    <?php endif; ?>

    <!-- CARDS DE RESUMO -->
    <div class="disparo-card">
        <div class="disparo-title">📊 Resumo dos Envios</div>
        <div class="stats-mini">
            <div class="stat-mini-card">
                <div class="stat-mini-label">Pendentes</div>
                <div class="stat-mini-value"><?= $totalPendentes ?></div>
            </div>
            <?php
            $totalEnviados = $conn->query("SELECT COUNT(*) as t FROM alunos WHERE whatsapp_enviado = 1")->fetch_assoc()['t'] ?? 0;
            $semTelefone   = $conn->query("SELECT COUNT(*) as t FROM alunos WHERE telefone_responsavel IS NULL OR telefone_responsavel = ''")->fetch_assoc()['t'] ?? 0;
            ?>
            <div class="stat-mini-card orange">
                <div class="stat-mini-label">Já Enviados</div>
                <div class="stat-mini-value"><?= $totalEnviados ?></div>
            </div>
            <div class="stat-mini-card red">
                <div class="stat-mini-label">Sem Telefone</div>
                <div class="stat-mini-value"><?= $semTelefone ?></div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-label">Total Cadastros</div>
                <div class="stat-mini-value"><?= $conn->query("SELECT COUNT(*) as t FROM alunos")->fetch_assoc()['t'] ?? 0 ?></div>
            </div>
        </div>

        <!-- FORMULÁRIO DE DISPARO -->
        <form method="POST" id="formDisparo">
            <div class="form-opcoes">
                <div>
                    <label style="display:block; font-size:.8rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px;">Filtrar por curso</label>
                    <select name="curso_filtro">
                        <option value="">Todos os cursos</option>
                        <?php foreach ($cursos as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= ($_POST['curso_filtro'] ?? '') === $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="opcao-check">
                    <input type="checkbox" name="apenas_pendentes" value="1" checked>
                    Enviar apenas para quem ainda não recebeu
                </label>
            </div>

            <button type="button" class="btn-disparar" onclick="abrirModal()">
                📲 Disparar Mensagens
            </button>
            <input type="hidden" name="confirmar_disparo" value="1">
        </form>
    </div>

    <!-- RESULTADO DO DISPARO ATUAL -->
    <?php if ($executou): ?>
    <div class="disparo-card">
        <div class="disparo-title">
            ✅ Resultado do Disparo
            <span style="margin-left:auto; font-family:monospace; font-size:.85rem; font-weight:400; color:#6b7280;">
                <?= $totalOk ?> enviados · <?= $totalErro ?> erros
            </span>
        </div>
        <ul class="resultado-lista">
            <?php foreach ($log as $item): ?>
            <li class="resultado-item <?= $item['ok'] ? 'ok' : 'erro' ?>">
                <span style="font-size:1.2rem;"><?= $item['ok'] ? '✅' : '❌' ?></span>
                <span class="ri-nome"><?= htmlspecialchars($item['nome']) ?></span>
                <span class="ri-tel"><?= htmlspecialchars($item['telefone']) ?></span>
                <span class="ri-sit <?= $item['situacao'] === 'CLASSIFICADO(A)' ? 'classificado' : 'nao-classificado' ?>">
                    <?= $item['situacao'] ?>
                </span>
                <span style="font-size:.8rem; color:#888; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= $item['ok'] ? $item['curso'] : '⚠️ ' . htmlspecialchars($item['erro']) ?>
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- HISTÓRICO DE ENVIOS -->
    <?php if (!empty($histLogs)): ?>
    <div class="disparo-card">
        <div class="disparo-title">🕒 Histórico de Envios (últimos 50)</div>
        <div style="overflow-x:auto;">
            <table class="hist-table">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Curso</th>
                        <th>Telefone</th>
                        <th>Status</th>
                        <th>Enviado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($histLogs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['nome_completo']) ?></td>
                        <td><?= htmlspecialchars($log['curso']) ?></td>
                        <td style="font-family:monospace;"><?= htmlspecialchars($log['telefone']) ?></td>
                        <td>
                            <?php if ($log['status'] === 'enviado'): ?>
                                <span class="badge-enviado">✅ Enviado</span>
                            <?php elseif ($log['status'] === 'erro'): ?>
                                <span class="badge-erro">❌ Erro</span>
                            <?php else: ?>
                                <span class="badge-pendente">⏳ Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.82rem; color:#6b7280;">
                            <?= date('d/m/Y H:i', strtotime($log['enviado_em'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>

<!-- MODAL DE CONFIRMAÇÃO -->
<div class="modal-overlay" id="modalConfirmar">
    <div class="modal-box">
        <div class="modal-icon">📲</div>
        <h2>Confirmar Disparo</h2>
        <p>
            Isso irá enviar mensagens de WhatsApp para os responsáveis dos alunos cadastrados.<br><br>
            <strong><?= $totalPendentes ?> mensagens pendentes</strong> serão disparadas agora.<br>
            Esta ação não pode ser desfeita.
        </p>
        <div class="modal-btns">
            <button class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
            <button class="btn-confirmar" onclick="document.getElementById('formDisparo').submit()">
                ✅ Sim, disparar agora
            </button>
        </div>
    </div>
</div>

<script>
function abrirModal() { document.getElementById('modalConfirmar').classList.add('aberto'); }
function fecharModal() { document.getElementById('modalConfirmar').classList.remove('aberto'); }
document.getElementById('modalConfirmar').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>

</body>
</html>
