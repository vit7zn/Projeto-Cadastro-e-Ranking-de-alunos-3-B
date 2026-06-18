<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.HTML?erro=acesso_negado");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost"; $user = "root"; $pass = ""; $dbname = "sistema_login";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Erro: " . $conn->connect_error);

$filtroCurso = $_GET['curso'] ?? 'todos';
$filtroSexo  = $_GET['sexo']  ?? 'todos';
$filtroProc  = $_GET['proc']  ?? 'todos';

$where = [];
if ($filtroCurso !== 'todos') $where[] = "curso = '" . $conn->real_escape_string($filtroCurso) . "'";
if ($filtroSexo  !== 'todos') $where[] = "sexo LIKE '%" . $conn->real_escape_string($filtroSexo) . "%'";
if ($filtroProc  !== 'todos') $where[] = "procedencia_escolar = '" . $conn->real_escape_string($filtroProc) . "'";
$wClause = count($where) ? "WHERE " . implode(" AND ", $where) : "";

function q($conn, $sql) { $r = $conn->query($sql); return $r ?: null; }

// Cards principais
$totalCadastros = q($conn, "SELECT COUNT(*) c FROM alunos $wClause")?->fetch_assoc()['c'] ?? 0;
$mediaGeral     = q($conn, "SELECT ROUND(AVG(media_geral),2) m FROM alunos $wClause")?->fetch_assoc()['m'] ?? 0;
$totalPCD       = q($conn, "SELECT COUNT(*) c FROM alunos " . ($wClause ? "$wClause AND" : "WHERE") . " pcd='sim'")?->fetch_assoc()['c'] ?? 0;
$totalCotaLocal = q($conn, "SELECT COUNT(*) c FROM alunos " . ($wClause ? "$wClause AND" : "WHERE") . " cota_local='sim'")?->fetch_assoc()['c'] ?? 0;

// Aprovação / Reprovação (considera aprovado quem tem média >= 6)
$totalAprovados  = q($conn, "SELECT COUNT(*) c FROM alunos " . ($wClause ? "$wClause AND" : "WHERE") . " media_geral >= 6")?->fetch_assoc()['c'] ?? 0;
$totalReprovados = q($conn, "SELECT COUNT(*) c FROM alunos " . ($wClause ? "$wClause AND" : "WHERE") . " media_geral < 6 AND media_geral IS NOT NULL")?->fetch_assoc()['c'] ?? 0;

// Maior e menor média
$maiorMedia = q($conn, "SELECT MAX(media_geral) m FROM alunos $wClause")?->fetch_assoc()['m'] ?? 0;
$menorMedia = q($conn, "SELECT MIN(media_geral) m FROM alunos " . ($wClause ? "$wClause AND" : "WHERE") . " media_geral IS NOT NULL")?->fetch_assoc()['m'] ?? 0;

// Procedência
$procData = ['Pública'=>0,'Privada'=>0];
$r = q($conn, "SELECT procedencia_escolar, COUNT(*) qtd FROM alunos $wClause GROUP BY procedencia_escolar");
if ($r) while ($row = $r->fetch_assoc()) {
    if ($row['procedencia_escolar']==='publica') $procData['Pública']=$row['qtd'];
    else $procData['Privada']=$row['qtd'];
}

// Gênero
$generoData = ['Masculino'=>0,'Feminino'=>0,'Outro'=>0];
$r = q($conn, "SELECT sexo, COUNT(*) qtd FROM alunos $wClause GROUP BY sexo");
if ($r) while ($row = $r->fetch_assoc()) {
    $s = strtolower($row['sexo'] ?? '');
    if (str_contains($s,'masc')) $generoData['Masculino'] += $row['qtd'];
    elseif (str_contains($s,'fem')) $generoData['Feminino'] += $row['qtd'];
    else $generoData['Outro'] += $row['qtd'];
}

// Cursos
$cursoData = [];
$r = q($conn, "SELECT curso, COUNT(*) qtd FROM alunos $wClause GROUP BY curso ORDER BY qtd DESC");
if ($r) while ($row = $r->fetch_assoc()) $cursoData[$row['curso'] ?: 'N/D'] = $row['qtd'];

// Rankings
$rankingData = [];
$r = q($conn, "SELECT categoria_ranking, COUNT(*) qtd FROM alunos $wClause GROUP BY categoria_ranking ORDER BY qtd DESC");
if ($r) while ($row = $r->fetch_assoc()) $rankingData[$row['categoria_ranking'] ?: 'N/D'] = $row['qtd'];

// Média por curso
$mediaCursoData = [];
$r = q($conn, "SELECT curso, ROUND(AVG(media_geral),2) media FROM alunos $wClause GROUP BY curso ORDER BY media DESC");
if ($r) while ($row = $r->fetch_assoc()) $mediaCursoData[$row['curso'] ?: 'N/D'] = $row['media'];

// Top 10
$topAlunos = [];
$wTop = count($where)
    ? "WHERE " . implode(" AND ", $where) . " AND media_geral IS NOT NULL"
    : "WHERE media_geral IS NOT NULL";
$r = q($conn, "SELECT nome_completo, media_geral, categoria_ranking, curso, sexo FROM alunos $wTop ORDER BY media_geral DESC LIMIT 10");
if ($r) while ($row = $r->fetch_assoc()) $topAlunos[] = $row;

// Distribuição de faixas de média
$faixasMedia = [
    '9–10' => 0, '8–9' => 0, '7–8' => 0, '6–7' => 0, 'Abaixo de 6' => 0
];
$r = q($conn, "SELECT media_geral FROM alunos " . ($wClause ? "$wClause AND" : "WHERE") . " media_geral IS NOT NULL");
if ($r) while ($row = $r->fetch_assoc()) {
    $m = (float)$row['media_geral'];
    if ($m >= 9)      $faixasMedia['9–10']++;
    elseif ($m >= 8)  $faixasMedia['8–9']++;
    elseif ($m >= 7)  $faixasMedia['7–8']++;
    elseif ($m >= 6)  $faixasMedia['6–7']++;
    else              $faixasMedia['Abaixo de 6']++;
}

// Desvio padrão da média
$desvioPadrao = q($conn, "SELECT ROUND(STDDEV(media_geral),2) dp FROM alunos $wClause")?->fetch_assoc()['dp'] ?? 0;

// Cursos disponíveis para filtro
$cursos = [];
$r = q($conn, "SELECT DISTINCT curso FROM alunos WHERE curso IS NOT NULL ORDER BY curso");
if ($r) while ($row = $r->fetch_assoc()) $cursos[] = $row['curso'];

$nomeUsuario = $_SESSION['nome'] ?? 'Secretária';
$taxaAprovacao = $totalCadastros > 0 ? round(($totalAprovados / $totalCadastros) * 100, 1) : 0;
$taxaPCD       = $totalCadastros > 0 ? round(($totalPCD / $totalCadastros) * 100, 1) : 0;
$conn->close();

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'totalCadastros' => $totalCadastros,
        'mediaGeral'     => number_format($mediaGeral, 1, ',', '.'),
        'totalPCD'       => $totalPCD,
        'totalCotaLocal' => $totalCotaLocal,
        'totalAprovados' => $totalAprovados,
        'totalReprovados'=> $totalReprovados,
        'maiorMedia'     => number_format($maiorMedia, 2, ',', '.'),
        'menorMedia'     => number_format($menorMedia, 2, ',', '.'),
        'desvioPadrao'   => number_format($desvioPadrao, 2, ',', '.'),
        'taxaAprovacao'  => $taxaAprovacao,
        'taxaPCD'        => $taxaPCD,
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
        'faixasLabels'   => array_keys($faixasMedia),
        'faixasValues'   => array_values($faixasMedia),
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
<title>Dashboard — SIPS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ══════════════════════════════════════
   TOKENS
══════════════════════════════════════ */
:root {
  --green-dark:#0f4d00;
  --green-main:#1b8a00;
  --green-light:#23a800;
  --green-muted:#d1fae5;
  --orange:#e67e22;
  --blue:#3b82f6;
  --violet:#8b5cf6;
  --teal:#0ea5e9;
  --pink:#ec4899;
  --yellow:#f59e0b;
  --red:#ef4444;
  --bg:#eef1ee;
  --card:#ffffff;
  --card2:#f8faf8;
  --border:#e0e7e0;
  --text:#111827;
  --muted:#6b7280;
  --shadow:0 1px 4px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.06);
  --shadow-lg:0 8px 30px rgba(0,0,0,.12);
  --radius:16px;
  --radius-sm:10px;
}

/* ══════════════════════════════════════
   RESET / BASE
══════════════════════════════════════ */
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body {
  background:var(--bg);
  font-family:'Outfit',sans-serif;
  color:var(--text);
  min-height:100vh;
}

/* ══════════════════════════════════════
   LAYOUT WRAPPER
══════════════════════════════════════ */
.dash-wrapper {
  padding:28px 32px;
  max-width:1440px;
  margin:0 auto;
}

/* ══════════════════════════════════════
   PAGE HEADER
══════════════════════════════════════ */
.page-header {
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  margin-bottom:24px;
  flex-wrap:wrap;
  gap:14px;
}
.page-header-left h1 {
  font-size:1.75rem;
  font-weight:800;
  color:var(--green-dark);
  letter-spacing:-.5px;
  display:flex;
  align-items:center;
  gap:10px;
}
.page-header-left p {
  color:var(--muted);
  font-size:.88rem;
  margin-top:4px;
}
.header-meta {
  display:flex;
  align-items:center;
  gap:10px;
  flex-wrap:wrap;
}
.live-badge {
  display:flex;
  align-items:center;
  gap:6px;
  background:#dcfce7;
  color:#166534;
  font-size:.75rem;
  font-weight:700;
  padding:5px 12px;
  border-radius:20px;
  letter-spacing:.3px;
}
.live-dot {
  width:8px; height:8px;
  background:#16a34a;
  border-radius:50%;
  animation:pulse-dot 1.8s ease infinite;
}
.last-update {
  font-size:.78rem;
  color:var(--muted);
  background:var(--card);
  padding:5px 12px;
  border-radius:20px;
  border:1.5px solid var(--border);
}

/* ══════════════════════════════════════
   FILTER BAR
══════════════════════════════════════ */
.filter-bar {
  background:var(--card);
  border-radius:var(--radius);
  padding:14px 20px;
  box-shadow:var(--shadow);
  margin-bottom:24px;
  display:flex;
  flex-wrap:wrap;
  align-items:center;
  gap:12px;
  border:1.5px solid var(--border);
}
.filter-label-main {
  font-size:.78rem;
  font-weight:800;
  color:var(--green-dark);
  text-transform:uppercase;
  letter-spacing:.5px;
  display:flex;
  align-items:center;
  gap:6px;
}
.filter-divider { width:1px; height:24px; background:var(--border); }
.filter-group { display:flex; align-items:center; gap:7px; }
.filter-group label {
  font-size:.75rem;
  font-weight:700;
  color:var(--muted);
  text-transform:uppercase;
  letter-spacing:.4px;
}
.filter-select {
  padding:7px 14px;
  border:1.5px solid var(--border);
  border-radius:25px;
  font-size:.84rem;
  font-family:'Outfit',sans-serif;
  color:var(--text);
  background:var(--bg);
  cursor:pointer;
  outline:none;
  transition:border-color .2s, box-shadow .2s;
  -webkit-appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M0 0l5 6 5-6H0z' fill='%236b7280'/%3E%3C/svg%3E");
  background-repeat:no-repeat;
  background-position:right 12px center;
  padding-right:30px;
}
.filter-select:focus, .filter-select:hover {
  border-color:var(--green-main);
  box-shadow:0 0 0 3px rgba(27,138,0,.1);
}
.filter-actions { display:flex; gap:8px; margin-left:auto; align-items:center; }
.btn-reset {
  padding:7px 16px;
  border:1.5px solid var(--border);
  border-radius:25px;
  background:#fff;
  font-size:.82rem;
  font-family:'Outfit',sans-serif;
  color:var(--muted);
  cursor:pointer;
  transition:all .2s;
  display:flex; align-items:center; gap:5px;
}
.btn-reset:hover { border-color:#e53935; color:#e53935; background:#fff5f5; }
.btn-export {
  padding:7px 16px;
  border:1.5px solid var(--green-main);
  border-radius:25px;
  background:var(--green-main);
  font-size:.82rem;
  font-family:'Outfit',sans-serif;
  color:#fff;
  cursor:pointer;
  transition:all .2s;
  display:flex; align-items:center; gap:5px;
  font-weight:600;
}
.btn-export:hover { background:var(--green-dark); border-color:var(--green-dark); }
.filter-active-badge {
  background:#dcfce7;
  color:#166534;
  padding:4px 10px;
  border-radius:20px;
  font-size:.73rem;
  font-weight:700;
  display:none;
}
.filter-active-badge.show { display:inline-flex; align-items:center; gap:4px; }

/* ══════════════════════════════════════
   KPI CARDS ROW 1 — PRINCIPAIS
══════════════════════════════════════ */
.kpi-grid-main {
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(170px, 1fr));
  gap:14px;
  margin-bottom:14px;
}
.kpi-card {
  background:var(--card);
  border-radius:var(--radius);
  padding:20px 22px;
  box-shadow:var(--shadow);
  border-top:4px solid var(--green-main);
  transition:transform .2s, box-shadow .2s;
  animation:fadeUp .4s ease both;
  position:relative;
  overflow:hidden;
}
.kpi-card::after {
  content:'';
  position:absolute;
  right:-18px; top:-18px;
  width:70px; height:70px;
  border-radius:50%;
  background:currentColor;
  opacity:.05;
}
.kpi-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-lg); }
.kpi-card.green  { border-top-color:var(--green-main); }
.kpi-card.orange { border-top-color:var(--orange); }
.kpi-card.teal   { border-top-color:var(--teal); }
.kpi-card.violet { border-top-color:var(--violet); }
.kpi-card.blue   { border-top-color:var(--blue); }
.kpi-card.pink   { border-top-color:var(--pink); }
.kpi-card.red    { border-top-color:var(--red); }
.kpi-card.yellow { border-top-color:var(--yellow); }

.kpi-label {
  font-size:.72rem;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.5px;
  color:var(--muted);
  display:flex;
  align-items:center;
  gap:6px;
  margin-bottom:8px;
}
.kpi-value {
  font-size:2.4rem;
  font-weight:900;
  line-height:1;
  letter-spacing:-1px;
}
.kpi-sub {
  font-size:.74rem;
  color:var(--muted);
  margin-top:6px;
  display:flex;
  align-items:center;
  gap:5px;
}
.kpi-trend {
  display:inline-flex;
  align-items:center;
  gap:3px;
  font-size:.72rem;
  font-weight:700;
  padding:2px 7px;
  border-radius:20px;
}
.kpi-trend.up { background:#dcfce7; color:#16a34a; }
.kpi-trend.down { background:#fee2e2; color:#dc2626; }
.kpi-trend.neutral { background:#f3f4f6; color:#6b7280; }

/* ══════════════════════════════════════
   KPI CARDS ROW 2 — INDICADORES
══════════════════════════════════════ */
.kpi-grid-secondary {
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
  gap:14px;
  margin-bottom:24px;
}
.indicator-card {
  background:var(--card);
  border-radius:var(--radius);
  padding:18px 20px;
  box-shadow:var(--shadow);
  border:1.5px solid var(--border);
  animation:fadeUp .45s ease both;
}
.indicator-header {
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:12px;
}
.indicator-title {
  font-size:.76rem;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.4px;
  color:var(--muted);
}
.indicator-icon {
  width:32px; height:32px;
  border-radius:8px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:1rem;
}
.indicator-icon.green { background:#dcfce7; }
.indicator-icon.orange { background:#ffedd5; }
.indicator-icon.blue { background:#dbeafe; }
.indicator-icon.violet { background:#ede9fe; }

.progress-label {
  display:flex;
  justify-content:space-between;
  font-size:.8rem;
  font-weight:600;
  margin-bottom:6px;
}
.progress-track {
  width:100%;
  height:8px;
  background:var(--border);
  border-radius:99px;
  overflow:hidden;
}
.progress-fill {
  height:100%;
  border-radius:99px;
  transition:width .8s cubic-bezier(.4,0,.2,1);
}
.progress-fill.green  { background:linear-gradient(90deg,#1b8a00,#23a800); }
.progress-fill.orange { background:linear-gradient(90deg,#f97316,#e67e22); }
.progress-fill.blue   { background:linear-gradient(90deg,#2563eb,#3b82f6); }
.progress-fill.violet { background:linear-gradient(90deg,#7c3aed,#8b5cf6); }
.progress-meta {
  display:flex;
  justify-content:space-between;
  font-size:.72rem;
  color:var(--muted);
  margin-top:5px;
}

/* ══════════════════════════════════════
   DUAL PANEL — CANVAS CHARTS
══════════════════════════════════════ */
.charts-grid {
  display:grid;
  grid-template-columns:repeat(12,1fr);
  gap:16px;
}
.col-3  { grid-column:span 3; }
.col-4  { grid-column:span 4; }
.col-5  { grid-column:span 5; }
.col-6  { grid-column:span 6; }
.col-7  { grid-column:span 7; }
.col-8  { grid-column:span 8; }
.col-9  { grid-column:span 9; }
.col-12 { grid-column:span 12; }

.chart-card {
  background:var(--card);
  border-radius:var(--radius);
  padding:20px 22px;
  box-shadow:var(--shadow);
  animation:fadeUp .5s ease both;
  border:1.5px solid var(--border);
  position:relative;
}
.chart-card-header {
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:16px;
  padding-bottom:12px;
  border-bottom:1.5px solid var(--border);
}
.chart-card-title {
  font-size:.82rem;
  font-weight:700;
  text-transform:uppercase;
  letter-spacing:.4px;
  color:var(--text);
  display:flex;
  align-items:center;
  gap:7px;
}
.chart-badge {
  font-size:.7rem;
  font-weight:700;
  padding:2px 8px;
  border-radius:20px;
  background:#f3f4f6;
  color:var(--muted);
}
.h-wrap { position:relative; }
.h200 { height:200px; }
.h240 { height:240px; }
.h280 { height:280px; }
.h320 { height:320px; }

/* ══════════════════════════════════════
   DONUT CARD COM LEGENDA LATERAL
══════════════════════════════════════ */
.donut-split {
  display:flex;
  align-items:center;
  gap:16px;
}
.donut-wrap {
  flex:0 0 140px;
  height:140px;
  position:relative;
}
.donut-legend {
  flex:1;
  display:flex;
  flex-direction:column;
  gap:8px;
}
.legend-item {
  display:flex;
  align-items:center;
  gap:8px;
}
.legend-dot {
  width:10px; height:10px;
  border-radius:50%;
  flex-shrink:0;
}
.legend-text {
  font-size:.8rem;
  flex:1;
}
.legend-val {
  font-size:.8rem;
  font-weight:700;
}

/* ══════════════════════════════════════
   TABELA TOP 10
══════════════════════════════════════ */
.top-table {
  width:100%;
  border-collapse:collapse;
  font-size:.84rem;
}
.top-table thead tr {
  background:var(--card2);
}
.top-table th {
  text-align:left;
  padding:10px 14px;
  color:var(--muted);
  font-weight:700;
  font-size:.71rem;
  text-transform:uppercase;
  letter-spacing:.4px;
  border-bottom:2px solid var(--border);
}
.top-table td {
  padding:10px 14px;
  border-bottom:1px solid var(--border);
  vertical-align:middle;
}
.top-table tr:last-child td { border-bottom:none; }
.top-table tbody tr { transition:background .15s; }
.top-table tbody tr:hover td { background:#f0fdf4; }

.medal { font-size:.95rem; }
.badge {
  display:inline-block;
  padding:3px 9px;
  border-radius:20px;
  font-size:.7rem;
  font-weight:700;
}
.badge-green  { background:#dcfce7; color:#166534; }
.badge-blue   { background:#dbeafe; color:#1e40af; }
.badge-violet { background:#ede9fe; color:#5b21b6; }
.badge-orange { background:#ffedd5; color:#9a3412; }
.badge-pink   { background:#fce7f3; color:#9d174d; }

.score-bar-wrap {
  display:flex;
  align-items:center;
  gap:8px;
}
.score-mini-bar {
  flex:1;
  height:5px;
  background:var(--border);
  border-radius:99px;
  overflow:hidden;
}
.score-mini-fill {
  height:100%;
  border-radius:99px;
  background:linear-gradient(90deg,#1b8a00,#23a800);
}

/* ══════════════════════════════════════
   STAT PILLS INLINE
══════════════════════════════════════ */
.stat-pill-row {
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-bottom:24px;
}
.stat-pill {
  display:flex;
  align-items:center;
  gap:7px;
  background:var(--card);
  border:1.5px solid var(--border);
  padding:7px 14px;
  border-radius:99px;
  font-size:.8rem;
  font-weight:600;
  box-shadow:var(--shadow);
  animation:fadeUp .3s ease both;
}
.stat-pill-icon { font-size:.95rem; }
.stat-pill-label { color:var(--muted); font-weight:500; }
.stat-pill-val { font-weight:800; }
.stat-pill-val.green { color:var(--green-main); }
.stat-pill-val.orange { color:var(--orange); }
.stat-pill-val.blue { color:var(--blue); }
.stat-pill-val.violet { color:var(--violet); }
.stat-pill-val.red { color:var(--red); }

/* ══════════════════════════════════════
   MINI SPARKLINE (SVG inline)
══════════════════════════════════════ */
.sparkline-row {
  display:flex;
  gap:6px;
  margin-top:10px;
}
.spark-bar {
  flex:1;
  border-radius:3px 3px 0 0;
  background:var(--green-muted);
  transition:height .4s ease, background .2s;
  align-self:flex-end;
}
.spark-bar:hover { background:var(--green-main); }
.sparkline-wrap {
  height:36px;
  display:flex;
  align-items:flex-end;
}

/* ══════════════════════════════════════
   FAIXAS DE MÉDIA — HORIZONTAL BARS
══════════════════════════════════════ */
.faixa-list { display:flex; flex-direction:column; gap:10px; }
.faixa-item {}
.faixa-header {
  display:flex;
  justify-content:space-between;
  font-size:.79rem;
  margin-bottom:4px;
}
.faixa-label { font-weight:600; }
.faixa-val { font-weight:700; color:var(--muted); }
.faixa-bar-wrap {
  height:9px;
  background:var(--border);
  border-radius:99px;
  overflow:hidden;
}
.faixa-bar-fill {
  height:100%;
  border-radius:99px;
  transition:width .9s cubic-bezier(.4,0,.2,1);
}

/* ══════════════════════════════════════
   CURSO TABLE
══════════════════════════════════════ */
.curso-table { width:100%; border-collapse:collapse; font-size:.83rem; }
.curso-table tr { border-bottom:1px solid var(--border); }
.curso-table tr:last-child { border-bottom:none; }
.curso-table td { padding:8px 4px; vertical-align:middle; }
.curso-table td:first-child { font-weight:600; width:140px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:140px; }
.curso-table td:last-child { width:45px; text-align:right; font-weight:700; font-size:.82rem; color:var(--muted); }
.curso-bar { height:8px; background:var(--border); border-radius:99px; overflow:hidden; }
.curso-bar-fill { height:100%; border-radius:99px; background:linear-gradient(90deg,#1b8a00,#23a800); }

/* ══════════════════════════════════════
   LOADING
══════════════════════════════════════ */
.loading-overlay {
  position:absolute; inset:0;
  background:rgba(255,255,255,.8);
  backdrop-filter:blur(2px);
  border-radius:var(--radius);
  display:none;
  align-items:center;
  justify-content:center;
  z-index:10;
}
.loading-overlay.ativo { display:flex; }
.spinner {
  width:28px; height:28px;
  border:3px solid var(--border);
  border-top-color:var(--green-main);
  border-radius:50%;
  animation:spin .7s linear infinite;
}

/* ══════════════════════════════════════
   EMPTY
══════════════════════════════════════ */
.empty-state {
  display:flex; flex-direction:column;
  align-items:center; justify-content:center;
  height:160px; color:var(--muted);
  font-size:.88rem; gap:8px;
}
.empty-state span { font-size:2.2rem; }

/* ══════════════════════════════════════
   SECTION DIVIDER
══════════════════════════════════════ */
.section-sep {
  display:flex;
  align-items:center;
  gap:12px;
  margin:24px 0 16px;
}
.section-sep-label {
  font-size:.75rem;
  font-weight:800;
  text-transform:uppercase;
  letter-spacing:.8px;
  color:var(--muted);
  white-space:nowrap;
}
.section-sep-line {
  flex:1;
  height:1px;
  background:var(--border);
}

/* ══════════════════════════════════════
   WHATSAPP BUTTON
══════════════════════════════════════ */
.nav-link-whatsapp {
  background:linear-gradient(135deg,#25D366,#128C7E) !important;
  color:#fff !important;
  padding:7px 16px !important;
  border-radius:20px !important;
  font-weight:700 !important;
  font-size:.83rem !important;
  box-shadow:0 2px 8px rgba(37,211,102,.35);
  transition:opacity .2s, transform .15s, box-shadow .2s !important;
}
.nav-link-whatsapp:hover {
  opacity:.9 !important;
  transform:translateY(-1px) !important;
  box-shadow:0 4px 14px rgba(37,211,102,.45) !important;
}

/* ══════════════════════════════════════
   ANIMATIONS
══════════════════════════════════════ */
@keyframes fadeUp {
  from { opacity:0; transform:translateY(16px); }
  to   { opacity:1; transform:translateY(0); }
}
@keyframes spin { to { transform:rotate(360deg); } }
@keyframes pulse-dot {
  0%,100% { box-shadow:0 0 0 0 rgba(22,163,74,.5); }
  50%      { box-shadow:0 0 0 5px rgba(22,163,74,0); }
}

.kpi-card:nth-child(1) { animation-delay:.04s }
.kpi-card:nth-child(2) { animation-delay:.08s }
.kpi-card:nth-child(3) { animation-delay:.12s }
.kpi-card:nth-child(4) { animation-delay:.16s }
.kpi-card:nth-child(5) { animation-delay:.20s }
.kpi-card:nth-child(6) { animation-delay:.24s }
.kpi-card:nth-child(7) { animation-delay:.28s }
.kpi-card:nth-child(8) { animation-delay:.32s }

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media(max-width:1100px) {
  .col-3,.col-4,.col-5 { grid-column:span 6; }
  .col-7,.col-8,.col-9 { grid-column:span 12; }
}
@media(max-width:768px) {
  .col-3,.col-4,.col-5,.col-6,.col-7,.col-8,.col-9 { grid-column:span 12; }
  .dash-wrapper { padding:12px 14px; }
  .kpi-value { font-size:2rem; }
}
@media(max-width:500px) {
  .kpi-grid-main { grid-template-columns:repeat(2,1fr); }
  .stat-pill-row { display:none; }
}
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
  <div class="sidebar-footer">SIPS © 2025</div>
</nav>

<nav class="navbar">
  <div class="navbar-left">
    <button class="btn-hamburguer" id="btn-hamburguer" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <a class="navbar-brand">
      <img src="logo_sips.svg" alt="Logo">
      SIPS
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

  <!-- ── PAGE HEADER ── -->
  <div class="page-header">
    <div class="page-header-left">
      <h1>📊 Dashboard</h1>
      <p>Visão analítica dos cadastros e desempenho — <?= htmlspecialchars($nomeUsuario) ?></p>
    </div>
    <div class="header-meta">
      <span class="live-badge"><span class="live-dot"></span>Ao vivo</span>
      <span class="last-update" id="last-update">⏱ Atualizado agora</span>
    </div>
  </div>

  <!-- ── FILTROS ── -->
  <div class="filter-bar">
    <span class="filter-label-main">🔍 Filtros</span>
    <div class="filter-divider"></div>

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
      <label for="f-sexo">Gênero</label>
      <select class="filter-select" id="f-sexo">
        <option value="todos">Todos</option>
        <option value="masc" <?= $filtroSexo==='masc'?'selected':'' ?>>Masculino</option>
        <option value="fem"  <?= $filtroSexo==='fem'?'selected':'' ?>>Feminino</option>
      </select>
    </div>

    <div class="filter-group">
      <label for="f-proc">Procedência</label>
      <select class="filter-select" id="f-proc">
        <option value="todos">Todas</option>
        <option value="publica"  <?= $filtroProc==='publica'?'selected':'' ?>>Escola Pública</option>
        <option value="privada"  <?= $filtroProc==='privada'?'selected':'' ?>>Escola Privada</option>
      </select>
    </div>

    <div class="filter-actions">
      <span class="filter-active-badge" id="badge-filtro">✦ Filtro ativo</span>
      <button class="btn-reset" id="btn-reset-filtros">✕ Limpar</button>
      <button class="btn-export" onclick="exportarCSV()">⬇ Exportar</button>
    </div>
  </div>

  <!-- ── KPI CARDS — LINHA 1 ── -->
  <div class="section-sep">
    <span class="section-sep-label">Métricas Principais</span>
    <div class="section-sep-line"></div>
  </div>

  <div class="kpi-grid-main">
    <div class="kpi-card">
      <div class="kpi-label">🎓 Total de Alunos</div>
      <div class="kpi-value" id="v-total"><?= number_format($totalCadastros) ?></div>
      <div class="kpi-sub">
        <span>cadastros registrados</span>
      </div>
    </div>
    <div class="kpi-card orange">
      <div class="kpi-label">📊 Média Geral</div>
      <div class="kpi-value" id="v-media"><?= number_format($mediaGeral,1,',','.') ?></div>
      <div class="kpi-sub">
        <span>de 10 pontos</span>
        <span class="kpi-trend neutral" id="v-dp">σ <?= number_format($desvioPadrao,2,',','.') ?></span>
      </div>
    </div>
    <div class="kpi-card blue">
      <div class="kpi-label">✅ Aprovados</div>
      <div class="kpi-value" id="v-aprovados"><?= $totalAprovados ?></div>
      <div class="kpi-sub">
        <span class="kpi-trend up" id="v-taxa-ap"><?= $taxaAprovacao ?>% aprovação</span>
      </div>
    </div>
    <div class="kpi-card red">
      <div class="kpi-label">❌ Reprovados</div>
      <div class="kpi-value" id="v-reprovados"><?= $totalReprovados ?></div>
      <div class="kpi-sub">
        <span>com média abaixo de 6</span>
      </div>
    </div>
    <div class="kpi-card teal">
      <div class="kpi-label">♿ PCD</div>
      <div class="kpi-value" id="v-pcd"><?= $totalPCD ?></div>
      <div class="kpi-sub">
        <span class="kpi-trend neutral" id="v-taxa-pcd"><?= $taxaPCD ?>% do total</span>
      </div>
    </div>
    <div class="kpi-card violet">
      <div class="kpi-label">📍 Cota Local</div>
      <div class="kpi-value" id="v-cota"><?= $totalCotaLocal ?></div>
      <div class="kpi-sub"><span>bairro Venâncios</span></div>
    </div>
    <div class="kpi-card green">
      <div class="kpi-label">🔝 Maior Média</div>
      <div class="kpi-value" style="font-size:1.8rem" id="v-maior"><?= number_format($maiorMedia,2,',','.') ?></div>
      <div class="kpi-sub"><span>top da turma</span></div>
    </div>
    <div class="kpi-card yellow">
      <div class="kpi-label">📉 Menor Média</div>
      <div class="kpi-value" style="font-size:1.8rem" id="v-menor"><?= number_format($menorMedia,2,',','.') ?></div>
      <div class="kpi-sub"><span>mínimo registrado</span></div>
    </div>
  </div>

  <!-- ── INDICADORES COM BARRA DE PROGRESSO ── -->
  <div class="section-sep">
    <span class="section-sep-label">Indicadores de Proporção</span>
    <div class="section-sep-line"></div>
  </div>

  <div class="kpi-grid-secondary">
    <div class="indicator-card">
      <div class="indicator-header">
        <span class="indicator-title">Taxa de Aprovação</span>
        <span class="indicator-icon green">✅</span>
      </div>
      <div class="progress-label">
        <span id="ind-ap-v"><?= $taxaAprovacao ?>%</span>
        <span style="font-size:.75rem;color:var(--muted)"><?= $totalAprovados ?> / <?= $totalCadastros ?></span>
      </div>
      <div class="progress-track">
        <div class="progress-fill green" id="bar-ap" style="width:<?= $taxaAprovacao ?>%"></div>
      </div>
      <div class="progress-meta"><span>Aprovados (≥6)</span><span><?= $totalReprovados ?> reprovados</span></div>
    </div>

    <div class="indicator-card">
      <div class="indicator-header">
        <span class="indicator-title">Escola Pública vs Privada</span>
        <span class="indicator-icon orange">🏫</span>
      </div>
      <?php $pctPub = $totalCadastros > 0 ? round(($procData['Pública'] / $totalCadastros)*100,1) : 0; ?>
      <div class="progress-label">
        <span id="ind-pub-v"><?= $pctPub ?>% pública</span>
        <span style="font-size:.75rem;color:var(--muted)"><?= $procData['Pública'] ?> / <?= $procData['Privada'] ?></span>
      </div>
      <div class="progress-track">
        <div class="progress-fill orange" id="bar-pub" style="width:<?= $pctPub ?>%"></div>
      </div>
      <div class="progress-meta">
        <span id="v-pub">Pública: <?= $procData['Pública'] ?></span>
        <span id="v-priv">Privada: <?= $procData['Privada'] ?></span>
      </div>
    </div>

    <div class="indicator-card">
      <div class="indicator-header">
        <span class="indicator-title">Alunos PCD</span>
        <span class="indicator-icon blue">♿</span>
      </div>
      <div class="progress-label">
        <span id="ind-pcd-v"><?= $taxaPCD ?>%</span>
        <span style="font-size:.75rem;color:var(--muted)"><?= $totalPCD ?> alunos</span>
      </div>
      <div class="progress-track">
        <div class="progress-fill blue" id="bar-pcd" style="width:<?= $taxaPCD ?>%"></div>
      </div>
      <div class="progress-meta"><span>Necessidades especiais</span><span>do total geral</span></div>
    </div>

    <div class="indicator-card">
      <div class="indicator-header">
        <span class="indicator-title">Masculino vs Feminino</span>
        <span class="indicator-icon violet">👥</span>
      </div>
      <?php
        $totGen = array_sum($generoData);
        $pctMasc = $totGen > 0 ? round(($generoData['Masculino'] / $totGen)*100, 1) : 0;
      ?>
      <div class="progress-label">
        <span id="ind-masc-v"><?= $pctMasc ?>% masc.</span>
        <span style="font-size:.75rem;color:var(--muted)"><?= $generoData['Masculino'] ?> / <?= $generoData['Feminino'] ?></span>
      </div>
      <div class="progress-track">
        <div class="progress-fill violet" id="bar-masc" style="width:<?= $pctMasc ?>%"></div>
      </div>
      <div class="progress-meta">
        <span>Masc: <?= $generoData['Masculino'] ?></span>
        <span>Fem: <?= $generoData['Feminino'] ?></span>
      </div>
    </div>
  </div>

  <!-- ── GRÁFICOS — LINHA 1 ── -->
  <div class="section-sep">
    <span class="section-sep-label">Distribuição por Curso e Procedência</span>
    <div class="section-sep-line"></div>
  </div>

  <div class="charts-grid" style="margin-bottom:16px">

    <!-- Alunos por Curso — Barra vertical -->
    <div class="chart-card col-8" style="position:relative">
      <div class="chart-card-header">
        <span class="chart-card-title">🎓 Alunos por Curso</span>
        <span class="chart-badge" id="badge-cursos"><?= count($cursoData) ?> cursos</span>
      </div>
      <div class="loading-overlay" id="load-curso"><div class="spinner"></div></div>
      <div class="h-wrap h280"><canvas id="chartCurso"></canvas></div>
    </div>

    <!-- Procedência — Donut com legenda -->
    <div class="chart-card col-4" style="position:relative">
      <div class="chart-card-header">
        <span class="chart-card-title">🏫 Procedência</span>
      </div>
      <div class="loading-overlay" id="load-proc"><div class="spinner"></div></div>
      <div class="donut-split" style="margin-top:10px">
        <div class="donut-wrap">
          <canvas id="chartProcedencia"></canvas>
        </div>
        <div class="donut-legend" id="legend-proc">
          <div class="legend-item">
            <div class="legend-dot" style="background:#1b8a00"></div>
            <span class="legend-text">Pública</span>
            <span class="legend-val" id="leg-pub"><?= $procData['Pública'] ?></span>
          </div>
          <div class="legend-item">
            <div class="legend-dot" style="background:#e67e22"></div>
            <span class="legend-text">Privada</span>
            <span class="legend-val" id="leg-priv"><?= $procData['Privada'] ?></span>
          </div>
        </div>
      </div>
      <!-- Mini curso table -->
      <div style="margin-top:18px; border-top:1.5px solid var(--border); padding-top:14px">
        <div style="font-size:.73rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);margin-bottom:10px">Top Cursos</div>
        <table class="curso-table" id="curso-mini-table">
          <?php foreach (array_slice($cursoData,0,5,true) as $nome=>$qtd):
            $max = max($cursoData);
            $pct = $max > 0 ? round(($qtd/$max)*100) : 0;
          ?>
          <tr>
            <td><?= htmlspecialchars($nome) ?></td>
            <td><div class="curso-bar"><div class="curso-bar-fill" style="width:<?= $pct ?>%"></div></div></td>
            <td><?= $qtd ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </div>

  <!-- ── GRÁFICOS — LINHA 2 ── -->
  <div class="section-sep">
    <span class="section-sep-label">Gênero, Ranking e Desempenho</span>
    <div class="section-sep-line"></div>
  </div>

  <div class="charts-grid" style="margin-bottom:16px">

    <!-- Gênero — Horizontal bar -->
    <div class="chart-card col-4" style="position:relative">
      <div class="chart-card-header">
        <span class="chart-card-title">👥 Gênero</span>
      </div>
      <div class="loading-overlay" id="load-gen"><div class="spinner"></div></div>
      <div class="h-wrap h200"><canvas id="chartGenero"></canvas></div>
    </div>

    <!-- Categorias Ranking -->
    <div class="chart-card col-4" style="position:relative">
      <div class="chart-card-header">
        <span class="chart-card-title">🏆 Categorias Ranking</span>
      </div>
      <div class="loading-overlay" id="load-rank"><div class="spinner"></div></div>
      <div class="h-wrap h200"><canvas id="chartRanking"></canvas></div>
    </div>

    <!-- Faixas de Média -->
    <div class="chart-card col-4" style="position:relative">
      <div class="chart-card-header">
        <span class="chart-card-title">📊 Faixas de Média</span>
      </div>
      <div class="loading-overlay" id="load-faixas"><div class="spinner"></div></div>
      <div class="faixa-list" id="faixa-list" style="margin-top:4px">
        <?php
          $faixaColors = ['#1b8a00','#3b82f6','#f59e0b','#e67e22','#ef4444'];
          $faixaMax = max($faixasMedia) ?: 1;
          $fi = 0;
          foreach ($faixasMedia as $label => $val):
            $pct = round(($val / $faixaMax) * 100);
        ?>
        <div class="faixa-item">
          <div class="faixa-header">
            <span class="faixa-label"><?= $label ?></span>
            <span class="faixa-val"><?= $val ?></span>
          </div>
          <div class="faixa-bar-wrap">
            <div class="faixa-bar-fill" style="width:<?= $pct ?>%;background:<?= $faixaColors[$fi] ?>"></div>
          </div>
        </div>
        <?php $fi++; endforeach; ?>
      </div>
    </div>

  </div>

  <!-- ── MÉDIA POR CURSO (full width) ── -->
  <div class="section-sep">
    <span class="section-sep-label">Média por Curso</span>
    <div class="section-sep-line"></div>
  </div>

  <div class="charts-grid" style="margin-bottom:16px">
    <div class="chart-card col-12" style="position:relative">
      <div class="chart-card-header">
        <span class="chart-card-title">📈 Comparativo de Média por Curso</span>
        <span class="chart-badge">Escala 0–10</span>
      </div>
      <div class="loading-overlay" id="load-media"><div class="spinner"></div></div>
      <div class="h-wrap h240"><canvas id="chartMediaCurso"></canvas></div>
    </div>
  </div>

  <!-- ── TOP 10 ALUNOS ── -->
  <div class="section-sep">
    <span class="section-sep-label">Ranking de Alunos</span>
    <div class="section-sep-line"></div>
  </div>

  <div class="charts-grid">
    <div class="chart-card col-12" style="position:relative">
      <div class="chart-card-header">
        <span class="chart-card-title">🥇 Top 10 — Melhores Médias</span>
        <span class="chart-badge"><?= count($topAlunos) ?> alunos</span>
      </div>
      <div class="loading-overlay" id="load-top"><div class="spinner"></div></div>
      <div id="top-table-wrap">
        <?php if (!empty($topAlunos)): ?>
        <table class="top-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nome</th>
              <th>Curso</th>
              <th>Género</th>
              <th style="min-width:160px">Média</th>
              <th>Categoria</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $medals=['🥇','🥈','🥉','4º','5º','6º','7º','8º','9º','10º'];
            $badges=['badge-green','badge-blue','badge-orange','badge-violet','badge-pink'];
            foreach($topAlunos as $i=>$a):
              $pctMedia = round(($a['media_geral'] / 10)*100);
          ?>
          <tr>
            <td><span class="medal"><?= $medals[$i] ?></span></td>
            <td><?= htmlspecialchars($a['nome_completo']) ?></td>
            <td><?= htmlspecialchars($a['curso']??'—') ?></td>
            <td><?= htmlspecialchars($a['sexo']??'—') ?></td>
            <td>
              <div class="score-bar-wrap">
                <strong><?= number_format($a['media_geral'],2,',','.') ?></strong>
                <div class="score-mini-bar">
                  <div class="score-mini-fill" style="width:<?= $pctMedia ?>%"></div>
                </div>
              </div>
            </td>
            <td><span class="badge <?= $badges[$i%5] ?>"><?= htmlspecialchars($a['categoria_ranking']??'—') ?></span></td>
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
/* ══════════════════════════════════════
   CORES E CONFIGURAÇÕES GLOBAIS
══════════════════════════════════════ */
const COLORS = {
  green:'#1b8a00', lightG:'#23a800', orange:'#e67e22',
  blue:'#3b82f6', violet:'#8b5cf6', pink:'#ec4899',
  teal:'#0ea5e9', yellow:'#f59e0b', red:'#ef4444', gray:'#9ca3af'
};
const PALETTE = [
  COLORS.green, COLORS.orange, COLORS.blue, COLORS.violet,
  COLORS.pink, COLORS.teal, COLORS.yellow, COLORS.red, COLORS.gray
];
const FONT = { family:'Outfit', size:12 };
Chart.defaults.font = FONT;

/* ══════════════════════════════════════
   DADOS INICIAIS PHP → JS
══════════════════════════════════════ */
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
  faixasLabels:   <?= json_encode(array_keys($faixasMedia)) ?>,
  faixasValues:   <?= json_encode(array_values($faixasMedia)) ?>,
};

/* ══════════════════════════════════════
   CRIAÇÃO DOS GRÁFICOS
══════════════════════════════════════ */
const charts = {};

function mkBar(id, labels, data, colors, indexAxis='x', max=null) {
  const ctx = document.getElementById(id);
  if (!ctx) return null;
  return new Chart(ctx, {
    type:'bar',
    data:{
      labels,
      datasets:[{
        data,
        backgroundColor: colors || PALETTE,
        borderRadius:7,
        borderSkipped:false,
        hoverBackgroundColor: (colors||PALETTE).map(c=>c+'cc'),
      }]
    },
    options:{
      indexAxis, responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{display:false}, tooltip:{
        callbacks:{
          label: ctx => ` ${ctx.parsed[indexAxis==='y'?'x':'y']} alunos`
        }
      }},
      scales:{
        x:{
          grid:{ color: indexAxis==='y' ? '#f0f2f0' : 'transparent' },
          ticks:{ font:FONT },
          ...(max && indexAxis==='y' ? {max} : {})
        },
        y:{
          grid:{ color: indexAxis==='x' ? '#f0f2f0' : 'transparent' },
          ticks:{ font:FONT },
          ...(max && indexAxis==='x' ? {max} : {})
        }
      },
      animation:{ duration:600, easing:'easeOutQuart' }
    }
  });
}

function mkDoughnut(id, labels, data) {
  const ctx = document.getElementById(id);
  if (!ctx) return null;
  return new Chart(ctx, {
    type:'doughnut',
    data:{
      labels,
      datasets:[{
        data,
        backgroundColor:[COLORS.green, COLORS.orange],
        borderWidth:3,
        borderColor:'#fff',
        hoverOffset:6
      }]
    },
    options:{
      responsive:true, maintainAspectRatio:false, cutout:'65%',
      plugins:{ legend:{display:false} },
      animation:{ duration:700, easing:'easeOutQuart' }
    }
  });
}

function mkMediaBar(id, labels, data) {
  const ctx = document.getElementById(id);
  if (!ctx) return null;
  return new Chart(ctx, {
    type:'bar',
    data:{
      labels,
      datasets:[{
        label:'Média',
        data,
        backgroundColor: data.map(v => v >= 7 ? COLORS.green+'cc' : v >= 6 ? COLORS.yellow+'cc' : COLORS.red+'cc'),
        borderRadius:6,
        borderSkipped:false,
        borderWidth:0,
      }]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{
        legend:{display:false},
        tooltip:{
          callbacks:{
            label: ctx => ` Média: ${parseFloat(ctx.parsed.y).toFixed(2).replace('.',',')}`
          }
        }
      },
      scales:{
        x:{ grid:{display:false}, ticks:{font:FONT} },
        y:{ min:0, max:10, grid:{color:'#f0f2f0'}, ticks:{font:FONT,
          callback: v => v.toFixed(0)
        }}
      },
      animation:{ duration:700, easing:'easeOutQuart' }
    }
  });
}

charts.curso   = mkBar('chartCurso', initData.cursoLabels, initData.cursoValues, PALETTE);
charts.proc    = mkDoughnut('chartProcedencia', initData.procLabels, initData.procValues);
charts.genero  = mkBar('chartGenero', initData.generoLabels, initData.generoValues,
                  [COLORS.blue, COLORS.pink, COLORS.teal], 'y');
charts.ranking = mkBar('chartRanking', initData.rankLabels, initData.rankValues, PALETTE);
charts.media   = mkMediaBar('chartMediaCurso', initData.mediaCursoLbls, initData.mediaCursoVals);

/* ══════════════════════════════════════
   UPDATE HELPERS
══════════════════════════════════════ */
function updateChart(chart, labels, data, colorFn) {
  if (!chart) return;
  chart.data.labels = labels;
  chart.data.datasets[0].data = data;
  if (colorFn) {
    chart.data.datasets[0].backgroundColor = colorFn(data);
  }
  chart.update();
}

function updateDoughnut(chart, labels, data) {
  if (!chart) return;
  chart.data.labels = labels;
  chart.data.datasets[0].data = data;
  chart.update();
}

function updateBarWithPalette(chart, labels, data, colors) {
  if (!chart) return;
  chart.data.labels = labels;
  chart.data.datasets[0].data = data;
  chart.data.datasets[0].backgroundColor = colors || PALETTE;
  chart.update();
}

function setEl(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

function setWidth(id, pct) {
  const el = document.getElementById(id);
  if (el) el.style.width = Math.min(100, Math.max(0, pct)) + '%';
}

function renderTop(alunos) {
  const medals=['🥇','🥈','🥉','4º','5º','6º','7º','8º','9º','10º'];
  const badges=['badge-green','badge-blue','badge-orange','badge-violet','badge-pink'];
  const wrap = document.getElementById('top-table-wrap');
  if (!alunos.length) {
    wrap.innerHTML = '<div class="empty-state"><span>📭</span>Nenhum aluno encontrado</div>';
    return;
  }
  let html = `<table class="top-table">
    <thead><tr><th>#</th><th>Nome</th><th>Curso</th><th>Gênero</th><th style="min-width:160px">Média</th><th>Categoria</th></tr></thead>
    <tbody>`;
  alunos.forEach((a,i) => {
    const m = parseFloat(a.media_geral);
    const media = m.toFixed(2).replace('.',',');
    const pct = Math.round((m/10)*100);
    html += `<tr>
      <td><span class="medal">${medals[i]||i+1+'º'}</span></td>
      <td>${a.nome_completo}</td>
      <td>${a.curso||'—'}</td>
      <td>${a.sexo||'—'}</td>
      <td>
        <div class="score-bar-wrap">
          <strong>${media}</strong>
          <div class="score-mini-bar"><div class="score-mini-fill" style="width:${pct}%"></div></div>
        </div>
      </td>
      <td><span class="badge ${badges[i%5]}">${a.categoria_ranking||'—'}</span></td>
    </tr>`;
  });
  html += '</tbody></table>';
  wrap.innerHTML = html;
}

function renderFaixas(labels, values) {
  const colors = ['#1b8a00','#3b82f6','#f59e0b','#e67e22','#ef4444'];
  const max = Math.max(...values) || 1;
  let html = '';
  labels.forEach((l,i) => {
    const pct = Math.round((values[i]/max)*100);
    html += `<div class="faixa-item">
      <div class="faixa-header">
        <span class="faixa-label">${l}</span>
        <span class="faixa-val">${values[i]}</span>
      </div>
      <div class="faixa-bar-wrap">
        <div class="faixa-bar-fill" style="width:${pct}%;background:${colors[i]||'#999'}"></div>
      </div>
    </div>`;
  });
  document.getElementById('faixa-list').innerHTML = html;
}

/* ══════════════════════════════════════
   SPINNERS
══════════════════════════════════════ */
function setLoading(on) {
  ['load-curso','load-proc','load-gen','load-rank','load-media','load-top','load-faixas'].forEach(id => {
    document.getElementById(id)?.classList.toggle('ativo', on);
  });
}

/* ══════════════════════════════════════
   AJAX FILTROS
══════════════════════════════════════ */
async function aplicarFiltros() {
  const curso = document.getElementById('f-curso').value;
  const sexo  = document.getElementById('f-sexo').value;
  const proc  = document.getElementById('f-proc').value;

  const ativo = curso!=='todos' || sexo!=='todos' || proc!=='todos';
  document.getElementById('badge-filtro').classList.toggle('show', ativo);

  setLoading(true);
  try {
    const url = `dashboard.php?ajax=1&curso=${encodeURIComponent(curso)}&sexo=${encodeURIComponent(sexo)}&proc=${encodeURIComponent(proc)}`;
    const resp = await fetch(url);
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    const d = await resp.json();

    // ── KPI cards principais ──
    setEl('v-total',      d.totalCadastros);
    setEl('v-media',      d.mediaGeral);
    setEl('v-pcd',        d.totalPCD);
    setEl('v-cota',       d.totalCotaLocal);
    setEl('v-aprovados',  d.totalAprovados);
    setEl('v-reprovados', d.totalReprovados);
    setEl('v-maior',      d.maiorMedia);
    setEl('v-menor',      d.menorMedia);
    setEl('v-dp',         'σ ' + d.desvioPadrao);
    setEl('v-taxa-ap',    d.taxaAprovacao + '% aprovação');
    setEl('v-taxa-pcd',   d.taxaPCD + '% do total');

    // ── Procedência: legenda do donut + texto de meta ──
    setEl('leg-pub',  d.pubTotal);
    setEl('leg-priv', d.privTotal);
    setEl('v-pub',    'Pública: ' + d.pubTotal);
    setEl('v-priv',   'Privada: ' + d.privTotal);

    // ── Indicadores: barras de progresso + percentuais + textos de meta ──
    setWidth('bar-ap',  d.taxaAprovacao);
    setEl('ind-ap-v',   d.taxaAprovacao + '%');
    // texto "X / Y" da taxa de aprovação
    const elApFrac = document.querySelector('#bar-ap')?.closest('.indicator-card')?.querySelector('.progress-label span:last-child');
    if (elApFrac) elApFrac.textContent = d.totalAprovados + ' / ' + d.totalCadastros;
    // meta: reprovados
    const elApMeta = document.querySelector('#bar-ap')?.closest('.indicator-card')?.querySelector('.progress-meta span:last-child');
    if (elApMeta) elApMeta.textContent = d.totalReprovados + ' reprovados';

    const totalD   = (d.pubTotal||0) + (d.privTotal||0);
    const pctPubD  = totalD > 0 ? Math.round((d.pubTotal / totalD) * 100) : 0;
    setWidth('bar-pub', pctPubD);
    setEl('ind-pub-v',  pctPubD + '% pública');
    // texto "X / Y" da procedência
    const elPubFrac = document.querySelector('#bar-pub')?.closest('.indicator-card')?.querySelector('.progress-label span:last-child');
    if (elPubFrac) elPubFrac.textContent = d.pubTotal + ' / ' + d.privTotal;

    setWidth('bar-pcd', d.taxaPCD);
    setEl('ind-pcd-v',  d.taxaPCD + '%');
    // texto de quantidade PCD
    const elPcdFrac = document.querySelector('#bar-pcd')?.closest('.indicator-card')?.querySelector('.progress-label span:last-child');
    if (elPcdFrac) elPcdFrac.textContent = d.totalPCD + ' alunos';

    const totGenD  = d.generoValues.reduce((a,b)=>a+b,0);
    const mascVal  = d.generoValues[0] || 0;
    const femVal   = d.generoValues[1] || 0;
    const pctMascD = totGenD > 0 ? Math.round((mascVal / totGenD) * 100) : 0;
    setWidth('bar-masc', pctMascD);
    setEl('ind-masc-v',  pctMascD + '% masc.');
    // texto "X / Y" do gênero
    const elGenFrac = document.querySelector('#bar-masc')?.closest('.indicator-card')?.querySelector('.progress-label span:last-child');
    if (elGenFrac) elGenFrac.textContent = mascVal + ' / ' + femVal;
    // meta masc/fem
    const elGenMeta = document.querySelector('#bar-masc')?.closest('.indicator-card')?.querySelector('.progress-meta');
    if (elGenMeta) elGenMeta.innerHTML = `<span>Masc: ${mascVal}</span><span>Fem: ${femVal}</span>`;

    // ── Mini-table top cursos ──
    const miniTable = document.getElementById('curso-mini-table');
    if (miniTable && d.cursoLabels.length) {
      const maxQtd = Math.max(...d.cursoValues) || 1;
      const top5   = d.cursoLabels.slice(0, 5);
      miniTable.innerHTML = top5.map((nome, i) => {
        const qtd = d.cursoValues[i] || 0;
        const pct = Math.round((qtd / maxQtd) * 100);
        return `<tr>
          <td>${nome}</td>
          <td><div class="curso-bar"><div class="curso-bar-fill" style="width:${pct}%"></div></div></td>
          <td>${qtd}</td>
        </tr>`;
      }).join('');
    }

    // ── Gráficos ──
    updateBarWithPalette(charts.curso,   d.cursoLabels,    d.cursoValues, PALETTE);
    updateDoughnut(charts.proc,          d.procLabels,     d.procValues);
    updateBarWithPalette(charts.genero,  d.generoLabels,   d.generoValues,
      [COLORS.blue, COLORS.pink, COLORS.teal]);
    updateBarWithPalette(charts.ranking, d.rankLabels,     d.rankValues, PALETTE);
    updateChart(charts.media, d.mediaCursoLbls, d.mediaCursoVals,
      vals => vals.map(v => v>=7 ? COLORS.green+'cc' : v>=6 ? COLORS.yellow+'cc' : COLORS.red+'cc'));

    renderFaixas(d.faixasLabels, d.faixasValues);
    renderTop(d.topAlunos);

    // Atualiza hora
    setEl('last-update', '⏱ ' + new Date().toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}));

  } catch(e) {
    console.error('Erro ao filtrar:', e);
    alert('Erro ao aplicar filtros. Verifique o console.');
  } finally {
    setLoading(false);
  }
}

/* ══════════════════════════════════════
   EXPORTAR CSV
══════════════════════════════════════ */
function exportarCSV() {
  const rows = [['#','Nome','Curso','Gênero','Média','Categoria']];
  document.querySelectorAll('#top-table-wrap tbody tr').forEach((tr,i) => {
    const cells = [...tr.querySelectorAll('td')];
    if (cells.length >= 6) {
      rows.push([
        i+1,
        cells[1].textContent.trim(),
        cells[2].textContent.trim(),
        cells[3].textContent.trim(),
        cells[4].querySelector('strong')?.textContent.trim() || '',
        cells[5].textContent.trim(),
      ]);
    }
  });
  const csv = rows.map(r => r.map(v => `"${v}"`).join(',')).join('\n');
  const blob = new Blob(['\uFEFF'+csv], { type:'text/csv;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'ranking_alunos.csv';
  a.click();
}

/* ══════════════════════════════════════
   EVENTOS — registrados com DOMContentLoaded
   para garantir que rodam após qualquer script externo
══════════════════════════════════════ */
(function registrarEventos() {
  let debounce;
  ['f-curso','f-sexo','f-proc'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    // remove listeners antigos clonando o elemento
    const clone = el.cloneNode(true);
    el.parentNode.replaceChild(clone, el);
    clone.addEventListener('change', () => {
      clearTimeout(debounce);
      debounce = setTimeout(aplicarFiltros, 250);
    });
  });

  const btnReset = document.getElementById('btn-reset-filtros');
  if (btnReset) {
    const clone = btnReset.cloneNode(true);
    btnReset.parentNode.replaceChild(clone, btnReset);
    clone.addEventListener('click', () => {
      document.getElementById('f-curso').value = 'todos';
      document.getElementById('f-sexo').value  = 'todos';
      document.getElementById('f-proc').value  = 'todos';
      aplicarFiltros();
    });
  }
})();
</script>
</body>
</html>