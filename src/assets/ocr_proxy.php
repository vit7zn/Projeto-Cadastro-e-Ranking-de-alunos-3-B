<?php
require_once __DIR__ . '/env_loader.php';
// ══════════════════════════════════════════════════════════════
//  ocr_proxy.php — OCR de Histórico Escolar Brasileiro
//  Versão: 12.0 — Prompt auditivo em fases, universal, zero interpolação
//  Layouts suportados: A (horizontal), B (vertical/compacto),
//                      C (bimestral), D (Nota+CH duplas)
//  API: OpenAI GPT-4o (vision) com fallback automático
// ══════════════════════════════════════════════════════════════

$OPENAI_KEYS = array_filter([
    getenv('OPENAI_API_KEY_PROD'),
    // getenv('OPENAI_API_KEY_EXTRA'), // adicione mais chaves no .env se quiser
]);

$OPENAI_MODEL = "gpt-4o";

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
    echo json_encode(["erro" => "Parâmetros 'imagem' e 'tipo' são obrigatórios."]);
    exit();
}

$imagemBase64 = $dados['imagem'];
$imagemTipo   = $dados['tipo'];

$tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($imagemTipo, $tiposPermitidos)) {
    http_response_code(400);
    echo json_encode(["erro" => "Tipo não suportado: $imagemTipo."]);
    exit();
}

$prompt = <<<'PROMPT'
Você é um sistema especializado em extrair notas de históricos escolares brasileiros.
Retorne APENAS JSON puro. Sem markdown. Sem texto fora do JSON.

════════════════════════════════════════════════════════════
TAREFA: extrair notas do 6º, 7º, 8º e 9º ano APENAS.
════════════════════════════════════════════════════════════

PASSO 1 — CONTE AS COLUNAS DE ANOS NA TABELA
─────────────────────────────────────────────
Antes de ler qualquer nota, conte fisicamente quantas colunas de anos existem.

Exemplo com 9 colunas:
  Col1=1ºAno | Col2=2ºAno | Col3=3ºAno | Col4=4ºAno | Col5=5ºAno | Col6=6ºAno | Col7=7ºAno | Col8=8ºAno | Col9=9ºAno

Se houver grupos no cabeçalho:
  "Fundamental 1º Seguimento" → agrupa Col1 a Col5 (cinco colunas)
  "Fundamental 2º Seguimento" → agrupa Col6 a Col9 (quatro colunas)
  → Col6 = 6ºAno. Col7 = 7ºAno. Col8 = 8ºAno. Col9 = 9ºAno.
  → ⛔ Col5 = 5ºAno ≠ 6ºAno. São colunas DIFERENTES.

Se o cabeçalho usar anos letivos (2017...2025):
  → Encontre a coluna do 1ºAno. Some 5 → chega ao 6ºAno.
  → Ex: 1ºAno=2017 → 6ºAno=2022 → 7ºAno=2023 → 8ºAno=2024 → 9ºAno=2025
  → ⛔ 2021=5ºAno ≠ 6ºAno.

Se usar duplo rótulo (5ªSérie/6ºAno):
  → O número APÓS a barra indica o ano. 5ªSérie/6ºAno = 6ºAno.

Se cada LINHA for um ano (ex: "Resultado Final 6º Ano 2022 | 10,0 | 9,5 | ..."):
  → Localize a linha do 6º, 7º, 8º, 9º ano.
  → As colunas são disciplinas — identifique pelo cabeçalho da página.

Se cada ano tiver par NOTA + CH:
  → Use apenas a coluna NOTA. Ignore CH (800, 1000, 1080, 1400, 1600).

PASSO 2 — IGNORE LINHAS DE RODAPÉ DA TABELA
─────────────────────────────────────────────
As linhas abaixo NÃO são disciplinas — NUNCA extraia valores delas:
  • Média de Aprovação / Média Geral → tem valores como 6,0 que parecem notas mas NÃO são
  • Carga Horária Anual / C.H. Total → 800, 1000, 1080, 1400, 1600
  • Frequência % / Taxa de Frequência → 98%, 100%
  • Total de Dias Letivos → 200, 205
  • Resultado Final → AP, RP, Aprovado, Reprovado
  • Oferta Anual → 1000, 1400, 1600, 1800

PASSO 3 — IDENTIFIQUE CADA DISCIPLINA PELO NOME
─────────────────────────────────────────────────
Procure o nome EXATO na tabela. Nunca use posição da linha.

"portugues"  ← Língua Portuguesa / Port. / LP / Língua Port.
"matematica" ← Matemática / Mat.
"historia"   ← História / Hist.
"geografia"  ← Geografia / Geo. / Geog. / Est. Sociais
"ciencias"   ← Ciências / Ciências Naturais / Ciências da Natureza / CN / C.Nat
"artes"      ← Arte / Artes / Arte-Educação / Educação Artística / Arte educação
"edfisica"   ← Educação Física / Ed. Física / Educ. Física
              Recreação → use SOMENTE se não houver Educação Física no mesmo boletim
"religiao"   ← Ensino Religioso / Ens. Religioso / E.Religioso / Educação Religiosa / Filosofia
"ingles"     ← Inglês / Língua Inglesa / Língua Estrangeira / LEM / Ling.est.mod. / L.E.Inglês

IGNORE completamente qualquer outra disciplina:
Redação, Música, Libras, Projeto de Vida, Empreendedorismo, Informática, Biologia,
Física, Química, Espanhol, Sociologia, Xadrez, Dança, Formação Socioemocional,
Cultura Digital, Textos e Contextos, Matematizando, Jogos de Raciocínio, Cidadania,
Nivelamento, Imersão, Esporte, Proj.Caminhar, Saúde, Corpo e Movimento,
Horário de Estudos, Técnicas Agrícolas, e outros não listados acima.

PASSO 4 — VALORES VÁLIDOS
─────────────────────────────────────────────
• Copie o número EXATO impresso. Nunca calcule nem estime.
• Valores válidos: 0.0 a 10.0. Use ponto decimal (9,5 → 9.5).
• Ignore: traço (-), asterisco (*), barra (/), AP, RP, AS, ANS.
• Ignore conceitos: O, MB, B, R, NS, S (letras = conceito, não nota).
• Ignore: 6,0 ou 6.0 na linha "Média de Aprovação" — não é nota de disciplina.
• Sônia Burgos: anos 1º-4º têm letras O/B/MB → ignore completamente esses anos.
• Primeiro de Janeiro: Filosofia → mapear para "religiao".

════════════════════════════════════════════════════════════
EXEMPLO REAL — boletim com grupos Fundamental 1º/2º Seguimento:
════════════════════════════════════════════════════════════

Tabela vista:
              | 1ºAno | 2ºAno | 3ºAno | 4ºAno | 5ºAno | 6ºAno | 7ºAno | 8ºAno | 9ºAno |
Língua Port.  |  9,0  | 10,0  |  9,5  |  9,0  |  7,0  | 10,0  |  9,0  |  9,0  |  9,0  |
Arte          |  9,0  | 10,0  | 10,0  | 10,0  |  7,0  |  8,0  |  8,0  | 10,0  | 10,0  |
Ed. Física    |  9,5  | 10,0  | 10,0  |   –   |  7,0  |  8,0  |  9,0  |  9,0  |  9,0  |
História      |  9,0  | 10,0  | 10,0  |  9,0  |  8,0  | 10,0  |  8,0  |  8,0  |  8,0  |
Geografia     |  9,0  | 10,0  | 10,0  |  9,5  |  8,0  |  9,0  |  8,0  |  8,0  |  9,0  |
Ens. Religioso|  9,5  | 10,0  |  9,5  | 10,0  |  7,0  | 10,0  |  9,0  |  8,0  |  8,0  |
Ciências Nat. |  9,5  | 10,0  | 10,0  |  9,0  |  8,0  | 10,0  |  9,0  |  9,0  |  9,0  |
Matemática    |  9,0  | 10,0  | 10,0  |  9,0  |  7,0  |  8,0  |  8,0  | 10,0  | 10,0  |
Ling.est.mod. |  9,0  |  9,5  | 10,0  |  8,0  |  8,0  |  8,0  |  9,0  | 10,0  |  9,0  |
Média Aprov.  |       |  6,0  |  6,0  |  6,0  |  6,0  |  6,0  |  6,0  |  6,0  |  6,0  |  ← IGNORAR
Carga Horária |  1000 |  1000 |  1000 |  1000 |  1400 |  1000 |  1000 |  1800 |  1600 |  ← IGNORAR
Resultado     |  AP   |  AP   |  AP   |  AP   |  AP   |  AP   |  AP   |  AP   |  AP   |  ← IGNORAR

Raciocínio correto:
- Há 9 colunas de anos
- Grupo "1º Seguimento" = colunas 1 a 5 (1ºAno ao 5ºAno) → IGNORAR
- Grupo "2º Seguimento" = colunas 6 a 9 (6ºAno ao 9ºAno) → USAR
- Língua Portuguesa no 6ºAno (Col6) = 10,0 ← NÃO é 9,0 (Col5=5ºAno) nem 7,0 (Col5=5ºAno)
- Matemática no 6ºAno (Col6) = 8,0 ← NÃO é 6,0 (linha Média de Aprovação)

JSON correto para esse exemplo:
{"ano6":{"portugues":10.0,"matematica":8.0,"historia":10.0,"geografia":9.0,"ciencias":10.0,"artes":8.0,"edfisica":8.0,"religiao":10.0,"ingles":8.0},"ano7":{"portugues":9.0,"matematica":8.0,"historia":8.0,"geografia":8.0,"ciencias":9.0,"artes":8.0,"edfisica":9.0,"religiao":9.0,"ingles":9.0},"ano8":{"portugues":9.0,"matematica":10.0,"historia":8.0,"geografia":8.0,"ciencias":9.0,"artes":10.0,"edfisica":9.0,"religiao":8.0,"ingles":10.0},"ano9":{"portugues":9.0,"matematica":10.0,"historia":8.0,"geografia":9.0,"ciencias":9.0,"artes":10.0,"edfisica":9.0,"religiao":8.0,"ingles":9.0}}

════════════════════════════════════════════════════════════
AGORA EXTRAIA AS NOTAS DO DOCUMENTO ENVIADO:
════════════════════════════════════════════════════════════

Retorne APENAS o JSON com ano6, ano7, ano8, ano9.
Se não encontrar notas → retorne {}
PROMPT;

// ══════════════════════════════════════════════
//  FUNÇÃO: Chamada à OpenAI GPT-4o
// ══════════════════════════════════════════════
function chamarOpenAI(string $key, string $imagemBase64, string $imagemTipo, string $prompt, string $modelo): array {

    $payload = json_encode([
        "model"      => $modelo,
        "max_tokens" => 3000,
        "messages"   => [
            [
                "role"    => "system",
                "content" => "Você extrai notas de históricos escolares brasileiros. Responda SOMENTE com JSON puro. Nenhuma palavra antes ou depois. Nenhum markdown. Apenas o objeto JSON."
            ],
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
            "Authorization: Bearer {$key}"
        ],
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $resposta   = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErro   = curl_error($ch);
    curl_close($ch);

    return [$resposta, $httpStatus, $curlErro];
}

// ══════════════════════════════════════════════
//  EXECUÇÃO: rotação de chaves + fallback
// ══════════════════════════════════════════════
$modelos = [$OPENAI_MODEL, 'gpt-4o-mini'];

$texto         = '';
$fonteUsada    = '';
$layoutUsado   = '';
$respostaFinal = '';
$statusFinal   = 0;

$chavesEmbaralhadas = $OPENAI_KEYS;
shuffle($chavesEmbaralhadas);

foreach ($chavesEmbaralhadas as $indiceChave => $chaveAtual) {
    foreach ($modelos as $modelo) {
        [$resposta, $status, $curlErro] = chamarOpenAI($chaveAtual, $imagemBase64, $imagemTipo, $prompt, $modelo);

        $respostaFinal = $resposta;
        $statusFinal   = $status;

        if ($curlErro) continue;
        if ($status === 429) break;

        if ($status === 200) {
            $dadosApi = json_decode($resposta, true);
            $t        = $dadosApi['choices'][0]['message']['content'] ?? '';
            if (!empty(trim($t))) {
                $texto      = $t;
                $fonteUsada = $modelo . ' (chave ' . ($indiceChave + 1) . ')';
                break 2;
            }
        }
    }

    if (!empty(trim($texto))) break;
}

// ── Todas as tentativas falharam ──
if (empty(trim($texto))) {
    $dadosUlt   = json_decode($respostaFinal, true);
    $msgErro    = $dadosUlt['error']['message'] ?? '';
    $retryAfter = 60;

    if (preg_match('/try again in ([\d.]+)s/i', $msgErro, $m)) {
        $retryAfter = (int) ceil((float) $m[1]);
    }

    if ($statusFinal === 429) {
        http_response_code(429);
        echo json_encode([
            "erro"        => "Limite de requisições atingido. Aguarde {$retryAfter}s e tente novamente.",
            "retry_after" => $retryAfter,
        ]);
    } elseif ($statusFinal === 401) {
        http_response_code(401);
        echo json_encode(["erro" => "Chave OpenAI inválida. Verifique em https://platform.openai.com/api-keys"]);
    } else {
        http_response_code(502);
        echo json_encode(["erro" => "Falha ao processar o arquivo. Tente novamente."]);
    }
    exit();
}

// ══════════════════════════════════════════════
//  LIMPEZA E PARSE DO JSON
// ══════════════════════════════════════════════
$texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);
$texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
$texto = preg_replace('/```[a-zA-Z]*\s*/i', '', $texto);
$texto = str_replace('```', '', $texto);
$texto = trim($texto);

$resultado = json_decode($texto, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $inicio = strpos($texto, '{');
    if ($inicio !== false) {
        $prof = 0; $fim = $inicio;
        for ($i = $inicio, $len = strlen($texto); $i < $len; $i++) {
            if ($texto[$i] === '{') $prof++;
            elseif ($texto[$i] === '}') {
                $prof--;
                if ($prof === 0) { $fim = $i; break; }
            }
        }
        $resultado = json_decode(substr($texto, $inicio, $fim - $inicio + 1), true);
    }
}

// ══════════════════════════════════════════════
//  FUNÇÃO: normaliza e valida disciplinas
// ══════════════════════════════════════════════
function normalizarDisciplinas(array $raw): array {
    $DISCIPLINAS_VALIDAS = [
        'portugues', 'matematica', 'ciencias', 'historia',
        'geografia', 'ingles', 'edfisica',
        'artes', 'religiao', 'filosofia'
    ];

    $disciplinas = [];
    foreach ($raw as $chave => $nota) {
        $chave = strtolower(trim((string)$chave));
        if ($chave === 'layout') continue;
        if (!in_array($chave, $DISCIPLINAS_VALIDAS)) continue;

        $notaStr = str_replace(',', '.', (string)$nota);
        $v = floatval($notaStr);

        if ($v < 0 || $v > 10) continue;

        $disciplinas[$chave] = round($v, 2);
    }
    return $disciplinas;
}

// ══════════════════════════════════════════════
//  NORMALIZA E MONTA A RESPOSTA FINAL
// ══════════════════════════════════════════════
if (is_array($resultado)) {

    $layoutUsado = strtoupper(trim($resultado['layout'] ?? ''));

    $anos     = ['ano6', 'ano7', 'ano8', 'ano9'];
    $resposta = [];
    $resumo   = [];

    foreach ($anos as $chaveAno) {
        if (!isset($resultado[$chaveAno]) || !is_array($resultado[$chaveAno])) continue;

        $disciplinas = normalizarDisciplinas($resultado[$chaveAno]);

        if (count($disciplinas) === 0) continue;

        $notas  = array_values($disciplinas);
        $media  = round(array_sum($notas) / count($notas), 5);

        $resposta[$chaveAno] = [
            'disciplinas'     => $disciplinas,
            'notas'           => $notas,
            'quantidade'      => count($notas),
            'media_calculada' => $media,
        ];

        $resumo[] = (int) substr($chaveAno, 3);
    }

    if (count($resposta) > 0) {
        echo json_encode([
            'anos'           => $resposta,
            'anos_com_notas' => $resumo,
            'layout_ocr'     => $layoutUsado ?: 'N/A',
            'fonte'          => $fonteUsada
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Nenhum ano com notas encontrado
http_response_code(500);
echo json_encode([
    "erro"        => "Não foi possível identificar as notas. Verifique se a imagem está nítida e é um histórico escolar.",
    "debug_texto" => mb_substr($texto, 0, 800)
]);
