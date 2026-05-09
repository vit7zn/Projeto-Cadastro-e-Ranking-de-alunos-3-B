<?php
// ══════════════════════════════════════════════════════════════════
//  cron_whatsapp.php — Disparo AUTOMÁTICO agendado (cron job)
//  EEEP Manoel Mano
//
//  Como configurar no servidor (cPanel / terminal Linux):
//  ----------------------------------------------------------
//  No cPanel → Cron Jobs, adicione:
//    0 10 15 7 * php /home/seuusuario/public_html/cron_whatsapp.php
//
//  Isso roda às 10:00 do dia 15 de julho.
//  Ajuste a data conforme o dia do resultado oficial.
//
//  Formato: minuto hora dia mês semana
//  Exemplos:
//    0 8  * * *   → todo dia às 08:00
//    0 10 20 * *  → dia 20 de cada mês às 10:00
//    0 9  15 7 *  → dia 15 de julho às 09:00
//
//  Para testar manualmente no terminal:
//    php cron_whatsapp.php --force
// ══════════════════════════════════════════════════════════════════

// Garante que só rode via CLI ou com parâmetro --force (segurança)
$isCLI   = php_sapi_name() === 'cli';
$isForce = in_array('--force', $argv ?? []);

if (!$isCLI && !$isForce) {
    http_response_code(403);
    die("Acesso negado. Este script deve ser executado via cron job.");
}

require_once __DIR__ . '/enviar_whatsapp.php';

// ── Conexão ──
$conn = new mysqli("localhost", "root", "", "sistema_login");
if ($conn->connect_error) {
    echo "[ERRO] Conexão falhou: " . $conn->connect_error . PHP_EOL;
    exit(1);
}
$conn->set_charset("utf8mb4");

echo "═══════════════════════════════════════════" . PHP_EOL;
echo " EEEP Manoel Mano — Disparo Automático WA " . PHP_EOL;
echo " " . date('d/m/Y H:i:s') . PHP_EOL;
echo "═══════════════════════════════════════════" . PHP_EOL;

// ── Busca alunos com telefone que ainda não receberam ──
$result = $conn->query(
    "SELECT * FROM alunos 
     WHERE telefone_responsavel IS NOT NULL 
     AND telefone_responsavel != ''
     AND whatsapp_enviado = 0
     ORDER BY curso, categoria_ranking, media_geral DESC"
);

if (!$result || $result->num_rows === 0) {
    echo "[INFO] Nenhum aluno pendente de envio. Encerrando." . PHP_EOL;
    exit(0);
}

$totalOk   = 0;
$totalErro = 0;

while ($aluno = $result->fetch_assoc()) {
    // Calcula situação no ranking
    $sit = calcularSituacao($conn, $aluno);
    $aluno['posicao'] = $sit['posicao'];
    $aluno['situacao'] = $sit['situacao'];

    // Monta a mensagem
    $mensagem = montarMensagem($aluno);

    // Envia
    $resultado = enviarWhatsappComPdf($conn, $aluno['telefone_responsavel'], $mensagem, $aluno['curso']);

    // Registra no log
    registrarLog($conn, $aluno['id'], $aluno['telefone_responsavel'], $mensagem, $resultado);

    $status = $resultado['ok'] ? '✅ OK' : '❌ ERRO';
    $detalhe = $resultado['ok'] ? $aluno['situacao'] : ($resultado['erro'] ?? '');

    echo sprintf(
        "[%s] %-40s | %s | Tel: %s | %s%s",
        $status,
        mb_substr($aluno['nome_completo'], 0, 40),
        $aluno['curso'],
        $aluno['telefone_responsavel'],
        $detalhe,
        PHP_EOL
    );

    if ($resultado['ok']) $totalOk++;
    else $totalErro++;

    // 1 segundo de pausa entre cada envio (evita bloqueio da API)
    sleep(1);
}

echo "═══════════════════════════════════════════" . PHP_EOL;
echo " Concluído: {$totalOk} enviados · {$totalErro} erros" . PHP_EOL;
echo "═══════════════════════════════════════════" . PHP_EOL;

$conn->close();
exit(0);
