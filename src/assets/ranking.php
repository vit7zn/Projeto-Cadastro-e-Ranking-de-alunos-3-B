<?php
$host   = "localhost";
$user   = "root";
$pass   = ""; 
$dbname = "sistema_login"; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Erro: " . $conn->connect_error); }

$curso = isset($_GET['curso']) ? $_GET['curso'] : 'Enfermagem';

// ── Vagas por categoria ──
$vagas = [
    'ampla_publica'            => 27,
    'cota_territorial_publica' => 9,
    'ampla_privada'            => 6,
    'cota_territorial_privada' => 3,
    'pcd'                      => 2,
];

function buscarAlunos($conn, $sql) {
    $result = $conn->query($sql);
    $lista = [];
    if ($result) while ($r = $result->fetch_assoc()) $lista[] = $r;
    return $lista;
}

$ampla_publica     = buscarAlunos($conn, "SELECT * FROM alunos WHERE curso LIKE '%$curso%' AND procedencia_escolar='publica' AND pcd='nao' ORDER BY media_geral DESC");
$cota_terr_publica = buscarAlunos($conn, "SELECT * FROM alunos WHERE curso LIKE '%$curso%' AND procedencia_escolar='publica' AND cota_local='sim' AND pcd='nao' ORDER BY media_geral DESC");
$ampla_privada     = buscarAlunos($conn, "SELECT * FROM alunos WHERE curso LIKE '%$curso%' AND procedencia_escolar='privada' AND pcd='nao' ORDER BY media_geral DESC");
$cota_terr_privada = buscarAlunos($conn, "SELECT * FROM alunos WHERE curso LIKE '%$curso%' AND procedencia_escolar='privada' AND cota_local='sim' AND pcd='nao' ORDER BY media_geral DESC");
$pcd               = buscarAlunos($conn, "SELECT * FROM alunos WHERE curso LIKE '%$curso%' AND pcd='sim' ORDER BY media_geral DESC");

$conn->close();

$cursos = [
    'Enfermagem'                  => 'Curso Técnico em Enfermagem',
    'Informatica'                 => 'Curso Técnico em Informática',
    'Administracao'               => 'Curso Técnico em Administração',
    'Desenvolvimento de Sistemas' => 'Curso Técnico Desenvolvimento de Sistemas',
];
$cursoLabel = $cursos[$curso] ?? strtoupper($curso);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resultado Preliminar – EEEP Manoel Mano</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Source Sans 3', Arial, sans-serif;
    background: #d8d8d8;
    color: #000;
    font-size: 13px;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ── Controles ── */
.controles-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    padding: 14px 24px;
    background: #0e5200;
    border-bottom: 2px solid #000;
}
.controles-bar label { font-weight: 700; color: #fff; font-size: .85rem; }
.controles-bar select {
    padding: 7px 14px;
    border-radius: 5px;
    border: 2px solid #fff;
    font-size: .87rem;
    color: #000;
    cursor: pointer;
    background: #fff;
    font-family: inherit;
}
.btn-pdf {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #e67e22;
    color: #fff;
    border: none;
    padding: 8px 18px;
    border-radius: 5px;
    font-weight: 700;
    font-size: .87rem;
    cursor: pointer;
    transition: background .2s;
    font-family: inherit;
}
.btn-pdf:hover { background: #cf711f; }
.btn-pdf svg { width: 14px; height: 14px; fill: currentColor; }

/* ── Folha ── */
.folha {
    width: 920px;
    margin: 22px auto 60px;
    background: #fff;
    box-shadow: 0 4px 28px rgba(0,0,0,.25);
    border: 1px solid #aaa;
}

/* ── Cabeçalho do documento ── */
.doc-header {
    display: grid;
    grid-template-columns: 76px 1fr 76px;
    align-items: center;
    padding: 12px 18px;
    border-bottom: 2.5px solid #000;
    gap: 10px;
}
.doc-header-logo img,
.doc-header-right img {
    width: 64px;
    height: auto;
    display: block;
}
.doc-header-right img { margin-left: auto; }
.doc-header-text { text-align: center; }
.doc-header-text .line1 {
    font-family: 'Libre Baskerville', Georgia, serif;
    font-size: 13.5px;
    font-weight: 700;
    color: #000;
    line-height: 1.35;
}
.doc-header-text .line2 {
    font-family: 'Libre Baskerville', Georgia, serif;
    font-size: 15px;
    font-weight: 700;
    color: #000;
    margin-top: 3px;
    line-height: 1.3;
}

/* ── Corpo ── */
.doc-body { padding: 0 18px 20px; }

/* ── Bloco ── */
.bloco { margin-top: 16px; }

/* ── Título da seção verde ── */
.secao-titulo {
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    text-align: center;
    padding: 6px 10px;
    border: 1.5px solid #000;
    border-bottom: none;
    font-family: 'Source Sans 3', sans-serif;
    background: #388e3c;
}
.secao-titulo.vermelho { background: #c62828; }

/* ── Tabela ── */
.rank-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}
.rank-table thead tr { background: #f0f0f0; }
.rank-table th {
    border: 1.5px solid #000;
    padding: 6px 5px;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: .35px;
    text-align: center;
}
.rank-table td {
    border: 1px solid #666;
    padding: 5px 6px;
    text-align: center;
}
.rank-table td.nome {
    text-align: left;
    text-transform: uppercase;
    padding-left: 9px;
    font-size: 10.5px;
}
.rank-table tr:nth-child(even) td { background: #f6fcf6; }

/* ── Classificado / Não classificado ── */
.linha-class td { font-weight: 700; color: #000; }
.linha-class .sit { color: #1a5c1a; }

.linha-nao td { color: #c62828; font-style: italic; font-weight: 400; }
.linha-nao .sit { color: #b71c1c; font-weight: 700; }

/* ── Vazio ── */
.row-vazio td {
    text-align: center;
    font-style: italic;
    color: #888;
    padding: 9px;
    font-size: 10.5px;
    border: 1px solid #aaa;
}

/* ── Parecer ── */
.parecer-box {
    margin: 18px 18px 0;
    border: 1.5px solid #888;
    padding: 9px 13px;
    font-size: 10px;
    line-height: 1.65;
    color: #222;
}
.parecer-titulo { font-weight: 700; margin-bottom: 5px; font-size: 10.5px; }
.parecer-box p { margin-bottom: 4px; }

/* ── Rodapé ── */
.doc-footer {
    text-align: right;
    padding: 8px 18px 12px;
    font-size: 10px;
    color: #666;
    font-style: italic;
    border-top: 1px solid #ddd;
    margin-top: 10px;
}

/* ── Impressão ── */
@media print {
    .navbar, .controles-bar,
    #sidebar-lateral, #overlay-menu { display: none !important; }
    body { background: #fff !important; }
    .folha {
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        margin: 0 !important;
    }
    .rank-table th, .rank-table td { font-size: 9px; padding: 3px 4px; }
    .secao-titulo { font-size: 9.5px; padding: 4px 8px; }
    .bloco { page-break-inside: avoid; }
    .rank-table tr { page-break-inside: avoid; }

    /* FORÇAR CORES NO PDF — sem isso o navegador remove backgrounds */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}
</style>
</head>
<body>

<div id="overlay-menu"></div>

<nav id="sidebar-lateral">
    <a href="dashboard.php">📊 Painel</a>
    <a href="cadastro.html">📋 Cadastro</a>
    <a href="ranking.php" class="active-link">🏆 Ranking</a>
    <a href="index.HTML">🚪 Sair</a>
    <div class="sidebar-footer">EEEP Manoel Mano © 2026</div>
</nav>

<header class="navbar">
    <div class="navbar-left">
        <button class="btn-hamburguer" id="btn-hamburguer" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <a class="navbar-brand" href="index.HTML">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTJgsXhRW5qdtpDbZWZmmPzg9njNJXOGcYLpQ&s" alt="Logo">
            EEEP Manoel Mano
        </a>
    </div>
    <div class="navbar-cards">
      <a href="dashboard.php" class="nav-card">
        <span class="nav-card-icon">📊</span><span>Painel</span>
      </a>
      <a href="cadastro.html" class="nav-card">
        <span class="nav-card-icon">📋</span><span>Cadastro</span>
      </a>
      <a href="ranking.php" class="nav-card active">
        <span class="nav-card-icon">🏆</span><span>Ranking</span>
      </a>
    </div>
    <div class="navbar-right">
        <a href="index.HTML" class="btn-sair">Sair</a>
    </div>
</header>

<!-- Controles -->
<div class="controles-bar">
    <form method="GET" style="display:inline-flex;align-items:center;gap:10px;">
        <label for="select-curso">FILTRAR POR CURSO:</label>
        <select id="select-curso" name="curso" onchange="this.form.submit()">
            <option value="Enfermagem"                  <?= $curso=='Enfermagem'                 ?'selected':'' ?>>ENFERMAGEM</option>
            <option value="Informatica"                 <?= $curso=='Informatica'                ?'selected':'' ?>>INFORMÁTICA</option>
            <option value="Administracao"               <?= $curso=='Administracao'              ?'selected':'' ?>>ADMINISTRAÇÃO</option>
            <option value="Desenvolvimento de Sistemas" <?= $curso=='Desenvolvimento de Sistemas'?'selected':'' ?>>DESENVOLVIMENTO DE SISTEMAS</option>
        </select>
    </form>
    <button class="btn-pdf" onclick="gerarPDF()">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17v-2h1.5v-3h2v3H13l-2.5 2.5L8 17zm4-9H7v2h5V8z"/></svg>
        Gerar PDF
    </button>
</div>

<!-- ═══════════ FOLHA ═══════════ -->
<div class="folha">

    <!-- Cabeçalho idêntico ao PDF -->
    <div class="doc-header">
        <div class="doc-header-logo">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTJgsXhRW5qdtpDbZWZmmPzg9njNJXOGcYLpQ&s" alt="Logo">
        </div>
        <div class="doc-header-text">
            <div class="line1">Processo Seletivo – EEEP Manoel Mano 2026</div>
            <div class="line2">RESULTADO PRELIMINAR – <?= htmlspecialchars($cursoLabel) ?></div>
        </div>
        <div class="doc-header-right">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTJgsXhRW5qdtpDbZWZmmPzg9njNJXOGcYLpQ&s" alt="ADESÕES">
        </div>
    </div>

    <div class="doc-body">
    <?php

    function renderBloco($titulo_secao, $tipo_label, $alunos, $vagas_n) {
        $class   = array_slice($alunos, 0, $vagas_n);
        $nclass  = array_slice($alunos, $vagas_n);

        // ── CLASSIFICADOS ──
        echo '<div class="bloco">';
        echo '<div class="secao-titulo">' . htmlspecialchars($titulo_secao) . ' CLASSIFICADOS</div>';
        echo '<table class="rank-table"><thead><tr>
                <th width="5%">ORDEM</th>
                <th width="45%">NOME</th>
                <th width="22%">TIPO CONCORRÊNCIA</th>
                <th width="18%">SITUAÇÃO</th>
                <th width="10%">MÉDIA FINAL</th>
              </tr></thead><tbody>';

        if (empty($class)) {
            echo '<tr class="row-vazio"><td colspan="5">NÃO HOUVERAM INSCRIÇÕES</td></tr>';
        } else {
            foreach ($class as $i => $a) {
                $pos   = $i + 1;
                $media = number_format((float)($a['media_geral'] ?? 0), 5, ',', '.');
                $nome  = htmlspecialchars(mb_strtoupper($a['nome_completo']));
                echo "<tr class='linha-class'>
                        <td>$pos</td>
                        <td class='nome'>$nome</td>
                        <td>" . htmlspecialchars(strtoupper($tipo_label)) . "</td>
                        <td class='sit'>CLASSIFICADO(A)</td>
                        <td>$media</td>
                      </tr>";
            }
        }
        echo '</tbody></table></div>';

        // ── NÃO CLASSIFICADOS ──
        echo '<div class="bloco">';
        echo '<div class="secao-titulo vermelho">' . htmlspecialchars($titulo_secao) . ' NÃO CLASSIFICADOS</div>';
        echo '<table class="rank-table"><thead><tr>
                <th width="5%">ORDEM</th>
                <th width="45%">NOME</th>
                <th width="22%">TIPO CONCORRÊNCIA</th>
                <th width="18%">SITUAÇÃO</th>
                <th width="10%">MÉDIA FINAL</th>
              </tr></thead><tbody>';

        if (empty($nclass)) {
            echo '<tr class="row-vazio"><td colspan="5">— nenhum candidato —</td></tr>';
        } else {
            foreach ($nclass as $i => $a) {
                $pos   = $vagas_n + $i + 1;
                $media = number_format((float)($a['media_geral'] ?? 0), 5, ',', '.');
                $nome  = htmlspecialchars(mb_strtoupper($a['nome_completo']));
                echo "<tr class='linha-nao'>
                        <td>$pos</td>
                        <td class='nome'>$nome</td>
                        <td>" . htmlspecialchars(strtoupper($tipo_label)) . "</td>
                        <td class='sit'>NÃO CLASSIFICADO(A)</td>
                        <td>$media</td>
                      </tr>";
            }
        }
        echo '</tbody></table></div>';
    }

    function renderBlocoPCD($alunos, $vagas_n) {
        // ── PCD CLASSIFICADOS ──
        echo '<div class="bloco">';
        echo '<div class="secao-titulo">COTA PESSOA COM DEFICIÊNCIA CLASSIFICADOS</div>';
        echo '<table class="rank-table"><thead><tr>
                <th width="5%">ORDEM</th>
                <th width="45%">NOME</th>
                <th width="22%">TIPO CONCORRÊNCIA</th>
                <th width="18%">SITUAÇÃO</th>
                <th width="10%">MÉDIA FINAL</th>
              </tr></thead><tbody>';

        $class  = array_slice($alunos, 0, $vagas_n);
        $nclass = array_slice($alunos, $vagas_n);

        if (empty($class)) {
            echo '<tr class="row-vazio"><td colspan="5">NÃO HOUVERAM INSCRIÇÕES</td></tr>';
        } else {
            foreach ($class as $i => $a) {
                $pos   = $i + 1;
                $media = number_format((float)($a['media_geral'] ?? 0), 5, ',', '.');
                $nome  = htmlspecialchars(mb_strtoupper($a['nome_completo']));
                echo "<tr class='linha-class'>
                        <td>$pos</td>
                        <td class='nome'>$nome</td>
                        <td>COTA PESSOA COM DEFICIÊNCIA</td>
                        <td class='sit'>CLASSIFICADO(A)</td>
                        <td>$media</td>
                      </tr>";
            }
        }
        echo '</tbody></table></div>';

        // ── PCD NÃO CLASSIFICADOS ──
        echo '<div class="bloco">';
        echo '<div class="secao-titulo vermelho">COTA PESSOA COM DEFICIÊNCIA NÃO CLASSIFICADOS</div>';
        echo '<table class="rank-table"><thead><tr>
                <th width="5%">ORDEM</th>
                <th width="45%">NOME</th>
                <th width="22%">TIPO CONCORRÊNCIA</th>
                <th width="18%">SITUAÇÃO</th>
                <th width="10%">MÉDIA FINAL</th>
              </tr></thead><tbody>';

        if (empty($nclass)) {
            echo '<tr class="row-vazio"><td colspan="5">NÃO HOUVERAM INSCRIÇÕES</td></tr>';
        } else {
            foreach ($nclass as $i => $a) {
                $pos   = $vagas_n + $i + 1;
                $media = number_format((float)($a['media_geral'] ?? 0), 5, ',', '.');
                $nome  = htmlspecialchars(mb_strtoupper($a['nome_completo']));
                echo "<tr class='linha-nao'>
                        <td>$pos</td>
                        <td class='nome'>$nome</td>
                        <td>COTA PESSOA COM DEFICIÊNCIA</td>
                        <td class='sit'>NÃO CLASSIFICADO(A)</td>
                        <td>$media</td>
                      </tr>";
            }
        }
        echo '</tbody></table></div>';
    }

    renderBloco('AMPLA CONCORRÊNCIA PÚBLICA',    'Ampla Concorrência Pública',    $ampla_publica,     $vagas['ampla_publica']);
    renderBloco('COTA TERRITORIAL PÚBLICA',      'Territorial Pública',           $cota_terr_publica, $vagas['cota_territorial_publica']);
    renderBloco('AMPLA CONCORRÊNCIA PRIVADO',    'Ampla Concorrência Privado',    $ampla_privada,     $vagas['ampla_privada']);
    renderBloco('COTA TERRITORIAL PRIVADO',      'Cota Territorial Privado',      $cota_terr_privada, $vagas['cota_territorial_privada']);
    renderBlocoPCD($pcd, $vagas['pcd']);
    ?>
    </div><!-- /doc-body -->

    <!-- Parecer jurídico -->
    <div class="parecer-box">
        <div class="parecer-titulo">PARECER Nº 010690/2025/SEDUC/ASJUR</div>
        <p>Embora a Lei Federal nº 15.142/2025 trate especificamente das cotas PPIQ, o modelo normativo federal de reserva de vagas é de: concorrência simultânea; dupla listagem; e não exclusão da ampla concorrência.</p>
        <p>Assim, candidatos inscritos em modalidades afirmativas territoriais não ficam restritos apenas à lista da reserva, devendo também figurar na ampla concorrência, preservando-se: <em>o princípio da isonomia; a ordem geral de classificação; e a competitividade entre todos os estudantes.</em></p>
    </div>

    <div class="doc-footer">EEEP Manoel Mano — Crateús &nbsp;|&nbsp; <?= date('d/m/Y') ?></div>

</div><!-- /folha -->

<script>
(function(){
    const btn     = document.getElementById('btn-hamburguer');
    const sidebar = document.getElementById('sidebar-lateral');
    const overlay = document.getElementById('overlay-menu');
    function fechar(){
        btn.classList.remove('aberto');
        sidebar.classList.remove('aberta');
        overlay.classList.remove('aberto');
    }
    btn.addEventListener('click', () =>
        sidebar.classList.contains('aberta') ? fechar() :
        (btn.classList.add('aberto'), sidebar.classList.add('aberta'), overlay.classList.add('aberto'))
    );
    overlay.addEventListener('click', fechar);
})();

function gerarPDF() {
    document.getElementById('sidebar-lateral').classList.remove('aberta');
    document.getElementById('overlay-menu').classList.remove('aberto');
    document.getElementById('btn-hamburguer').classList.remove('aberto');
    setTimeout(() => window.print(), 120);
}
</script>
</body>
</html>
