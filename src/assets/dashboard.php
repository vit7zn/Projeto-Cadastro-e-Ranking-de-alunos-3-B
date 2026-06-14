<?php
session_start();

// ── Guard de sessão: redireciona para login se não autenticado ──
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.HTML?erro=acesso_negado");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost"; $user = "root"; $pass = ""; $dbname = "sistema_login";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Erro: " . $conn->connect_error);

// ── Filtros vindos do AJAX ou da URL ──
$filtroCurso = $_GET['curso'] ?? 'todos';
$filtroSexo  = $_GET['sexo']  ?? 'todos';
$filtroProc  = $_GET['proc']  ?? 'todos';

// ── Monta cláusula WHERE dinâmica ──
$where = [];
if ($filtroCurso !== 'todos') $where[] = "curso = '" . $conn->real_escape_string($filtroCurso) . "'";
if ($filtroSexo  !== 'todos') $where[] = "sexo LIKE '%" . $conn->real_escape_string($filtroSexo) . "%'";
if ($filtroProc  !== 'todos') $where[] = "procedencia_escolar = '" . $conn->real_escape_string($filtroProc) . "'";
$wClause = count($where) ? "WHERE " . implode(" AND ", $where) : "";

function q($conn, $sql) {
    $r = $conn->query($sql);
    return $r ?: null;
}

// ── Dados para os cards ──
$totalCadastros = q($conn, "SELECT COUNT(*) c FROM alunos $wClause")?->fetch_assoc()['c'] ?? 0;
$mediaGeral     = q($conn, "SELECT ROUND(AVG(media_geral),2) m FROM alunos $wClause")?->fetch_assoc()['m'] ?? 0;
$totalPCD       = q($conn, "SELECT COUNT(*) c FROM alunos " . ($wClause ? "$wClause AND" : "WHERE") . " pcd='sim'")?->fetch_assoc()['c'] ?? 0;
$totalCotaLocal = q($conn, "SELECT COUNT(*) c FROM alunos " . ($wClause ? "$wClause AND" : "WHERE") . " cota_local='sim'")?->fetch_assoc()['c'] ?? 0;

// ── Distribuição por gênero ──
$generoData = ['Masculino'=>0,'Feminino'=>0,'Outro'=>0];
$r = q($conn, "SELECT sexo, COUNT(*) qtd FROM alunos $wClause GROUP BY sexo");
if ($r) while ($row = $r->fetch_assoc()) {
    $s = strtolower($row['sexo'] ?? '');
    if (str_contains($s,'masc')) $generoData['Masculino'] += $row['qtd'];
    elseif (str_contains($s,'fem')) $generoData['Feminino'] += $row['qtd'];
    else $generoData['Outro'] += $row['qtd'];
}

// ── Procedência ──
$procData = ['Pública'=>0,'Privada'=>0];
$r = q($conn, "SELECT procedencia_escolar, COUNT(*) qtd FROM alunos $wClause GROUP BY procedencia_escolar");
if ($r) while ($row = $r->fetch_assoc()) {
    if ($row['procedencia_escolar']==='publica') $procData['Pública']=$row['qtd'];
    else $procData['Privada']=$row['qtd'];
}

// ── Distribuição por curso ──
$cursoData = [];
$r = q($conn, "SELECT curso, COUNT(*) qtd FROM alunos $wClause GROUP BY curso ORDER BY qtd DESC");
if ($r) while ($row = $r->fetch_assoc()) $cursoData[$row['curso'] ?: 'N/D'] = $row['qtd'];

// ── Categorias de ranking ──
$rankingData = [];
$r = q($conn, "SELECT categoria_ranking, COUNT(*) qtd FROM alunos $wClause GROUP BY categoria_ranking");
if ($r) while ($row = $r->fetch_assoc()) $rankingData[$row['categoria_ranking'] ?: 'N/D'] = $row['qtd'];

// ── Média por curso ──
$mediaCursoData = [];
$r = q($conn, "SELECT curso, ROUND(AVG(media_geral),2) media FROM alunos $wClause GROUP BY curso ORDER BY media DESC");
if ($r) while ($row = $r->fetch_assoc()) $mediaCursoData[$row['curso'] ?: 'N/D'] = $row['media'];

// ── Top 10 alunos ──
$topAlunos = [];
$wTop = count($where)
    ? "WHERE " . implode(" AND ", $where) . " AND media_geral IS NOT NULL"
    : "WHERE media_geral IS NOT NULL";
$r = q($conn, "SELECT nome_completo, media_geral, categoria_ranking, curso FROM alunos $wTop ORDER BY media_geral DESC LIMIT 10");
if ($r) while ($row = $r->fetch_assoc()) $topAlunos[] = $row;

// ── Listas para filtros ──
$cursos = [];
$r = q($conn, "SELECT DISTINCT curso FROM alunos WHERE curso IS NOT NULL ORDER BY curso");
if ($r) while ($row = $r->fetch_assoc()) $cursos[] = $row['curso'];

$nomeUsuario = $_SESSION['nome'] ?? 'Secretária';
$conn->close();

// ── Se requisição AJAX, retorna JSON ──
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'totalCadastros' => $totalCadastros,
        'mediaGeral'     => number_format($mediaGeral, 1, ',', '.'),
        'totalPCD'       => $totalPCD,
        'totalCotaLocal' => $totalCotaLocal,
        'pubTotal'       => $procData['Pública'],
        'privTotal'      => $procData['Privada'],
        'generoLabels'   => array_keys($generoData),
        'generoValues'   => array_values($generoData),
        'procLabels'     => array_keys($procData),
        'procValues'     => array_values($procData),
        'cursoLabels'    => array_keys($cursoData),
        'cursoValues'    => array_values($cursoData),
        'rankLabels'     => array_keys($rankingData),
        'rankValues'     => array_values($rankingData),
        'mediaCursoLbls' => array_keys($mediaCursoData),
        'mediaCursoVals' => array_values($mediaCursoData),
        'topAlunos'      => $topAlunos,
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – Sistema de Cadastro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root {
  --green-dark:#145f00; --green-main:#1b8a00; --green-light:#23a800;
  --orange:#e67e22; --blue:#3b82f6; --violet:#8b5cf6;
  --teal:#0ea5e9; --pink:#ec4899; --yellow:#f59e0b;
  --bg:#f0f2f0; --card:#fff; --border:#e5e7eb;
  --text:#1a1a1a; --muted:#6b7280;
  --shadow:0 2px 12px rgba(0,0,0,.08);
  --radius:14px;
}
body { background:var(--bg); font-family:'Outfit',sans-serif; color:var(--text); }

/* ── FILTROS ── */
.filter-bar {
  background:var(--card); border-radius:var(--radius); padding:16px 20px;
  box-shadow:var(--shadow); margin-bottom:22px;
  display:flex; flex-wrap:wrap; align-items:center; gap:12px;
}
.filter-bar label { font-size:.78rem; font-weight:700; color:var(--muted);
  text-transform:uppercase; letter-spacing:.4px; }
.filter-group { display:flex; align-items:center; gap:8px; }
.filter-select {
  padding:8px 14px; border:1.5px solid var(--border); border-radius:25px;
  font-size:.85rem; font-family:'Outfit',sans-serif; color:var(--text);
  background:var(--bg); cursor:pointer; outline:none;
  transition:border-color .2s;
}
.filter-select:focus { border-color:var(--green-main); }
.filter-select:hover { border-color:var(--green-main); }
.btn-reset {
  padding:8px 18px; border:1.5px solid var(--border); border-radius:25px;
  background:#fff; font-size:.82rem; font-family:'Outfit',sans-serif;
  color:var(--muted); cursor:pointer; transition:all .2s;
}
.btn-reset:hover { border-color:#e53935; color:#e53935; background:#fff5f5; }
.filter-active-badge {
  background:#dcfce7; color:#166534; padding:4px 10px; border-radius:20px;
  font-size:.75rem; font-weight:700; display:none;
}
.filter-active-badge.show { display:inline-block; }

/* ── CARDS ── */
.dash-wrapper { padding:28px 32px; max-width:1280px; margin:0 auto; }
.dash-header { margin-bottom:22px; }
.dash-header h1 { font-size:1.5rem; font-weight:700; color:var(--green-dark); }
.dash-header p  { color:var(--muted); font-size:.88rem; margin-top:2px; }

.stats-row {
  display:grid; grid-template-columns:repeat(auto-fit,minmax(175px,1fr));
  gap:14px; margin-bottom:22px;
}
.stat-card {
  background:var(--card); border-radius:var(--radius); padding:20px;
  box-shadow:var(--shadow); border-top:4px solid var(--green-main);
  transition:transform .2s,box-shadow .2s; animation:fadeUp .4s ease both;
}
.stat-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,.12); }
.stat-card.orange { border-top-color:var(--orange); }
.stat-card.teal   { border-top-color:var(--teal); }
.stat-card.violet { border-top-color:var(--violet); }
.stat-card.blue   { border-top-color:var(--blue); }
.stat-card.pink   { border-top-color:var(--pink); }
.stat-label { font-size:.74rem; font-weight:700; text-transform:uppercase;
  letter-spacing:.5px; color:var(--muted); display:block; margin-bottom:4px; }
.stat-value { font-size:2.2rem; font-weight:800; line-height:1; display:block; }
.stat-sub   { font-size:.75rem; color:var(--muted); display:block; margin-top:4px; }

/* ── GRID GRÁFICOS ── */
.charts-grid { display:grid; grid-template-columns:repeat(12,1fr); gap:16px; }
.chart-card {
  background:var(--card); border-radius:var(--radius); padding:20px;
  box-shadow:var(--shadow); animation:fadeUp .5s ease both;
}
.chart-card h3 {
  font-size:.82rem; font-weight:700; text-transform:uppercase;
  letter-spacing:.4px; color:var(--text); margin-bottom:14px;
  padding-bottom:10px; border-bottom:2px solid var(--border);
  display:flex; align-items:center; gap:6px;
}
.col-4  { grid-column:span 4; }
.col-5  { grid-column:span 5; }
.col-6  { grid-column:span 6; }
.col-7  { grid-column:span 7; }
.col-8  { grid-column:span 8; }
.col-12 { grid-column:span 12; }
.h-wrap { position:relative; }
.h240 { height:240px; } .h200 { height:200px; } .h280 { height:280px; }

/* ── TABELA TOP ── */
.top-table { width:100%; border-collapse:collapse; font-size:.84rem; }
.top-table th { text-align:left; padding:9px 12px; background:#f8faf8;
  color:var(--muted); font-weight:600; font-size:.73rem; text-transform:uppercase;
  border-bottom:2px solid var(--border); }
.top-table td { padding:9px 12px; border-bottom:1px solid var(--border); }
.top-table tr:last-child td { border-bottom:none; }
.top-table tr:hover td { background:#f9fdf7; }
.badge { display:inline-block; padding:3px 9px; border-radius:20px; font-size:.7rem; font-weight:700; }
.badge-green  { background:#dcfce7; color:#166534; }
.badge-blue   { background:#dbeafe; color:#1e40af; }
.badge-violet { background:#ede9fe; color:#5b21b6; }
.badge-orange { background:#ffedd5; color:#9a3412; }
.badge-pink   { background:#fce7f3; color:#9d174d; }
.medal { font-size:.95rem; }

/* ── LOADING OVERLAY ── */
.loading-overlay {
  position:absolute; inset:0; background:rgba(255,255,255,.75);
  border-radius:var(--radius); display:none; align-items:center;
  justify-content:center; z-index:10;
}
.loading-overlay.ativo { display:flex; }
.spinner { width:28px; height:28px; border:3px solid #d4e4d0;
  border-top-color:var(--green-main); border-radius:50%;
  animation:spin .7s linear infinite; }

/* ── EMPTY STATE ── */
.empty-state { display:flex; flex-direction:column; align-items:center;
  justify-content:center; height:160px; color:var(--muted); font-size:.88rem; gap:6px; }
.empty-state span { font-size:2rem; }

/* ── RESPONSIVE ── */
@media(max-width:960px) {
  .col-4,.col-5,.col-6,.col-7,.col-8 { grid-column:span 12; }
  .dash-wrapper { padding:14px; }
  .filter-bar { gap:8px; }
}
@media(max-width:600px) {
  .stats-row { grid-template-columns:repeat(2,1fr); }
  .filter-group { flex-wrap:wrap; }
}

/* ── BOTÃO WHATSAPP NA NAVBAR ── */
.nav-link-whatsapp {
  background: linear-gradient(135deg, #25D366, #128C7E) !important;
  color: #fff !important;
  padding: 7px 16px !important;
  border-radius: 20px !important;
  font-weight: 700 !important;
  font-size: .83rem !important;
  box-shadow: 0 2px 8px rgba(37,211,102,.35);
  transition: opacity .2s, transform .15s, box-shadow .2s !important;
}
.nav-link-whatsapp:hover {
  opacity: .9 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 14px rgba(37,211,102,.45) !important;
  background: linear-gradient(135deg, #25D366, #128C7E) !important;
  color: #fff !important;
}

@keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
@keyframes spin   { to{transform:rotate(360deg)} }
.stat-card:nth-child(1){animation-delay:.05s} .stat-card:nth-child(2){animation-delay:.10s}
.stat-card:nth-child(3){animation-delay:.15s} .stat-card:nth-child(4){animation-delay:.20s}
.stat-card:nth-child(5){animation-delay:.25s} .stat-card:nth-child(6){animation-delay:.30s}
</style>
</head>
<body>

<div id="overlay-menu"></div>
<nav id="sidebar-lateral">
  <a href="dashboard.php" class="active-link">📊 Dashboard</a>
  <a href="cadastro.php">📋 Cadastro</a>
  <a href="ranking.php">🏆 Ranking</a>
  <a href="disparar_resultado.php">📲 WhatsApp</a>
  <a href="logout.php">🚪 Sair</a>
  <div class="sidebar-footer">EEEP Manoel Mano © 2027</div>
</nav>

<nav class="navbar">
  <div class="navbar-left">
    <button class="btn-hamburguer" id="btn-hamburguer" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <a class="navbar-brand">
      <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTJgsXhRW5qdtpDbZWZmmPzg9njNJXOGcYLpQ&s" alt="Logo">
      EEEP Manoel Mano
    </a>
  </div>
  <div class="navbar-cards">
    <a href="dashboard.php" class="nav-card active">
      <span class="nav-card-icon">📊</span><span>Painel</span>
    </a>
    <a href="cadastro.php" class="nav-card">
      <span class="nav-card-icon">📋</span><span>Cadastro</span>
    </a>
    <a href="ranking.php" class="nav-card">
      <span class="nav-card-icon">🏆</span><span>Ranking</span>
    </a>
    <a href="disparar_resultado.php" class="nav-card" style="background:rgba(37,211,102,.1);border-color:rgba(37,211,102,.22);color:rgba(37,211,102,.85);">
      <span class="nav-card-icon">📲</span><span>WhatsApp</span>
    </a>
  </div>
  <div class="navbar-right">
    <a href="logout.php" class="btn-sair">Sair</a>
  </div>
</nav>

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

<main class="dash-wrapper">

  <div class="dash-header">
    <h1>📊 Dashboard</h1>
    <p>Visão geral dos cadastros e desempenho dos alunos</p>
  </div>

  <!-- BARRA DE FILTROS -->
  <div class="filter-bar">
    <span style="font-size:.82rem;font-weight:700;color:var(--green-dark);">🔍 Filtrar por:</span>

    <div class="filter-group">
      <label for="f-curso">Curso</label>
      <select class="filter-select" id="f-curso">
        <option value="todos">Todos os cursos</option>
        <?php foreach ($cursos as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>" <?= $filtroCurso===$c?'selected':'' ?>>
          <?= htmlspecialchars($c) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label for="f-sexo">Sexo</label>
      <select class="filter-select" id="f-sexo">
        <option value="todos">Todos</option>
        <option value="masc" <?= $filtroSexo==='masc'?'selected':'' ?>>Masculino</option>
        <option value="fem"  <?= $filtroSexo==='fem' ?'selected':'' ?>>Feminino</option>
      </select>
    </div>

    <div class="filter-group">
      <label for="f-proc">Procedência</label>
      <select class="filter-select" id="f-proc">
        <option value="todos">Todas</option>
        <option value="publica"  <?= $filtroProc==='publica' ?'selected':'' ?>>Escola Pública</option>
        <option value="privada"  <?= $filtroProc==='privada' ?'selected':'' ?>>Escola Privada</option>
      </select>
    </div>

    <button class="btn-reset" id="btn-reset-filtros">✕ Limpar filtros</button>
    <span class="filter-active-badge" id="badge-filtro">Filtro ativo</span>
  </div>

  <!-- CARDS ESTATÍSTICOS -->
  <div class="stats-row" id="stats-row">
    <div class="stat-card">
      <span class="stat-label">Total de Cadastros</span>
      <span class="stat-value" id="v-total"><?= number_format($totalCadastros) ?></span>
      <span class="stat-sub">alunos registrados</span>
    </div>
    <div class="stat-card orange">
      <span class="stat-label">Média Geral</span>
      <span class="stat-value" id="v-media"><?= number_format($mediaGeral, 1, ',', '.') ?></span>
      <span class="stat-sub">pontos de média</span>
    </div>
    <div class="stat-card teal">
      <span class="stat-label">Alunos PCD</span>
      <span class="stat-value" id="v-pcd"><?= $totalPCD ?></span>
      <span class="stat-sub">com necessidades especiais</span>
    </div>
    <div class="stat-card violet">
      <span class="stat-label">Cota Local</span>
      <span class="stat-value" id="v-cota"><?= $totalCotaLocal ?></span>
      <span class="stat-sub">bairro Venâncios</span>
    </div>
    <div class="stat-card blue">
      <span class="stat-label">Escola Pública</span>
      <span class="stat-value" id="v-pub"><?= $procData['Pública'] ?></span>
      <span class="stat-sub">procedência pública</span>
    </div>
    <div class="stat-card pink">
      <span class="stat-label">Escola Privada</span>
      <span class="stat-value" id="v-priv"><?= $procData['Privada'] ?></span>
      <span class="stat-sub">procedência privada</span>
    </div>
  </div>

  <!-- GRÁFICOS -->
  <div class="charts-grid">

    <!-- Alunos por Curso -->
    <div class="chart-card col-7" style="position:relative">
      <h3>🎓 Alunos por Curso</h3>
      <div class="loading-overlay" id="load-curso"><div class="spinner"></div></div>
      <div class="h-wrap h240"><canvas id="chartCurso"></canvas></div>
    </div>

    <!-- Procedência (rosca) -->
    <div class="chart-card col-5" style="position:relative">
      <h3>🏫 Procedência Escolar</h3>
      <div class="loading-overlay" id="load-proc"><div class="spinner"></div></div>
      <div class="h-wrap h240"><canvas id="chartProcedencia"></canvas></div>
    </div>

    <!-- Gênero -->
    <div class="chart-card col-5" style="position:relative">
      <h3>👥 Gênero dos Alunos</h3>
      <div class="loading-overlay" id="load-gen"><div class="spinner"></div></div>
      <div class="h-wrap h200"><canvas id="chartGenero"></canvas></div>
    </div>

    <!-- Categorias Ranking -->
    <div class="chart-card col-7" style="position:relative">
      <h3>🏆 Categorias no Ranking</h3>
      <div class="loading-overlay" id="load-rank"><div class="spinner"></div></div>
      <div class="h-wrap h200"><canvas id="chartRanking"></canvas></div>
    </div>

    <!-- Média por Curso -->
    <div class="chart-card col-12" style="position:relative">
      <h3>📈 Média por Curso</h3>
      <div class="loading-overlay" id="load-media"><div class="spinner"></div></div>
      <div class="h-wrap h200"><canvas id="chartMediaCurso"></canvas></div>
    </div>

    <!-- Top 10 Alunos -->
    <div class="chart-card col-12" style="position:relative">
      <h3>🥇 Top 10 Alunos por Média</h3>
      <div class="loading-overlay" id="load-top"><div class="spinner"></div></div>
      <div id="top-table-wrap">
        <?php if (!empty($topAlunos)): ?>
        <table class="top-table">
          <thead><tr><th>#</th><th>Nome</th><th>Curso</th><th>Média</th><th>Categoria</th></tr></thead>
          <tbody>
          <?php
            $medals=['🥇','🥈','🥉','4º','5º','6º','7º','8º','9º','10º'];
            $badges=['badge-green','badge-blue','badge-orange','badge-violet','badge-pink'];
            foreach($topAlunos as $i=>$a):
          ?>
          <tr>
            <td><span class="medal"><?=$medals[$i]?></span></td>
            <td><?=htmlspecialchars($a['nome_completo'])?></td>
            <td><?=htmlspecialchars($a['curso']??'—')?></td>
            <td><strong><?=number_format($a['media_geral'],2,',','.')?></strong></td>
            <td><span class="badge <?=$badges[$i%5]?>"><?=htmlspecialchars($a['categoria_ranking']??'—')?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state"><span>📭</span>Nenhum aluno com média cadastrada</div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>

<script src="script.js"></script>
<script>
const COLORS = {
  green:'#1b8a00', lightG:'#23a800', orange:'#e67e22',
  blue:'#3b82f6', violet:'#8b5cf6', pink:'#ec4899',
  teal:'#0ea5e9', yellow:'#f59e0b', red:'#ef4444',
};
const PALETTE = [COLORS.green,COLORS.orange,COLORS.blue,COLORS.violet,COLORS.pink,COLORS.teal,COLORS.yellow,COLORS.red];
const FONT = {family:'Outfit',size:12};
Chart.defaults.font = FONT;

// ── Dados iniciais do PHP ──
const initData = {
  cursoLabels:    <?= json_encode(array_keys($cursoData)) ?>,
  cursoValues:    <?= json_encode(array_values($cursoData)) ?>,
  procLabels:     <?= json_encode(array_keys($procData)) ?>,
  procValues:     <?= json_encode(array_values($procData)) ?>,
  generoLabels:   <?= json_encode(array_keys($generoData)) ?>,
  generoValues:   <?= json_encode(array_values($generoData)) ?>,
  rankLabels:     <?= json_encode(array_keys($rankingData)) ?>,
  rankValues:     <?= json_encode(array_values($rankingData)) ?>,
  mediaCursoLbls: <?= json_encode(array_keys($mediaCursoData)) ?>,
  mediaCursoVals: <?= json_encode(array_values($mediaCursoData)) ?>,
};

// ── Cria gráficos ──
const charts = {};

function mkBar(id, labels, data, colors, indexAxis='x', max=null) {
  const ctx = document.getElementById(id);
  if (!ctx) return null;
  const cfg = {
    type:'bar',
    data:{ labels, datasets:[{ data, backgroundColor: colors||PALETTE, borderRadius:6, borderSkipped:false }] },
    options:{
      indexAxis, responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{display:false} },
      scales:{
        x:{ grid:{color: indexAxis==='y'?'#f0f0f0':'none'}, ticks:{font:FONT}, ...(max&&indexAxis==='y'?{max}:{}) },
        y:{ grid:{color: indexAxis==='x'?'#f0f0f0':'none'}, ticks:{font:FONT}, ...(max&&indexAxis==='x'?{max}:{}) }
      }
    }
  };
  return new Chart(ctx, cfg);
}

function mkDoughnut(id, labels, data) {
  const ctx = document.getElementById(id);
  if (!ctx) return null;
  return new Chart(ctx, {
    type:'doughnut',
    data:{ labels, datasets:[{ data, backgroundColor:[COLORS.green,COLORS.orange], borderWidth:3, borderColor:'#fff' }] },
    options:{ responsive:true, maintainAspectRatio:false, cutout:'62%',
      plugins:{ legend:{position:'bottom', labels:{font:FONT, padding:14}} } }
  });
}

charts.curso     = mkBar('chartCurso',    initData.cursoLabels,    initData.cursoValues,    PALETTE);
charts.proc      = mkDoughnut('chartProcedencia', initData.procLabels, initData.procValues);
charts.genero    = mkBar('chartGenero',   initData.generoLabels,   initData.generoValues,   [COLORS.blue,COLORS.pink,COLORS.teal], 'y');
charts.ranking   = mkBar('chartRanking',  initData.rankLabels,     initData.rankValues,     PALETTE);
charts.media     = mkBar('chartMediaCurso', initData.mediaCursoLbls, initData.mediaCursoVals, PALETTE, 'x', 10);

// ── Atualiza gráfico com novos dados ──
function updateChart(chart, labels, data) {
  if (!chart) return;
  chart.data.labels = labels;
  chart.data.datasets[0].data = data;
  chart.update('active');
}

// ── Atualiza tabela top 10 ──
function renderTop(alunos) {
  const medals=['🥇','🥈','🥉','4º','5º','6º','7º','8º','9º','10º'];
  const badges=['badge-green','badge-blue','badge-orange','badge-violet','badge-pink'];
  if (!alunos.length) {
    document.getElementById('top-table-wrap').innerHTML = '<div class="empty-state"><span>📭</span>Nenhum aluno encontrado</div>';
    return;
  }
  let html = `<table class="top-table"><thead><tr><th>#</th><th>Nome</th><th>Curso</th><th>Média</th><th>Categoria</th></tr></thead><tbody>`;
  alunos.forEach((a,i) => {
    const media = parseFloat(a.media_geral).toFixed(2).replace('.',',');
    html += `<tr>
      <td><span class="medal">${medals[i]||i+1+'º'}</span></td>
      <td>${a.nome_completo}</td>
      <td>${a.curso||'—'}</td>
      <td><strong>${media}</strong></td>
      <td><span class="badge ${badges[i%5]}">${a.categoria_ranking||'—'}</span></td>
    </tr>`;
  });
  html += '</tbody></table>';
  document.getElementById('top-table-wrap').innerHTML = html;
}

// ── Spinners ──
function setLoading(on) {
  ['load-curso','load-proc','load-gen','load-rank','load-media','load-top'].forEach(id => {
    document.getElementById(id)?.classList.toggle('ativo', on);
  });
}

// ── Busca dados filtrados via AJAX ──
async function aplicarFiltros() {
  const curso = document.getElementById('f-curso').value;
  const sexo  = document.getElementById('f-sexo').value;
  const proc  = document.getElementById('f-proc').value;

  const ativo = curso!=='todos' || sexo!=='todos' || proc!=='todos';
  document.getElementById('badge-filtro').classList.toggle('show', ativo);

  setLoading(true);

  try {
    const url = `dashboard.php?ajax=1&curso=${encodeURIComponent(curso)}&sexo=${encodeURIComponent(sexo)}&proc=${encodeURIComponent(proc)}`;
    const res = await fetch(url);
    const d   = await res.json();

    // Cards
    document.getElementById('v-total').textContent = d.totalCadastros;
    document.getElementById('v-media').textContent = d.mediaGeral;
    document.getElementById('v-pcd').textContent   = d.totalPCD;
    document.getElementById('v-cota').textContent  = d.totalCotaLocal;
    document.getElementById('v-pub').textContent   = d.pubTotal;
    document.getElementById('v-priv').textContent  = d.privTotal;

    // Gráficos
    updateChart(charts.curso,   d.cursoLabels,    d.cursoValues);
    updateChart(charts.proc,    d.procLabels,     d.procValues);
    updateChart(charts.genero,  d.generoLabels,   d.generoValues);
    updateChart(charts.ranking, d.rankLabels,     d.rankValues);
    updateChart(charts.media,   d.mediaCursoLbls, d.mediaCursoVals);
    renderTop(d.topAlunos);

  } catch(e) {
    console.error('Erro ao filtrar:', e);
  } finally {
    setLoading(false);
  }
}

// ── Event listeners ──
let debounce;
['f-curso','f-sexo','f-proc'].forEach(id => {
  document.getElementById(id).addEventListener('change', () => {
    clearTimeout(debounce);
    debounce = setTimeout(aplicarFiltros, 150);
  });
});

document.getElementById('btn-reset-filtros').addEventListener('click', () => {
  document.getElementById('f-curso').value = 'todos';
  document.getElementById('f-sexo').value  = 'todos';
  document.getElementById('f-proc').value  = 'todos';
  aplicarFiltros();
});
</script>
</body>
</html>