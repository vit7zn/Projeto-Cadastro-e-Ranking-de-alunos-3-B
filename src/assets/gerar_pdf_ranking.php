<?php
// ══════════════════════════════════════════════════════════════════
//  gerar_pdf_ranking.php — Gerador de PDF do ranking
//  EEEP Manoel Mano
//
//  Este arquivo tem DOIS modos de uso:
//
//  1) Incluído por require_once (ex: enviar_whatsapp.php)
//     → Apenas define a função gerarPdfRanking($conn, $curso)
//     → NÃO executa nada, NÃO faz download
//
//  2) Acessado diretamente via URL (?curso=Enfermagem)
//     → Verifica sessão, gera o PDF e faz download direto
//
//  Dependência: mPDF
//    composer require mpdf/mpdf
// ══════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────────────────────────────
//  FUNÇÃO PRINCIPAL — pode ser chamada por qualquer include
//  Recebe conexão ativa e nome do curso
//  Retorna os bytes do PDF (string)
// ──────────────────────────────────────────────────────────────────
function gerarPdfRanking(mysqli $conn, string $curso): string {

    require_once __DIR__ . '/../../vendor/autoload.php';

    $cursos = [
        'Enfermagem'                  => 'Curso Técnico em Enfermagem',
        'Informatica'                 => 'Curso Técnico em Informática',
        'Administracao'               => 'Curso Técnico em Administração',
        'Desenvolvimento de Sistemas' => 'Curso Técnico Desenvolvimento de Sistemas',
    ];
    $cursoLabel = $cursos[$curso] ?? strtoupper($curso);

    // ── Vagas por categoria ──
    $vagas = [
        'ampla_publica'            => 27,
        'cota_territorial_publica' => 9,
        'ampla_privada'            => 6,
        'cota_territorial_privada' => 3,
        'pcd'                      => 2,
    ];

    // ── Busca alunos ──
    $cursoEsc = $conn->real_escape_string($curso);

    $buscar = function(string $sql) use ($conn): array {
        $r = $conn->query($sql);
        $l = [];
        if ($r) while ($row = $r->fetch_assoc()) $l[] = $row;
        return $l;
    };

    $ampla_publica     = $buscar("SELECT * FROM alunos WHERE curso LIKE '%$cursoEsc%' AND procedencia_escolar='publica'  AND pcd='nao' ORDER BY media_geral DESC");
    $cota_terr_publica = $buscar("SELECT * FROM alunos WHERE curso LIKE '%$cursoEsc%' AND procedencia_escolar='publica'  AND cota_local='sim' AND pcd='nao' ORDER BY media_geral DESC");
    $ampla_privada     = $buscar("SELECT * FROM alunos WHERE curso LIKE '%$cursoEsc%' AND procedencia_escolar='privada'  AND pcd='nao' ORDER BY media_geral DESC");
    $cota_terr_privada = $buscar("SELECT * FROM alunos WHERE curso LIKE '%$cursoEsc%' AND procedencia_escolar='privada'  AND cota_local='sim' AND pcd='nao' ORDER BY media_geral DESC");
    $pcd_lista         = $buscar("SELECT * FROM alunos WHERE curso LIKE '%$cursoEsc%' AND pcd='sim' ORDER BY media_geral DESC");

    $data    = date('d/m/Y');
    $logoUrl = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTJgsXhRW5qdtpDbZWZmmPzg9njNJXOGcYLpQ&s';

    // ── HTML do PDF ──
    $html = _pdfHtmlCompleto($cursoLabel, $logoUrl, $data,
        $ampla_publica, $cota_terr_publica,
        $ampla_privada, $cota_terr_privada,
        $pcd_lista, $vagas
    );

    // ── Gera com mPDF e retorna bytes ──
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'margin_top'    => 12,
        'margin_bottom' => 10,
        'margin_left'   => 12,
        'margin_right'  => 12,
        'tempDir'       => sys_get_temp_dir(),
    ]);
    $mpdf->SetTitle("Resultado Seletivo 2027 – $curso");
    $mpdf->SetAuthor("EEEP Manoel Mano");
    $mpdf->SetHTMLHeader('');
    $mpdf->SetHTMLFooter('');
    $mpdf->WriteHTML($html);

    // Output('', 'S') = retorna string com os bytes do PDF
    return $mpdf->Output('', 'S');
}

// ──────────────────────────────────────────────────────────────────
//  Renderiza bloco de uma categoria (HTML interno)
// ──────────────────────────────────────────────────────────────────
function _renderBlocoHtml(string $titulo, string $tipoLabel, array $alunos, int $vagas_n): string {
    $class  = array_slice($alunos, 0, $vagas_n);
    $nclass = array_slice($alunos, $vagas_n);
    $html   = '';

    $html .= "<div class='secao-titulo'>$titulo CLASSIFICADOS</div>";
    $html .= "<table class='rank-table'><thead><tr>
                <th width='5%'>ORDEM</th>
                <th width='45%'>NOME</th>
                <th width='22%'>TIPO CONCORRÊNCIA</th>
                <th width='18%'>SITUAÇÃO</th>
                <th width='10%'>MÉDIA FINAL</th>
              </tr></thead><tbody>";

    if (empty($class)) {
        $html .= "<tr class='row-vazio'><td colspan='5'>NÃO HOUVERAM INSCRIÇÕES</td></tr>";
    } else {
        foreach ($class as $i => $a) {
            $pos   = $i + 1;
            $media = number_format((float)($a['media_geral'] ?? 0), 5, ',', '.');
            $nome  = htmlspecialchars(mb_strtoupper($a['nome_completo']));
            $tipo  = htmlspecialchars(strtoupper($tipoLabel));
            $html .= "<tr class='linha-class'>
                        <td>$pos</td><td class='nome'>$nome</td>
                        <td>$tipo</td><td class='sit'>CLASSIFICADO(A)</td>
                        <td>$media</td>
                      </tr>";
        }
    }
    $html .= "</tbody></table>";

    $html .= "<div class='secao-titulo vermelho'>$titulo NÃO CLASSIFICADOS</div>";
    $html .= "<table class='rank-table'><thead><tr>
                <th width='5%'>ORDEM</th>
                <th width='45%'>NOME</th>
                <th width='22%'>TIPO CONCORRÊNCIA</th>
                <th width='18%'>SITUAÇÃO</th>
                <th width='10%'>MÉDIA FINAL</th>
              </tr></thead><tbody>";

    if (empty($nclass)) {
        $html .= "<tr class='row-vazio'><td colspan='5'>— nenhum candidato —</td></tr>";
    } else {
        foreach ($nclass as $i => $a) {
            $pos   = $vagas_n + $i + 1;
            $media = number_format((float)($a['media_geral'] ?? 0), 5, ',', '.');
            $nome  = htmlspecialchars(mb_strtoupper($a['nome_completo']));
            $tipo  = htmlspecialchars(strtoupper($tipoLabel));
            $html .= "<tr class='linha-nao'>
                        <td>$pos</td><td class='nome'>$nome</td>
                        <td>$tipo</td><td class='sit'>NÃO CLASSIFICADO(A)</td>
                        <td>$media</td>
                      </tr>";
        }
    }
    $html .= "</tbody></table>";

    return $html;
}

function _renderBlocoPcdHtml(array $alunos, int $vagas_n): string {
    $class  = array_slice($alunos, 0, $vagas_n);
    $nclass = array_slice($alunos, $vagas_n);
    $html   = '';

    $html .= "<div class='secao-titulo'>COTA PESSOA COM DEFICIÊNCIA CLASSIFICADOS</div>";
    $html .= "<table class='rank-table'><thead><tr>
                <th width='5%'>ORDEM</th>
                <th width='45%'>NOME</th>
                <th width='22%'>TIPO CONCORRÊNCIA</th>
                <th width='18%'>SITUAÇÃO</th>
                <th width='10%'>MÉDIA FINAL</th>
              </tr></thead><tbody>";

    if (empty($class)) {
        $html .= "<tr class='row-vazio'><td colspan='5'>NÃO HOUVERAM INSCRIÇÕES</td></tr>";
    } else {
        foreach ($class as $i => $a) {
            $pos   = $i + 1;
            $media = number_format((float)($a['media_geral'] ?? 0), 5, ',', '.');
            $nome  = htmlspecialchars(mb_strtoupper($a['nome_completo']));
            $html .= "<tr class='linha-class'>
                        <td>$pos</td><td class='nome'>$nome</td>
                        <td>COTA PESSOA COM DEFICIÊNCIA</td>
                        <td class='sit'>CLASSIFICADO(A)</td>
                        <td>$media</td>
                      </tr>";
        }
    }
    $html .= "</tbody></table>";

    $html .= "<div class='secao-titulo vermelho'>COTA PESSOA COM DEFICIÊNCIA NÃO CLASSIFICADOS</div>";
    $html .= "<table class='rank-table'><thead><tr>
                <th width='5%'>ORDEM</th>
                <th width='45%'>NOME</th>
                <th width='22%'>TIPO CONCORRÊNCIA</th>
                <th width='18%'>SITUAÇÃO</th>
                <th width='10%'>MÉDIA FINAL</th>
              </tr></thead><tbody>";

    if (empty($nclass)) {
        $html .= "<tr class='row-vazio'><td colspan='5'>NÃO HOUVERAM INSCRIÇÕES</td></tr>";
    } else {
        foreach ($nclass as $i => $a) {
            $pos   = $vagas_n + $i + 1;
            $media = number_format((float)($a['media_geral'] ?? 0), 5, ',', '.');
            $nome  = htmlspecialchars(mb_strtoupper($a['nome_completo']));
            $html .= "<tr class='linha-nao'>
                        <td>$pos</td><td class='nome'>$nome</td>
                        <td>COTA PESSOA COM DEFICIÊNCIA</td>
                        <td class='sit'>NÃO CLASSIFICADO(A)</td>
                        <td>$media</td>
                      </tr>";
        }
    }
    $html .= "</tbody></table>";

    return $html;
}

function _pdfHtmlCompleto(
    string $cursoLabel, string $logoUrl, string $data,
    array $ampla_publica, array $cota_terr_publica,
    array $ampla_privada, array $cota_terr_privada,
    array $pcd_lista, array $vagas
): string {
    $html = "<!DOCTYPE html><html lang='pt-br'><head><meta charset='UTF-8'><style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
.doc-header { width: 100%; border-bottom: 2.5px solid #000; padding-bottom: 8px; margin-bottom: 0; }
.doc-header table { width: 100%; border: none; }
.doc-header td { border: none; padding: 2px 6px; vertical-align: middle; }
.doc-header-text { text-align: center; }
.doc-header-text .line1 { font-size: 12px; font-weight: bold; }
.doc-header-text .line2 { font-size: 14px; font-weight: bold; margin-top: 3px; }
.doc-header img { width: 56px; height: auto; }
.img-right { text-align: right; }
.secao-titulo { color: #fff; font-size: 10px; font-weight: bold; text-transform: uppercase;
    letter-spacing: .5px; text-align: center; padding: 5px 8px;
    border: 1.5px solid #000; border-bottom: none;
    background-color: #388e3c; margin-top: 14px; }
.secao-titulo.vermelho { background-color: #c62828; margin-top: 0; }
.rank-table { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 0; }
.rank-table thead tr { background-color: #f0f0f0; }
.rank-table th { border: 1.5px solid #000; padding: 5px 4px; font-weight: bold;
    text-transform: uppercase; font-size: 8.5px; text-align: center; }
.rank-table td { border: 1px solid #666; padding: 4px 5px; text-align: center; }
.rank-table td.nome { text-align: left; text-transform: uppercase; padding-left: 7px; }
.linha-class td { font-weight: bold; color: #000; }
.linha-class .sit { color: #1a5c1a; }
.linha-nao td { color: #c62828; font-style: italic; }
.linha-nao .sit { color: #b71c1c; font-weight: bold; }
.row-vazio td { text-align: center; font-style: italic; color: #888; padding: 7px; border: 1px solid #aaa; }
.parecer-box { margin-top: 16px; border: 1.5px solid #888; padding: 8px 11px;
    font-size: 9px; line-height: 1.6; color: #222; }
.parecer-titulo { font-weight: bold; margin-bottom: 4px; font-size: 9.5px; }
.parecer-box p { margin-bottom: 3px; }
.doc-footer { text-align: right; padding: 7px 0 0; font-size: 9px; color: #666;
    font-style: italic; border-top: 1px solid #ddd; margin-top: 10px; }
</style></head><body>

<div class='doc-header'>
    <table><tr>
        <td width='70'><img src='$logoUrl' alt='Logo'></td>
        <td class='doc-header-text'>
            <div class='line1'>Processo Seletivo – EEEP Manoel Mano 2027</div>
            <div class='line2'>RESULTADO PRELIMINAR – " . htmlspecialchars($cursoLabel) . "</div>
        </td>
        <td width='70' class='img-right'><img src='$logoUrl' alt='Logo'></td>
    </tr></table>
</div>";

    $html .= _renderBlocoHtml('AMPLA CONCORRÊNCIA PÚBLICA',  'Ampla Concorrência Pública',  $ampla_publica,     $vagas['ampla_publica']);
    $html .= _renderBlocoHtml('COTA TERRITORIAL PÚBLICA',    'Territorial Pública',         $cota_terr_publica, $vagas['cota_territorial_publica']);
    $html .= _renderBlocoHtml('AMPLA CONCORRÊNCIA PRIVADO',  'Ampla Concorrência Privado',  $ampla_privada,     $vagas['ampla_privada']);
    $html .= _renderBlocoHtml('COTA TERRITORIAL PRIVADO',    'Cota Territorial Privado',    $cota_terr_privada, $vagas['cota_territorial_privada']);
    $html .= _renderBlocoPcdHtml($pcd_lista, $vagas['pcd']);

    $html .= "
<div class='parecer-box'>
    <div class='parecer-titulo'>PARECER Nº 010690/2025/SEDUC/ASJUR</div>
    <p>Embora a Lei Federal nº 15.142/2025 trate especificamente das cotas PPIQ, o modelo normativo federal de reserva de vagas é de: concorrência simultânea; dupla listagem; e não exclusão da ampla concorrência.</p>
    <p>Assim, candidatos inscritos em modalidades afirmativas territoriais não ficam restritos apenas à lista da reserva, devendo também figurar na ampla concorrência, preservando-se: <em>o princípio da isonomia; a ordem geral de classificação; e a competitividade entre todos os estudantes.</em></p>
</div>
<div class='doc-footer'>EEEP Manoel Mano — Crateús &nbsp;|&nbsp; $data</div>
</body></html>";

    return $html;
}

// ──────────────────────────────────────────────────────────────────
//  MODO WEB — acesso direto via URL (?curso=Enfermagem)
//  Só executa se este arquivo foi chamado diretamente pelo browser
// ──────────────────────────────────────────────────────────────────
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {

    session_start();

    if (!isset($_SESSION['usuario_id'])) {
        header("Location: index.HTML?erro=acesso_negado");
        exit();
    }

    require_once __DIR__ . '/../../vendor/autoload.php';

    $conn = new mysqli("localhost", "root", "", "sistema_login");
    if ($conn->connect_error) die("Erro: " . $conn->connect_error);
    $conn->set_charset("utf8mb4");

    $curso = isset($_GET['curso']) ? trim($_GET['curso']) : 'Enfermagem';

    $pdfBytes    = gerarPdfRanking($conn, $curso);
    $nomeArquivo = 'Resultado_Seletivo_2027_' . preg_replace('/[^a-zA-Z0-9]/', '_', $curso) . '.pdf';

    $conn->close();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
    header('Content-Length: ' . strlen($pdfBytes));
    header('Cache-Control: no-cache');
    echo $pdfBytes;
    exit();
}