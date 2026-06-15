<?php
// ══════════════════════════════════════════════
//  ocr_proxy_debug.php — Versão de diagnóstico
//  Protegido via arquivo de variáveis locais .env
// ══════════════════════════════════════════════

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

$OPENAI_KEY = "";
if (file_exists(__DIR__ . '/.env')) {
    $linhas = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        if (strpos(trim($linha), '#') === 0) continue;
        if (strpos($linha, '=') !== false) {
            list($nome, $valor) = explode('=', $linha, 2);
            if (trim($nome) === 'OPENAI_API_KEY_DEBUG') {
                $OPENAI_KEY = trim($valor);
            }
        }
    }
}

if (empty($OPENAI_KEY)) {
    http_response_code(500);
    echo json_encode(["erro" => "Chave de depuração não configurada no ambiente."]);
    exit();
}

$OPENAI_MODEL = "gpt-4o-mini";

$body  = file_get_contents("php://input");
$dados = json_decode($body, true);

if (!isset($dados['imagem']) || empty($dados['imagem'])) {
    http_response_code(400);
    echo json_encode(["erro" => "Nenhuma imagem em base64 fornecida."]);
    exit();
}

$imagemBase64 = $dados['imagem'];
$imagemTipo   = isset($dados['tipo']) ? $dados['tipo'] : 'image/jpeg';

if (preg_match('/^data:image\/(\w+);base64,/', $imagemBase64, $tipoRetornado)) {
    $imagemBase64 = substr($imagemBase64, strpos($imagemBase64, ',') + 1);
}

$prompt = "Você é um diagnosticador estrutural escolar. Analise o documento e descreva a formatação visual e os campos de notas identificados.";

$payload = json_encode([
    "model"       => $OPENAI_MODEL,
    "temperature" => 0.0,
    "messages"    => [
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

echo json_encode([
    "http_status"       => $httpStatus,
    "modelo_usado"      => $OPENAI_MODEL,
    "resposta_completa" => $dadosResposta
]);