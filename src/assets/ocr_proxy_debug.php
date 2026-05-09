<?php
// ══════════════════════════════════════════════
//  ocr_proxy_debug.php — Versão de diagnóstico
//  Use temporariamente para ver o que o Gemini retorna
// ══════════════════════════════════════════════

$API_KEY = "AIzaSyAW9E_m4-2hTcfXHCWjshsnaCZ8tTbiuWc";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(["erro" => "Método não permitido."]); exit(); }

$body  = file_get_contents("php://input");
$dados = json_decode($body, true);

if (!isset($dados['imagem']) || !isset($dados['tipo'])) {
    http_response_code(400);
    echo json_encode(["erro" => "Parâmetros obrigatórios ausentes."]);
    exit();
}

$imagemBase64 = $dados['imagem'];
$imagemTipo   = $dados['tipo'];

$prompt = 'Analise a imagem de um boletim escolar e extraia todos os valores numericos de notas visiveis.

Responda SOMENTE com este JSON puro, sem markdown, sem blocos de codigo, sem texto antes ou depois:
{"notas":[8.0,7.0,9.0],"obs":""}

Regras:
- "notas" deve ser um array com todos os numeros entre 0 e 10 que voce encontrar na imagem
- Use ponto como separador decimal (ex: 7.5 nao 7,5)
- "obs" pode ficar vazio
- Se nao for um boletim, responda: {"erro":"Imagem invalida"}';

$payload = json_encode([
    "contents" => [[
        "parts" => [
            ["inline_data" => ["mime_type" => $imagemTipo, "data" => $imagemBase64]],
            ["text" => $prompt]
        ]
    ]],
    "generationConfig" => [
        "temperature"     => 0.0,
        "maxOutputTokens" => 300
    ]
]);

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$API_KEY";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$resposta   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErro   = curl_error($ch);
curl_close($ch);

if ($curlErro) { http_response_code(502); echo json_encode(["erro" => "cURL: $curlErro"]); exit(); }

$dadosResposta = json_decode($resposta, true);

// ══ RETORNA TUDO PARA DIAGNÓSTICO ══
echo json_encode([
    "http_status"       => $httpStatus,
    "resposta_completa" => $dadosResposta,
    "texto_extraido"    => $dadosResposta['candidates'][0]['content']['parts'][0]['text'] ?? 'CAMPO NAO ENCONTRADO',
    "finish_reason"     => $dadosResposta['candidates'][0]['finishReason'] ?? 'N/A',
    "json_raw_api"      => $resposta,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
