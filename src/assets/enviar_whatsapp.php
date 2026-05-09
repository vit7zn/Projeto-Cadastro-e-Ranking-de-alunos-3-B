<?php
// ══════════════════════════════════════════════════════════════════
//  enviar_whatsapp.php — Envio via Ultramsg
//  EEEP Manoel Mano
// ══════════════════════════════════════════════════════════════════

// ──────────────────────────────────────────
//  ⚙️  CONFIGURAÇÕES — edite aqui
// ──────────────────────────────────────────
define('ULTRAMSG_INSTANCE', 'instance174201');  // ex: instance12345
define('ULTRAMSG_TOKEN',    'g950xsqrr7s4ualp');       // token do painel Ultramsg
define('ESCOLA_NOME',       'EEEP Manoel Mano');

// Inclui gerador de PDF do ranking
require_once __DIR__ . '/gerar_pdf_ranking.php';

// ──────────────────────────────────────────
//  Monta a mensagem personalizada
// ──────────────────────────────────────────
function montarMensagem(array $aluno): string {
    $nome      = $aluno['nome_completo'];
    $curso     = $aluno['curso'];
    $situacao  = $aluno['situacao'];
    $media     = number_format($aluno['media_geral'], 5, ',', '.');
    $posicao   = $aluno['posicao'];
    $categoria = $aluno['categoria_ranking'];
    $escola    = ESCOLA_NOME;

    if ($situacao === 'CLASSIFICADO(A)') {
        $corpo = "🎉 *Resultado Seletivo 2026 — {$escola}*\n\n"
               . "Olá, responsável por *{$nome}*!\n\n"
               . "✅ *CLASSIFICADO(A)*\n\n"
               . "Temos o prazer de informar que *{$nome}* foi *APROVADO(A)* "
               . "no processo seletivo para o Curso Técnico em *{$curso}*!\n\n"
               . "📊 *Média Final:* {$media}\n"
               . "🏅 *Posição:* {$posicao}º lugar\n"
               . "📂 *Categoria:* {$categoria}\n\n"
               . "📎 O resultado completo está no PDF em anexo.\n\n"
               . "_Mensagem automática da {$escola}._";
    } else {
        $corpo = "😔 *Resultado Seletivo 2026 — {$escola}*\n\n"
               . "Olá, responsável por *{$nome}*!\n\n"
               . "❌ *NÃO CLASSIFICADO(A)*\n\n"
               . "Informamos que *{$nome}* não foi classificado(a) nesta edição "
               . "do processo seletivo para o Curso Técnico em *{$curso}*.\n\n"
               . "📊 *Média Final:* {$media}\n"
               . "📂 *Categoria:* {$categoria}\n\n"
               . "📎 O resultado completo está no PDF em anexo.\n\n"
               . "Não desanime! Novas oportunidades surgirão. 💪\n\n"
               . "_Mensagem automática da {$escola}._";
    }

    return $corpo;
}

// ──────────────────────────────────────────
//  Formata número: (88) 9 9999-9999 → 5588999999999
// ──────────────────────────────────────────
function formatarTelefone(string $tel): string {
    $tel = preg_replace('/\D/', '', $tel);
    if (substr($tel, 0, 2) !== '55') {
        $tel = '55' . $tel;
    }
    return $tel;
}

// ──────────────────────────────────────────
//  Envia via Ultramsg API
//  Retorna ['ok' => true] ou ['ok' => false, 'erro' => '...']
// ──────────────────────────────────────────
function enviarWhatsapp(string $telefone, string $mensagem): array {
    $numero = formatarTelefone($telefone);

    $url  = "https://api.ultramsg.com/" . ULTRAMSG_INSTANCE . "/messages/chat";
    $data = http_build_query([
        'token'    => ULTRAMSG_TOKEN,
        'to'       => $numero,
        'body'     => $mensagem,
        'priority' => 1,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $resposta  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErro  = curl_error($ch);
    curl_close($ch);

    if ($curlErro) {
        return ['ok' => false, 'erro' => "cURL: {$curlErro}", 'raw' => ''];
    }

    $json = json_decode($resposta, true);

    // Ultramsg retorna {"sent":"true"} em sucesso
    if ($httpCode === 200 && isset($json['sent']) && $json['sent'] === 'true') {
        return ['ok' => true, 'raw' => $resposta];
    }

    $erroMsg = $json['error'] ?? $json['message'] ?? "Resposta inesperada (HTTP {$httpCode})";
    return ['ok' => false, 'erro' => $erroMsg, 'raw' => $resposta];
}


// ──────────────────────────────────────────
//  Envia mensagem de texto + PDF em anexo
//  Fluxo: 1) envia texto, 2) envia documento base64
// ──────────────────────────────────────────
function enviarWhatsappComPdf(mysqli $conn, string $telefone, string $mensagem, string $curso): array {
    // Passo 1 — envia a mensagem de texto normalmente
    $resultadoTexto = enviarWhatsapp($telefone, $mensagem);
    if (!$resultadoTexto['ok']) {
        return $resultadoTexto; // se o texto falhou, nem tenta o PDF
    }

    // Passo 2 — gera o PDF do ranking do curso em memória
    try {
        $pdfBytes = gerarPdfRanking($conn, $curso);
    } catch (\Throwable $e) {
        // PDF falhou mas texto foi enviado — registra aviso mas não marca como erro
        return ['ok' => true, 'raw' => $resultadoTexto['raw'], 'aviso' => 'PDF não gerado: ' . $e->getMessage()];
    }

    // Passo 3 — envia o PDF como documento via /messages/document (base64)
    $numero      = formatarTelefone($telefone);
    $pdfBase64   = base64_encode($pdfBytes);
    $nomeArquivo = 'Resultado_Seletivo_2026_' . preg_replace('/[^a-zA-Z0-9]/', '_', $curso) . '.pdf';

    $url  = "https://api.ultramsg.com/" . ULTRAMSG_INSTANCE . "/messages/document";
    $data = http_build_query([
        'token'    => ULTRAMSG_TOKEN,
        'to'       => $numero,
        'filename' => $nomeArquivo,
        'document' => $pdfBase64,
        'caption'  => '📄 Resultado completo do processo seletivo 2026',
        'priority' => 1,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 60, // PDF pode demorar mais
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $resposta  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErro  = curl_error($ch);
    curl_close($ch);

    if ($curlErro) {
        return ['ok' => true, 'raw' => $resultadoTexto['raw'], 'aviso' => "PDF enviado com erro cURL: $curlErro"];
    }

    $json = json_decode($resposta, true);
    if ($httpCode === 200 && isset($json['sent']) && $json['sent'] === 'true') {
        return ['ok' => true, 'raw' => $resposta];
    }

    $erroMsg = $json['error'] ?? $json['message'] ?? "HTTP $httpCode";
    // Texto foi enviado mas PDF falhou — ainda marca ok, mas com aviso
    return ['ok' => true, 'raw' => $resultadoTexto['raw'], 'aviso' => "PDF não enviado: $erroMsg"];
}

// ──────────────────────────────────────────
//  Registra no log_whatsapp
// ──────────────────────────────────────────
function registrarLog(mysqli $conn, int $alunoId, string $telefone, string $mensagem, array $resultado): void {
    $status      = $resultado['ok'] ? 'enviado' : 'erro';
    $respostaApi = $resultado['raw'] ?? ($resultado['erro'] ?? '');

    $stmt = $conn->prepare(
        "INSERT INTO log_whatsapp (aluno_id, telefone, mensagem, status, resposta_api)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issss", $alunoId, $telefone, $mensagem, $status, $respostaApi);
    $stmt->execute();
    $stmt->close();

    if ($resultado['ok']) {
        $stmt2 = $conn->prepare(
            "UPDATE alunos SET whatsapp_enviado = 1, whatsapp_enviado_em = NOW() WHERE id = ?"
        );
        $stmt2->bind_param("i", $alunoId);
        $stmt2->execute();
        $stmt2->close();
    }
}

// ──────────────────────────────────────────
//  Calcula situação e posição do aluno no ranking
//  Usa exatamente a mesma lógica de filtro do ranking.php
// ──────────────────────────────────────────
function calcularSituacao(mysqli $conn, array $aluno): array {

    // Vagas idênticas às do ranking.php
    $vagas = [
        'ampla_publica'            => 27,
        'cota_territorial_publica' => 9,
        'ampla_privada'            => 6,
        'cota_territorial_privada' => 3,
        'pcd'                      => 2,
    ];

    $curso = $conn->real_escape_string($aluno['curso']);
    $pcd   = $aluno['pcd']                 ?? 'nao';
    $proc  = $aluno['procedencia_escolar'] ?? '';
    $cota  = $aluno['cota_local']          ?? 'nao';

    // Determina a lista correta — mesma lógica do ranking.php
    if ($pcd === 'sim') {
        $chave   = 'pcd';
        $sqlLista = "SELECT id FROM alunos
                     WHERE curso LIKE '%$curso%' AND pcd='sim'
                     ORDER BY media_geral DESC";

    } elseif ($proc === 'privada' && $cota === 'sim') {
        $chave   = 'cota_territorial_privada';
        $sqlLista = "SELECT id FROM alunos
                     WHERE curso LIKE '%$curso%' AND procedencia_escolar='privada'
                       AND cota_local='sim' AND pcd='nao'
                     ORDER BY media_geral DESC";

    } elseif ($proc === 'privada') {
        $chave   = 'ampla_privada';
        $sqlLista = "SELECT id FROM alunos
                     WHERE curso LIKE '%$curso%' AND procedencia_escolar='privada'
                       AND pcd='nao'
                     ORDER BY media_geral DESC";

    } elseif ($proc === 'publica' && $cota === 'sim') {
        $chave   = 'cota_territorial_publica';
        $sqlLista = "SELECT id FROM alunos
                     WHERE curso LIKE '%$curso%' AND procedencia_escolar='publica'
                       AND cota_local='sim' AND pcd='nao'
                     ORDER BY media_geral DESC";

    } else {
        $chave   = 'ampla_publica';
        $sqlLista = "SELECT id FROM alunos
                     WHERE curso LIKE '%$curso%' AND procedencia_escolar='publica'
                       AND pcd='nao'
                     ORDER BY media_geral DESC";
    }

    $vagasCateg = $vagas[$chave];
    $res        = $conn->query($sqlLista);

    $posicao    = 1;
    $encontrado = false;
    while ($row = $res->fetch_assoc()) {
        if ((int)$row['id'] === (int)$aluno['id']) {
            $encontrado = true;
            break;
        }
        $posicao++;
    }

    // Se não encontrou por alguma inconsistência, marca NÃO CLASSIFICADO por segurança
    if (!$encontrado) {
        return ['posicao' => 0, 'situacao' => 'NÃO CLASSIFICADO(A)'];
    }

    $situacao = ($posicao <= $vagasCateg) ? 'CLASSIFICADO(A)' : 'NÃO CLASSIFICADO(A)';
    return ['posicao' => $posicao, 'situacao' => $situacao];
}