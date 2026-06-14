<?php
// ══════════════════════════════════════════════
//  ocr_proxy_debug.php — Versão de diagnóstico
//  Use temporariamente para ver o que o GPT retorna
//  API: OpenAI gpt-4o-mini (vision)
// ══════════════════════════════════════════════

$OPENAI_KEY   = "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"; // ← cole sua chave aqui
$OPENAI_MODEL = "gpt-4o-mini";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["erro" => "Método não permitido."]);
    exit();
}

$body  = file_get_contents("php://input");
$dados = json_decode($body, true);

if (!isset($dados['imagem']) || !isset($dados['tipo'])) {
    http_response_code(400);
    echo json_encode(["erro" => "Parâmetros obrigatórios ausentes."]);
    exit();
}

$imagemBase64 = $dados['imagem'];
$imagemTipo   = $dados['tipo'];

$prompt = 'Você é um auditor especializado em históricos escolares brasileiros do Ensino Fundamental.
Sua missão: extrair as notas finais anuais SOMENTE do 6º, 7º, 8º e 9º ano.

LEI ABSOLUTA: Copie EXATAMENTE o número impresso. Nunca invente, estime ou interpole.
IGNORE COMPLETAMENTE os anos 1º ao 5º.

IDENTIFICAÇÃO DE LAYOUT:
A) Anos nas COLUNAS (Rota A - mais comum): cabeçalho com 1ºAno...9ºAno, ou 1ªSérie...9ªSérie, ou anos letivos 2017...2025
B) Anos nas LINHAS (Rota B): cada linha é "Resultado Final Xº Ano"
C) Bimestral (Rota C): sub-colunas por bimestre
D) Duplo Nota+CH (Rota D): cada ano tem coluna Nota e coluna CH lado a lado

MAPEAMENTO DE COLUNAS quando o cabeçalho usa ANOS LETIVOS:
  2017=1ºAno(ignorar) | 2018=2ºAno(ignorar) | 2019=3ºAno(ignorar) | 2020=4ºAno(ignorar) | 2021=5ºAno(ignorar)
  2022=6ºAno(usar→ano6) | 2023=7ºAno(usar→ano7) | 2024=8ºAno(usar→ano8) | 2025=9ºAno(usar→ano9)
NUNCA confunda col 2021 (5ºAno) com col 2022 (6ºAno).

DISCIPLINAS ACEITAS (apenas estas 10 chaves):
  portugues → Língua Portuguesa / LP / Port.
  matematica → Matemática / Mat.
  ciencias → Ciências / Ciências Naturais / CN
  historia → História / Hist.
  geografia → Geografia / Geo.
  ingles → Inglês / Língua Inglesa / LEM / Língua Estrangeira / Ling.est.mod.
  edfisica → Educação Física / Ed. Física / Recreação (só se não houver Ed. Física)
  artes → Arte / Artes / Arte-Educação / Educação Artística
  religiao → Ensino Religioso / Ens. Religioso / Rel.
  filosofia → Filosofia

IGNORAR: CH/Carga Horária (800,1000,1400,1600), Frequência(%), Dias Letivos(200,205),
Redação, Música, Libras, Proj.Vida, Empreendedorismo, Informática, Biologia, Física,
Química, Espanhol, Sociologia, Xadrez, Dança, Formação Socioemocional, Cultura Digital,
e qualquer outro nome não listado acima.

Responda SOMENTE com este JSON puro, sem markdown:
{"layout":"A","ano6":{"portugues":8.0,"matematica":9.0},"ano7":{...},"ano8":{...},"ano9":{...}}

Inclua apenas ano6/ano7/ano8/ano9. NUNCA ano1 a ano5. Use ponto decimal (9.5 não 9,5).
Omita disciplinas sem nota numérica. Se nada encontrado: {}

RETORNE SOMENTE O JSON.';

$payload = json_encode([
    "model"      => $OPENAI_MODEL,
    "max_tokens" => 500,
    "messages"   => [
        [
            "role"    => "user",
            "content" => [
                [
                    "type"      => "image_url",
                    "image_url" => [
                        "url"    => "data:{$imagemTipo};base64,{$imagemBase64}",
                        "detail" => "high"
                    ]
                ],
                [
                    "type" => "text",
                    "text" => $prompt
                ]
            ]
        ]
    ]
]);

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        "Content-Type: application/json",
        "Authorization: Bearer {$OPENAI_KEY}"
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$resposta   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErro   = curl_error($ch);
curl_close($ch);

if ($curlErro) {
    http_response_code(502);
    echo json_encode(["erro" => "cURL: $curlErro"]);
    exit();
}

$dadosResposta = json_decode($resposta, true);

// ══ RETORNA TUDO PARA DIAGNÓSTICO ══
echo json_encode([
    "http_status"       => $httpStatus,
    "modelo_usado"      => $OPENAI_MODEL,
    "resposta_completa" => $dadosResposta,
    "texto_extraido"    => $dadosResposta['choices'][0]['message']['content'] ?? 'CAMPO NAO ENCONTRADO',
    "finish_reason"     => $dadosResposta['choices'][0]['finish_reason'] ?? 'N/A',
    "json_raw_api"      => $resposta,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);