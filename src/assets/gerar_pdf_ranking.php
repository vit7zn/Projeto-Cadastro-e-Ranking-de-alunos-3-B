<?php
// ══════════════════════════════════════════════════════════════════
//  gerar_pdf_ranking.php — Gera PDF do ranking de um curso
//  Retorna o conteúdo binário do PDF
//  EEEP Manoel Mano
//
//  Dependência: mPDF
//  Instalar via Composer na raiz do projeto:
//    composer require mpdf/mpdf
// ══════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../../vendor/autoload.php';

function gerarPdfRanking(mysqli $conn, string $curso): string {

    // ── Vagas (idêntico ao ranking.php) ──
    $vagas = [
        'ampla_publica'            => 27,
        'cota_territorial_publica' => 9,
        'ampla_privada'            => 6,
        'cota_territorial_privada' => 3,
        'pcd'                      => 2,
    ];

    $cursoEsc = $conn->real_escape_string($curso);

    // ── Busca alunos por categoria (mesma lógica do ranking.php) ──
    function buscarPdf($conn, $sql) {
        $r = $conn->query($sql); $l = [];
        if ($r) while ($row = $r->fetch_assoc()) $l[] = $row;
        return $l;
    }

    $ampla_publica     = buscarPdf($conn, "SELECT * FROM alunos WHERE curso LIKE '%$cursoEsc%' AND procedencia_escolar='publica'  AND pcd='nao' ORDER BY media_geral DESC");
    $cota_terr_publica = buscarPdf($conn, "SELECT * FROM alunos WHERE curso LIKE '%$cursoEsc%' AND procedencia_escolar='publica'  AND cota_local='sim' AND pcd='nao' ORDER BY media_geral DESC");
    $ampla_privada     = buscarPdf($conn, "SELECT * FROM alunos WHERE curso LIKE '%$cursoEsc%' AND procedencia_escolar='privada'  AND pcd='nao' ORDER BY media_geral DESC");
    $cota_terr_privada = buscarPdf($conn, "SELECT * FROM alunos WHERE curso LIKE '%$cursoEsc%' AND procedencia_escolar='privada'  AND cota_local='sim' AND pcd='nao' ORDER BY media_geral DESC");
    $pcd_lista         = buscarPdf($conn, "SELECT * FROM alunos WHERE curso LIKE '%$cursoEsc%' AND pcd='sim' ORDER BY media_geral DESC");

    // ── Monta HTML do ranking ──
    $cursoLabel = strtoupper($curso);
    $data       = date('d/m/Y');

    $html = "
    <html><head><meta charset='UTF-8'>
    <style>
        body  { font-family: Arial, sans-serif; font-size: 10px; color: #000; }
        h1    { font-size: 13px; text-align: center; margin: 0 0 2px; }
        h2    { font-size: 11px; text-align: center; color: #555; margin: 0 0 14px; }
        .secao-titulo {
            background: #388e3c; color: #fff; font-size: 10px; font-weight: bold;
            text-transform: uppercase; padding: 5px 8px; letter-spacing: .5px;
            margin-top: 14px;
        }
        .secao-titulo.vermelho { background: #c62828; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 2px; }
        th    { background: #f0f0f0; border: 1px solid #000; padding: 4px 5px;
                font-weight: bold; text-transform: uppercase; font-size: 8px; text-align: center; }
        td    { border: 1px solid #888; padding: 4px 5px; text-align: center; }
        td.nome { text-align: left; text-transform: uppercase; }
        .class  { font-weight: bold; }
        .class .sit { color: #1a5c1a; }
        .nao    { color: #c62828; font-style: italic; }
        .nao .sit { font-weight: bold; }
        .vazio  { text-align: center; font-style: italic; color: #888; padding: 6px; }
        .rodape { text-align: center; font-size: 8px; color: #888; margin-top: 16px; }
    </style></head><body>
    <h1>Processo Seletivo – EEEP Manoel Mano 2026</h1>
    <h2>RESULTADO PRELIMINAR – $cursoLabel</h2>
    ";

    // Função interna para renderizar bloco
    $renderBloco = function(string $titulo, string $tipoLabel, array $alunos, int $vagas_n) use (&$html) {
        $class  = array_slice($alunos, 0, $vagas_n);
        $nclass = array_slice($alunos, $vagas_n);

        // Classificados
        $html .= "<div class='secao-titulo'>$titulo — CLASSIFICADOS</div>";
        $html .= "<table><thead><tr><th>Ord.</th><th>Nome</th><th>Tipo</th><th>Situação</th><th>Média</th></tr></thead><tbody>";
        if (empty($class)) {
            $html .= "<tr><td colspan='5' class='vazio'>Nenhuma inscrição</td></tr>";
        } else {
            foreach ($class as $i => $a) {
                $pos   = $i + 1;
                $media = number_format((float)($a['media_geral'] ?? 0), 5, ',', '.');
                $nome  = htmlspecialchars(mb_strtoupper($a['nome_completo']));
                $html .= "<tr class='class'><td>$pos</td><td class='nome'>$nome</td>
                          <td>" . htmlspecialchars(strtoupper($tipoLabel)) . "</td>
                          <td class='sit'>CLASSIFICADO(A)</td><td>$media</td></tr>";
            }
        }
        $html .= "</tbody></table>";

        // Não classificados
        $html .= "<div class='secao-titulo vermelho'>$titulo — NÃO CLASSIFICADOS</div>";
        $html .= "<table><thead><tr><th>Ord.</th><th>Nome</th><th>Tipo</th><th>Situação</th><th>Média</th></tr></thead><tbody>";
        if (empty($nclass)) {
            $html .= "<tr><td colspan='5' class='vazio'>— nenhum candidato —</td></tr>";
        } else {
            foreach ($nclass as $i => $a) {
                $pos   = $vagas_n + $i + 1;
                $media = number_format((float)($a['media_geral'] ?? 0), 5, ',', '.');
                $nome  = htmlspecialchars(mb_strtoupper($a['nome_completo']));
                $html .= "<tr class='nao'><td>$pos</td><td class='nome'>$nome</td>
                          <td>" . htmlspecialchars(strtoupper($tipoLabel)) . "</td>
                          <td class='sit'>NÃO CLASSIFICADO(A)</td><td>$media</td></tr>";
            }
        }
        $html .= "</tbody></table>";
    };

    $renderBloco('AMPLA CONCORRÊNCIA PÚBLICA',   'Ampla Concorrência Pública',   $ampla_publica,     $vagas['ampla_publica']);
    $renderBloco('COTA TERRITORIAL PÚBLICA',     'Cota Territorial Pública',     $cota_terr_publica, $vagas['cota_territorial_publica']);
    $renderBloco('AMPLA CONCORRÊNCIA PRIVADO',   'Ampla Concorrência Privado',   $ampla_privada,     $vagas['ampla_privada']);
    $renderBloco('COTA TERRITORIAL PRIVADO',     'Cota Territorial Privado',     $cota_terr_privada, $vagas['cota_territorial_privada']);
    $renderBloco('COTA PESSOA COM DEFICIÊNCIA',  'Cota PCD',                     $pcd_lista,         $vagas['pcd']);

    $html .= "<div class='rodape'>EEEP Manoel Mano — Crateús | Gerado em $data</div>";
    $html .= "</body></html>";

    // ── Gera o PDF com mPDF ──
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'margin_top'    => 12,
        'margin_bottom' => 10,
        'margin_left'   => 12,
        'margin_right'  => 12,
        'tempDir'       => sys_get_temp_dir(),
    ]);
    $mpdf->SetTitle("Resultado Seletivo 2026 – $curso");
    $mpdf->WriteHTML($html);

    // Retorna o PDF como string binária (não faz output direto)
    return $mpdf->Output('', 'S');
}
