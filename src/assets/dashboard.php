<?php
// =============================================
// DASHBOARD - Sistema de Cadastro de Alunos
// =============================================
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Configuração de conexão
$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "sistema_login";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// --- DADOS PARA OS CARDS E GRÁFICOS ---

// 1. Total de cadastros
$totalCadastros = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM alunos");
if ($res) $totalCadastros = $res->fetch_assoc()['total'];

// 2. Distribuição por Gênero
$generoData = ['Masculino' => 0, 'Feminino' => 0, 'Outro' => 0];
$res = $conn->query("SELECT sexo, COUNT(*) as qtd FROM alunos GROUP BY sexo");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $s = strtolower($row['sexo'] ?? '');
        if (str_contains($s, 'masc')) $generoData['Masculino'] += $row['qtd'];
        elseif (str_contains($s, 'fem')) $generoData['Feminino'] += $row['qtd'];
        else $generoData['Outro'] += $row['qtd'];
    }
}

// 3. Procedência escolar
$procedenciaData = ['Pública' => 0, 'Privada' => 0];
$res = $conn->query("SELECT procedencia_escolar, COUNT(*) as qtd FROM alunos GROUP BY procedencia_escolar");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if ($row['procedencia_escolar'] === 'publica') $procedenciaData['Pública'] = $row['qtd'];
        else $procedenciaData['Privada'] = $row['qtd'];
    }
}

// 4. Média por matéria (notas_detalhadas)
$mediaPorMateria = [];
$res = $conn->query("SELECT materia,
    ROUND(AVG((nota_6_ano + nota_7_ano + nota_8_ano + nota_9_ano)/4), 2) as media_geral
    FROM notas_detalhadas GROUP BY materia ORDER BY materia");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $mediaPorMateria[$row['materia']] = $row['media_geral'];
    }
}

// 5. Categorias de Ranking
$rankingData = [];
$res = $conn->query("SELECT categoria_ranking, COUNT(*) as qtd FROM alunos WHERE categoria_ranking IS NOT NULL GROUP BY categoria_ranking");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rankingData[$row['categoria_ranking']] = $row['qtd'];
    }
}

// 6. PCD
$totalPCD = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM alunos WHERE pcd = 'sim'");
if ($res) $totalPCD = $res->fetch_assoc()['total'];

// 7. Cota Local
$totalCotaLocal = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM alunos WHERE cota_local = 'sim'");
if ($res) $totalCotaLocal = $res->fetch_assoc()['total'];

// 8. Top 5 alunos por média
$topAlunos = [];
$res = $conn->query("SELECT nome_completo, media_geral, categoria_ranking FROM alunos WHERE media_geral IS NOT NULL ORDER BY media_geral DESC LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) $topAlunos[] = $row;
}

// 9. Média geral do sistema
$mediaGeral = 0;
$res = $conn->query("SELECT ROUND(AVG(media_geral), 2) as media FROM alunos WHERE media_geral IS NOT NULL");
if ($res) $mediaGeral = $res->fetch_assoc()['media'] ?? 0;

// Nome do usuário logado (sessão)
$nomeUsuario = $_SESSION['nome'] ?? 'Secretária';

$conn->close();

// Converter dados para JSON para o JS
$generoJson      = json_encode(array_values($generoData));
$generoLabels    = json_encode(array_keys($generoData));
$procedenciaJson = json_encode(array_values($procedenciaData));
$materiaLabels   = json_encode(array_keys($mediaPorMateria));
$materiaMedias   = json_encode(array_values($mediaPorMateria));
$rankingLabels   = json_encode(array_keys($rankingData));
$rankingValues   = json_encode(array_values($rankingData));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – Sistema de Cadastro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="style.css">
<style>
  /* =============================================
     DASHBOARD - Estilos exclusivos
  ============================================= */
  :root {
    --green-dark:  #145f00;
    --green-main:  #1b8a00;
    --green-light: #23a800;
    --orange:      #e67e22;
    --orange-dark: #cf711f;
    --bg:          #f0f2f0;
    --card-bg:     #ffffff;
    --text-dark:   #1a1a1a;
    --text-muted:  #6b7280;
    --border:      #e5e7eb;
    --shadow:      0 2px 12px rgba(0,0,0,.08);
    --radius:      14px;
  }

  body { background: var(--bg); font-family: 'Outfit', sans-serif; color: var(--text-dark); }

  /* ---- NAVBAR ---- */
  .navbar {
    background: linear-gradient(90deg, var(--green-dark) 0%, var(--green-main) 100%);
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 28px;
    height: 62px;
    position: sticky;
    top: 0;
    z-index: 900;
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
  }

  .navbar-left   { display: flex; align-items: center; gap: 14px; }
  .navbar-center { display: flex; gap: 4px; }
  .navbar-right  { display: flex; align-items: center; gap: 10px; }

  .user-block .user-name { font-weight: 600; font-size: .9rem; line-height: 1.2; }
  .user-block .user-role { font-size: .72rem; opacity: .7; }

  .nav-link {
    color: rgba(255,255,255,.8);
    text-decoration: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: .88rem;
    font-weight: 500;
    transition: background .2s, color .2s;
  }
  .nav-link:hover      { background: rgba(255,255,255,.12); color: #fff; }
  .nav-link.active     { background: #fff; color: var(--green-main); font-weight: 700; }

  .nav-icon-btn {
    background: none; border: none; cursor: pointer;
    color: #fff; font-size: 1.1rem; padding: 6px;
    border-radius: 6px; transition: background .2s;
  }
  .nav-icon-btn:hover { background: rgba(255,255,255,.15); }

  /* ---- LAYOUT PRINCIPAL ---- */
  .dash-wrapper { padding: 28px 32px; max-width: 1280px; margin: 0 auto; }

  .dash-header { margin-bottom: 24px; }
  .dash-header h1 { font-size: 1.55rem; font-weight: 700; color: var(--green-dark); }
  .dash-header p  { color: var(--text-muted); font-size: .9rem; margin-top: 2px; }

  /* ---- CARDS ESTATÍSTICOS ---- */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: var(--card-bg);
    border-radius: var(--radius);
    padding: 22px 20px;
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
    gap: 6px;
    border-top: 4px solid var(--green-main);
    transition: transform .2s, box-shadow .2s;
  }
  .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.12); }
  .stat-card.orange { border-top-color: var(--orange); }
  .stat-card.teal   { border-top-color: #0ea5e9; }
  .stat-card.violet { border-top-color: #8b5cf6; }

  .stat-label { font-size: .78rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .5px; color: var(--text-muted); }
  .stat-value { font-size: 2.4rem; font-weight: 700; line-height: 1; color: var(--text-dark); }
  .stat-sub   { font-size: .78rem; color: var(--text-muted); }

  /* ---- GRID DE GRÁFICOS ---- */
  .charts-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 18px;
  }

  .chart-card {
    background: var(--card-bg);
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--shadow);
    transition: transform .2s;
  }
  .chart-card:hover { transform: translateY(-2px); }

  .chart-card h3 {
    font-size: .88rem; font-weight: 700; color: var(--text-dark);
    margin-bottom: 14px; text-transform: uppercase; letter-spacing: .4px;
    padding-bottom: 10px; border-bottom: 2px solid var(--border);
  }

  /* Tamanhos no grid */
  .col-4  { grid-column: span 4; }
  .col-5  { grid-column: span 5; }
  .col-6  { grid-column: span 6; }
  .col-7  { grid-column: span 7; }
  .col-8  { grid-column: span 8; }
  .col-12 { grid-column: span 12; }

  .chart-canvas-wrap { position: relative; }
  .chart-canvas-wrap.h240 { height: 240px; }
  .chart-canvas-wrap.h200 { height: 200px; }
  .chart-canvas-wrap.h280 { height: 280px; }

  /* ---- TABELA TOP ALUNOS ---- */
  .top-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
  .top-table th {
    text-align: left; padding: 9px 12px;
    background: #f8faf8; color: var(--text-muted);
    font-weight: 600; font-size: .75rem;
    text-transform: uppercase; letter-spacing: .4px;
    border-bottom: 2px solid var(--border);
  }
  .top-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); }
  .top-table tr:last-child td { border-bottom: none; }
  .top-table tr:hover td { background: #f9fdf7; }

  .badge {
    display: inline-block; padding: 3px 9px; border-radius: 20px;
    font-size: .72rem; font-weight: 600;
  }
  .badge-green  { background: #dcfce7; color: #166534; }
  .badge-blue   { background: #dbeafe; color: #1e40af; }
  .badge-violet { background: #ede9fe; color: #5b21b6; }
  .badge-orange { background: #ffedd5; color: #9a3412; }

  .medal { font-size: 1rem; margin-right: 4px; }

  /* ---- EMPTY STATE ---- */
  .empty-state {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; height: 160px;
    color: var(--text-muted); font-size: .9rem; gap: 8px;
  }
  .empty-state span { font-size: 2rem; }

  /* ---- RESPONSIVE ---- */
  @media (max-width: 900px) {
    .col-4, .col-5, .col-6, .col-7, .col-8 { grid-column: span 12; }
    .dash-wrapper { padding: 16px; }
    .navbar-center { display: none; }
  }

  /* ---- ANIMAÇÕES ---- */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .stat-card { animation: fadeUp .4s ease both; }
  .stat-card:nth-child(1) { animation-delay: .05s; }
  .stat-card:nth-child(2) { animation-delay: .10s; }
  .stat-card:nth-child(3) { animation-delay: .15s; }
  .stat-card:nth-child(4) { animation-delay: .20s; }
  .stat-card:nth-child(5) { animation-delay: .25s; }

  .chart-card { animation: fadeUp .5s ease both; animation-delay: .3s; }
</style>
</head>
<body>

<!-- ===================== OVERLAY ===================== -->
<div id="overlay-menu"></div>

<!-- ===================== NAVBAR ===================== -->
<nav class="navbar">
  <div class="navbar-left">
    <button id="btn-menu-hamburguer" aria-label="Menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <div class="user-block">
      <span class="user-name"><?= htmlspecialchars($nomeUsuario) ?></span>
      <span class="user-role">Secretária</span>
    </div>
  </div>

  <div class="navbar-center">
    <a href="ocr-notas.html"  class="nav-link">Cadastro Aluno</a>
    <a href="dashboard.php" class="nav-link active">Dashboard</a>
    <a href="ranking.php"   class="nav-link">Ranking</a>
  </div>

  <div class="navbar-right">
    <button class="nav-icon-btn" title="Sair" onclick="window.location.href='logout.php'">🚪</button>
  </div>
</nav>

<!-- ===================== SIDEBAR ===================== -->
<nav id="sidebar-lateral">
  <a href="index.html">Início</a>
  <a href="cadastro.html">Cadastro</a>
  <a href="dashboard.php" class="active-link">Dashboard</a>
  <a href="ranking.php">Ranking</a>
  <div class="sidebar-footer">Sistema de Matrículas © 2025</div>
</nav>

<!-- ===================== CONTEÚDO PRINCIPAL ===================== -->
<main class="dash-wrapper">

  <div class="dash-header">
    <h1>📊 Dashboard</h1>
    <p>Visão geral dos cadastros e desempenho dos alunos</p>
  </div>

  <!-- CARDS ESTATÍSTICOS -->
  <div class="stats-row">
    <div class="stat-card">
      <span class="stat-label">Total de Cadastros</span>
      <span class="stat-value"><?= number_format($totalCadastros) ?></span>
      <span class="stat-sub">alunos registrados</span>
    </div>
    <div class="stat-card orange">
      <span class="stat-label">Média Geral</span>
      <span class="stat-value"><?= number_format($mediaGeral, 1, ',', '.') ?></span>
      <span class="stat-sub">pontos de média</span>
    </div>
    <div class="stat-card teal">
      <span class="stat-label">Alunos PCD</span>
      <span class="stat-value"><?= $totalPCD ?></span>
      <span class="stat-sub">com necessidades especiais</span>
    </div>
    <div class="stat-card violet">
      <span class="stat-label">Cota Local</span>
      <span class="stat-value"><?= $totalCotaLocal ?></span>
      <span class="stat-sub">bairro Venâncios</span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Escola Pública</span>
      <span class="stat-value"><?= $procedenciaData['Pública'] ?></span>
      <span class="stat-sub">procedência pública</span>
    </div>
  </div>

  <!-- GRID DE GRÁFICOS -->
  <div class="charts-grid">

    <!-- Média por Matéria -->
    <div class="chart-card col-7">
      <h3>📚 Média por Matéria</h3>
      <?php if (!empty($mediaPorMateria)): ?>
      <div class="chart-canvas-wrap h240">
        <canvas id="chartMaterias"></canvas>
      </div>
      <?php else: ?>
      <div class="empty-state"><span>📭</span>Nenhuma nota cadastrada ainda</div>
      <?php endif; ?>
    </div>

    <!-- Procedência Escolar (Rosca) -->
    <div class="chart-card col-5">
      <h3>🏫 Procedência Escolar</h3>
      <div class="chart-canvas-wrap h240">
        <canvas id="chartProcedencia"></canvas>
      </div>
    </div>

    <!-- Gênero dos Alunos -->
    <div class="chart-card col-5">
      <h3>👥 Gênero dos Alunos</h3>
      <div class="chart-canvas-wrap h200">
        <canvas id="chartGenero"></canvas>
      </div>
    </div>

    <!-- Categorias de Ranking -->
    <div class="chart-card col-7">
      <h3>🏆 Categorias no Ranking</h3>
      <?php if (!empty($rankingData)): ?>
      <div class="chart-canvas-wrap h200">
        <canvas id="chartRanking"></canvas>
      </div>
      <?php else: ?>
      <div class="empty-state"><span>📭</span>Nenhuma categoria cadastrada ainda</div>
      <?php endif; ?>
    </div>

    <!-- Top 5 Alunos -->
    <div class="chart-card col-12">
      <h3>🥇 Top 5 Alunos por Média</h3>
      <?php if (!empty($topAlunos)): ?>
      <table class="top-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Média</th>
            <th>Categoria</th>
          </tr>
        </thead>
        <tbody>
          <?php
            $medals = ['🥇','🥈','🥉','4º','5º'];
            $badgeClass = ['badge-green','badge-blue','badge-orange','badge-violet'];
            foreach ($topAlunos as $i => $a):
              $bc = $badgeClass[$i % count($badgeClass)];
          ?>
          <tr>
            <td><span class="medal"><?= $medals[$i] ?></span></td>
            <td><?= htmlspecialchars($a['nome_completo']) ?></td>
            <td><strong><?= number_format($a['media_geral'], 2, ',', '.') ?></strong></td>
            <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($a['categoria_ranking'] ?? '—') ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state"><span>📭</span>Nenhum aluno com média cadastrada</div>
      <?php endif; ?>
    </div>

  </div><!-- /charts-grid -->
</main>

<!-- ===================== SCRIPTS ===================== -->
<script src="script.js"></script>
<script>
// Paleta de cores do sistema
const COLORS = {
  green:  '#1b8a00',
  lightG: '#23a800',
  orange: '#e67e22',
  blue:   '#3b82f6',
  violet: '#8b5cf6',
  pink:   '#ec4899',
  teal:   '#0ea5e9',
  yellow: '#f59e0b',
};

const defaultFont = { family: 'Outfit', size: 12 };
Chart.defaults.font = defaultFont;

// ---- Gráfico: Média por Matéria (barras horizontais) ----
<?php if (!empty($mediaPorMateria)): ?>
new Chart(document.getElementById('chartMaterias'), {
  type: 'bar',
  data: {
    labels: <?= $materiaLabels ?>,
    datasets: [{
      label: 'Média',
      data:   <?= $materiaMedias ?>,
      backgroundColor: [
        '#1b8a00cc','#e67e22cc','#3b82f6cc',
        '#8b5cf6cc','#ec4899cc','#0ea5e9cc','#f59e0bcc'
      ],
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: '#f0f0f0' }, max: 10,
           ticks: { font: defaultFont } },
      y: { grid: { display: false }, ticks: { font: defaultFont } }
    }
  }
});
<?php endif; ?>

// ---- Gráfico: Procedência Escolar (rosca) ----
new Chart(document.getElementById('chartProcedencia'), {
  type: 'doughnut',
  data: {
    labels: <?= $procedenciaJson ? json_encode(array_keys($procedenciaData)) : '["Pública","Privada"]' ?>,
    datasets: [{
      data: <?= $procedenciaJson ? json_encode(array_values($procedenciaData)) : '[0,0]' ?>,
      backgroundColor: [COLORS.green, COLORS.orange],
      borderWidth: 3,
      borderColor: '#fff',
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    cutout: '62%',
    plugins: {
      legend: { position: 'bottom', labels: { font: defaultFont, padding: 14 } }
    }
  }
});

// ---- Gráfico: Gênero (barras horizontais) ----
new Chart(document.getElementById('chartGenero'), {
  type: 'bar',
  data: {
    labels: <?= $generoLabels ?>,
    datasets: [{
      label: 'Alunos',
      data: <?= $generoJson ?>,
      backgroundColor: [COLORS.blue, COLORS.pink, COLORS.teal],
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: '#f0f0f0' }, ticks: { font: defaultFont } },
      y: { grid: { display: false }, ticks: { font: defaultFont } }
    }
  }
});

// ---- Gráfico: Categorias de Ranking ----
<?php if (!empty($rankingData)): ?>
new Chart(document.getElementById('chartRanking'), {
  type: 'bar',
  data: {
    labels: <?= $rankingLabels ?>,
    datasets: [{
      label: 'Alunos',
      data: <?= $rankingValues ?>,
      backgroundColor: [
        COLORS.green, COLORS.orange, COLORS.violet, COLORS.teal, COLORS.yellow
      ],
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { font: defaultFont, maxRotation: 25 } },
      y: { grid: { color: '#f0f0f0' }, ticks: { font: defaultFont, stepSize: 1 } }
    }
  }
});
<?php endif; ?>
</script>
</body>
</html>
