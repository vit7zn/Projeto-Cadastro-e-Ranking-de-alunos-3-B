<!DOCTYPE html>
<?php
session_start();
// ── Guard de sessão no servidor — sem depender de JS ──
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.HTML?erro=acesso_negado");
    exit();
}
$nomeUsuario = htmlspecialchars($_SESSION['nome'] ?? 'Usuário');
?>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Estudante - EEEP Manoel Mano</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- OVERLAY -->
    <div id="overlay-menu"></div>

    <!-- SIDEBAR LATERAL -->
    <nav id="sidebar-lateral">
        <a href="dashboard.php">📊 Painel</a>
        <a href="cadastro.php" class="active-link">📋 Cadastro</a>
        <a href="ranking.php">🏆 Ranking</a>
        <a href="logout.php">🚪 Sair</a>
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
        <nav class="navbar-cards">
            <a href="dashboard.php" class="nav-card">
              <span class="nav-card-icon">📊</span><span>Painel</span>
            </a>
            <a href="cadastro.php" class="nav-card active">
              <span class="nav-card-icon">📋</span><span>Cadastro</span>
            </a>
            <a href="ranking.php" class="nav-card">
              <span class="nav-card-icon">🏆</span><span>Ranking</span>
            </a>
        </nav>
        <div class="navbar-right">
            <a href="logout.php" class="btn-sair">Sair</a>
        </div>
    </header>

</script>

    <main class="registration-container">
        <h1>📋 Cadastro de Estudante</h1>

        <form class="student-form" action="salvar_cadastro.php" method="POST">
            <input type="hidden" name="media_final"       id="media_final"       value="0.00000">
            <input type="hidden" name="categoria_ranking" id="categoria_ranking" value="">

            <!-- DADOS PESSOAIS -->
            <div class="form-section">
                <div class="form-section-title">👤 Dados do Candidato</div>

                <div class="form-row-group">
                    <div class="field" style="flex:2;">
                        <label>Nome completo do aluno</label>
                        <input type="text" name="nome_aluno" placeholder="Nome completo" required>
                    </div>
                    <div class="field" style="flex:1;">
                        <label>Sexo</label>
                        <select name="sexo" id="sexo" required>
                            <option value="">Selecione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Feminino">Feminino</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                </div>

                <div class="form-row-group">
                    <div class="field">
                        <label>Bairro</label>
                        <input type="text" name="bairro" id="bairro" placeholder="Ex: Venâncios" required>
                    </div>
                    <div class="field">
                        <label>Deseja concorrer à Cota Local?</label>
                        <select name="optou_cota_local" id="optou_cota_local">
                            <option value="nao">Não</option>
                            <option value="sim">Sim (Bairro dos Venâncios)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row-group">
                    <div class="field">
                        <label>Curso pretendido</label>
                        <select name="curso" id="curso">
                            <option value="Enfermagem">Enfermagem</option>
                            <option value="Informatica">Informática</option>
                            <option value="Administracao">Administração</option>
                            <option value="Desenvolvimento de Sistemas">Desenvolvimento de Sistemas</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Procedência Escolar</label>
                        <select name="procedencia" id="procedencia">
                            <option value="publica">Escola Pública</option>
                            <option value="privada">Escola Privada</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="max-width: 320px;">
                    <label>Cota PCD (Pessoa com Deficiência)?</label>
                    <select name="pcd" id="pcd">
                        <option value="nao">Não</option>
                        <option value="sim">Sim</option>
                    </select>
                </div>
            </div>

            <!-- DADOS DO RESPONSÁVEL -->
            <div class="form-section">
                <div class="form-section-title">📞 Dados do Responsável</div>

                <div class="form-row-group">
                    <div class="field" style="flex:2;">
                        <label>Nome do responsável</label>
                        <input type="text" name="nome_responsavel" id="nome_responsavel" placeholder="Nome completo do responsável" required>
                    </div>
                </div>

                <div class="form-row-group">
                    <div class="field">
                        <label>E-mail do responsável</label>
                        <input type="email" name="email_responsavel" id="email_responsavel" placeholder="email@exemplo.com">
                    </div>
                    <div class="field">
                        <label>WhatsApp do responsável</label>
                        <input type="tel" name="telefone_responsavel" id="telefone_responsavel"
                               placeholder="(88) 9 9999-9999"
                               maxlength="16"
                               oninput="this.value = this.value.replace(/\D/g,'').replace(/^(\d{2})(\d)/,'($1) $2').replace(/(\d)(\d{4})$/,'$1-$2')">
                        <small style="color:var(--text-muted);font-size:.72rem;margin-top:4px;display:block;">
                            📲 Usado para envio automático do resultado via WhatsApp
                        </small>
                    </div>
                </div>
            </div>

            <!-- NOTAS DO BOLETIM -->
            <div class="form-section">
                <div class="grades-section-header">
                    <div class="form-section-title" style="margin-bottom:0; border:none; padding:0;">
                        📚 Notas do Boletim Escolar
                    </div>
                    <button type="button" id="btn-abrir-ocr" class="btn-ocr">
                        📷 Preencher via OCR (IA)
                    </button>
                </div>
                <p style="font-size:.8rem; color:var(--text-muted); margin-bottom:16px;">
                    Preencha as notas de cada disciplina por ano letivo (6º ao 9º ano). Use o botão OCR para preencher automaticamente com foto do boletim.
                </p>

                <div style="overflow-x:auto;">
                    <table class="notas-grid-table" id="tabela-notas">
                        <thead>
                            <tr>
                                <th>Disciplina</th>
                                <th>6º Ano</th>
                                <th>7º Ano</th>
                                <th>8º Ano</th>
                                <th>9º Ano</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Português</td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[portugues][6]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[portugues][7]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[portugues][8]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[portugues][9]" placeholder="—"></td>
                            </tr>
                            <tr>
                                <td>Matemática</td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[matematica][6]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[matematica][7]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[matematica][8]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[matematica][9]" placeholder="—"></td>
                            </tr>
                            <tr>
                                <td>Ciências</td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[ciencias][6]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[ciencias][7]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[ciencias][8]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[ciencias][9]" placeholder="—"></td>
                            </tr>
                            <tr>
                                <td>História</td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[historia][6]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[historia][7]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[historia][8]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[historia][9]" placeholder="—"></td>
                            </tr>
                            <tr>
                                <td>Geografia</td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[geografia][6]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[geografia][7]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[geografia][8]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[geografia][9]" placeholder="—"></td>
                            </tr>
                            <tr>
                                <td>Artes</td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[artes][6]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[artes][7]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[artes][8]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[artes][9]" placeholder="—"></td>
                            </tr>
                            <tr>
                                <td>Inglês</td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[ingles][6]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[ingles][7]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[ingles][8]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[ingles][9]" placeholder="—"></td>
                            </tr>
                            <tr>
                                <td>Ed. Física</td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[edfisica][6]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[edfisica][7]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[edfisica][8]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[edfisica][9]" placeholder="—"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-top:16px;">
                    <p style="font-size:.78rem; color:var(--text-muted); margin:0;">
                        💡 Campos em branco são ignorados no cálculo. A média usa todas as notas preenchidas.
                    </p>
                    <div class="media-display-box" id="media-display" style="display:none;">
                        Média: <span id="media-valor" style="font-family:monospace;">0.00000</span>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════ -->
            <!--  MODAL OCR                              -->
            <!-- ═══════════════════════════════════════ -->
            <div id="ocr-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px);z-index:9000;align-items:center;justify-content:center;">
                <div style="background:#fff;border-radius:18px;width:min(680px,96vw);box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;animation:ocrFadeIn .25s ease;max-height:92vh;display:flex;flex-direction:column;">

                    <!-- Cabeçalho fixo -->
                    <div style="background:linear-gradient(135deg,#0e5200,#1b8a00);padding:18px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                        <div>
                            <div style="font-size:1.05rem;font-weight:800;color:#fff;">📷 OCR de Notas com IA</div>
                            <div style="color:rgba(255,255,255,.65);font-size:.78rem;margin-top:3px;">Adicione um boletim por ano letivo — a IA lê e preenche automaticamente</div>
                        </div>
                        <button type="button" id="btn-fechar-ocr" style="background:rgba(255,255,255,.15);border:none;color:#fff;font-size:1.2rem;width:34px;height:34px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
                    </div>

                    <!-- Corpo com scroll -->
                    <div style="padding:22px 24px;overflow-y:auto;flex:1;">

                        <!-- ═══ SLOTS DOS 4 ANOS ═══ -->
                        <div id="ocr-slots" style="display:flex;flex-direction:column;gap:12px;"></div>

                        <!-- Status global / spinner -->
                        <div id="ocr-status" style="display:none;align-items:center;gap:12px;background:#f0f7f0;border-left:4px solid #1b8a00;border-radius:10px;padding:13px 16px;margin-top:14px;font-size:.88rem;color:#3a5c38;">
                            <div id="ocr-spinner" style="width:20px;height:20px;border:3px solid #d4e4d0;border-top-color:#1b8a00;border-radius:50%;animation:spin .8s linear infinite;flex-shrink:0;display:none;"></div>
                            <span id="ocr-status-msg">Analisando…</span>
                        </div>

                        <!-- ═══ RESULTADO CONSOLIDADO ═══ -->
                        <div id="ocr-resultado" style="display:none;margin-top:18px;">
                            <div style="font-weight:700;color:#1b8a00;font-size:.9rem;margin-bottom:4px;">
                                ✅ Notas prontas — confira e clique em <strong>"Preencher automaticamente"</strong>:
                            </div>
                            <div id="ocr-resumo" style="font-size:.8rem;color:#555;margin-bottom:14px;"></div>
                            <div id="ocr-lista-notas" style="display:flex;flex-direction:column;gap:6px;max-height:280px;overflow-y:auto;padding-right:4px;"></div>
                        </div>

                        <!-- Aviso de erro -->
                        <div id="ocr-aviso" style="display:none;background:#fff5f5;border:1px solid #ffcdd2;border-radius:10px;padding:12px 16px;margin-top:14px;font-size:.83rem;color:#c62828;"></div>

                        <!-- Botões -->
                        <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap;">
                            <button type="button" id="btn-ocr-analisar" disabled
                                style="flex:1;min-width:140px;background:linear-gradient(135deg,#1b8a00,#0e5200);color:#fff;border:none;padding:13px;border-radius:30px;font-weight:700;font-size:.92rem;cursor:pointer;opacity:.45;transition:opacity .2s;">
                                🔍 Analisar com IA
                            </button>
                            <button type="button" id="btn-ocr-usar" style="display:none;background:#e67e22;color:#fff;border:none;padding:13px 18px;border-radius:30px;font-weight:700;font-size:.9rem;cursor:pointer;">
                                ✅ Preencher automaticamente
                            </button>
                            <button type="button" id="btn-ocr-limpar" style="background:#f0f0f0;color:#555;border:none;padding:13px 18px;border-radius:30px;font-weight:600;font-size:.9rem;cursor:pointer;">
                                🗑️ Limpar tudo
                            </button>
                        </div>
                    </div><!-- /corpo -->
                </div>
            </div><!-- /ocr-overlay -->

            <button type="submit" class="btn-save">✅ SALVAR CADASTRO</button>
        </form>
    </main>

    <style>
    @keyframes ocrFadeIn { from { opacity:0; transform:translateY(-16px); } to { opacity:1; transform:none; } }
    @keyframes spin      { to { transform:rotate(360deg); } }
    #ocr-overlay { display:none; }
    #ocr-overlay.aberto { display:flex; }

    /* Cartão de cada nota na lista vertical */
    .nota-card {
        display:flex;
        align-items:center;
        gap:8px;
        background:#fff;
        border:1.5px solid #d4e8d0;
        border-radius:10px;
        padding:7px 10px;
        font-size:.9rem;
    }
    .nota-card .nota-num {
        font-weight:800;
        font-size:1rem;
        min-width:40px;
    }
    .nota-card .nota-pos {
        font-size:.72rem;
        color:#888;
        font-family:monospace;
        flex:1;
    }
    .nota-card.aplicada {
        border-color:#1b8a00;
        background:#f0fbf0;
        opacity:.6;
    }
    .nota-card.aplicada .nota-num { color:#1b8a00; }
    </style>

    <script>
    // ══════════════════════════════════════════
    //  CÁLCULO AUTOMÁTICO DA MÉDIA
    // ══════════════════════════════════════════
    function atualizarMedia() {
        const inputs = document.querySelectorAll('.input-nota');
        let soma = 0, qtd = 0;
        inputs.forEach(inp => {
            const v = parseFloat(inp.value);
            if (!isNaN(v) && inp.value.trim() !== '') { soma += v; qtd++; }
        });
        const media = qtd > 0 ? (soma / qtd) : 0;
        document.getElementById('media_final').value = media.toFixed(5);

        const disp = document.getElementById('media-display');
        const val  = document.getElementById('media-valor');
        if (qtd > 0) {
            val.textContent = media.toFixed(5) + ' (' + qtd + ' nota' + (qtd !== 1 ? 's' : '') + ')';
            disp.style.display = 'inline-flex';
        } else {
            disp.style.display = 'none';
        }
    }

    document.querySelectorAll('.input-nota').forEach(inp => {
        inp.addEventListener('input', atualizarMedia);
    });

    // ══════════════════════════════════════════
    //  LÓGICA DE CATEGORIA AO SUBMETER
    // ══════════════════════════════════════════
    document.querySelector('.student-form').addEventListener('submit', function() {
        const bairro      = (document.getElementById('bairro').value || '').toLowerCase();
        const optouCota   = document.getElementById('optou_cota_local').value;
        const procedencia = document.getElementById('procedencia').value;
        const pcd         = document.getElementById('pcd').value;

        let categoria = '';
        if (pcd === 'sim') {
            categoria = 'Cota PCD';
        } else if (procedencia === 'publica' && optouCota === 'sim' &&
                   (bairro.includes('venancio') || bairro.includes('venâncio'))) {
            categoria = 'Cota Local (Venâncios)';
        } else if (procedencia === 'privada') {
            categoria = 'Ampla Concorrência Privado';
        } else {
            categoria = 'Ampla Concorrência Pública';
        }
        document.getElementById('categoria_ranking').value = categoria;
        atualizarMedia();
    });

    // ══════════════════════════════════════════
    //  OCR MODAL — múltiplos boletins + exclusão
    // ══════════════════════════════════════════
    (function () {

        // Estado por slot (um slot = um ano letivo)
        const ANOS = ['6º Ano', '7º Ano', '8º Ano', '9º Ano'];
        const DISCIPLINAS = ['portugues','matematica','ciencias','historia','geografia','artes','ingles','edfisica'];
        const DISC_LABELS  = ['Português','Matemática','Ciências','História','Geografia','Artes','Inglês','Ed. Física'];

        // CAMPOS_ORDEM e LABELS para aplicar na tabela (vertical: ano a ano)
        const CAMPOS_ORDEM = [];
        const LABELS_CAMPOS = [];
        [6,7,8,9].forEach(ano => {
            DISCIPLINAS.forEach((d, di) => {
                CAMPOS_ORDEM.push(`nota[${d}][${ano}]`);
                LABELS_CAMPOS.push(`${DISC_LABELS[di]} ${ano}º`);
            });
        });

        // slots[i] = { base64, tipo, notas: [] | null }
        const slots = ANOS.map(() => ({ base64: null, tipo: 'image/jpeg', notas: null }));

        // notasFinais[i] = número consolidado para o CAMPOS_ORDEM[i], ou null se excluído
        let notasFinais = new Array(CAMPOS_ORDEM.length).fill(null);

        const overlay   = document.getElementById('ocr-overlay');
        const btnAbrir  = document.getElementById('btn-abrir-ocr');
        const btnFechar = document.getElementById('btn-fechar-ocr');
        const btnAnali  = document.getElementById('btn-ocr-analisar');
        const btnLimpar = document.getElementById('btn-ocr-limpar');
        const btnUsar   = document.getElementById('btn-ocr-usar');
        const statusBox = document.getElementById('ocr-status');
        const statusMsg = document.getElementById('ocr-status-msg');
        const aviso     = document.getElementById('ocr-aviso');
        const resultado = document.getElementById('ocr-resultado');

        btnAbrir.addEventListener('click',  () => { overlay.classList.add('aberto'); renderSlots(); });
        btnFechar.addEventListener('click', fechar);
        overlay.addEventListener('click',  e => { if (e.target === overlay) fechar(); });
        function fechar() { overlay.classList.remove('aberto'); }

        // ── Renderiza os 4 slots de upload ──
        function renderSlots() {
            const container = document.getElementById('ocr-slots');
            container.innerHTML = '';
            ANOS.forEach((label, i) => {
                const slot = slots[i];
                const temArquivo = !!slot.base64;
                const temNotas   = slot.notas !== null;

                const div = document.createElement('div');
                div.style.cssText = 'display:flex;align-items:center;gap:12px;background:#f9fdf9;border:1.5px solid #d4e8d0;border-radius:12px;padding:12px 14px;';

                // Badge do ano
                div.innerHTML = `
                    <div style="font-weight:800;font-size:.85rem;color:#0e5200;background:#d4f0c8;border-radius:8px;padding:5px 10px;min-width:56px;text-align:center;flex-shrink:0;">${label}</div>

                    <!-- zona de upload clicável -->
                    <div class="slot-zone" data-slot="${i}"
                         style="flex:1;position:relative;border:2px dashed ${temArquivo ? '#1b8a00' : '#b2d8b0'};border-radius:10px;padding:10px 14px;cursor:pointer;background:${temArquivo ? '#f0fbf0' : '#fff'};transition:.2s;">
                        <input type="file" class="slot-file" data-slot="${i}" accept="image/*,application/pdf"
                               style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
                        <div style="font-size:.85rem;font-weight:700;color:${temArquivo ? '#1b8a00' : '#6b8268'};">
                            ${temArquivo ? '✅ Boletim carregado' : '📎 Clique ou arraste o boletim'}
                        </div>
                        ${temNotas
                            ? `<div style="font-size:.72rem;color:#1b8a00;margin-top:2px;">🔢 ${slot.notas.length} nota${slot.notas.length!==1?'s':''} lida${slot.notas.length!==1?'s':''}</div>`
                            : `<div style="font-size:.72rem;color:#6b8268;margin-top:2px;">JPG, PNG, WEBP ou PDF</div>`
                        }
                    </div>

                    <!-- botão remover slot -->
                    ${temArquivo ? `<button type="button" class="slot-remover" data-slot="${i}"
                        style="background:#fff0f0;border:1.5px solid #ffcdd2;color:#c62828;border-radius:8px;padding:7px 10px;cursor:pointer;font-size:.8rem;font-weight:700;flex-shrink:0;">✕</button>` : ''}
                `;
                container.appendChild(div);
            });

            // Eventos dos inputs de arquivo
            container.querySelectorAll('.slot-file').forEach(input => {
                input.addEventListener('change', () => {
                    if (input.files[0]) carregarArquivoSlot(parseInt(input.dataset.slot), input.files[0]);
                });
            });

            // Eventos dos botões remover
            container.querySelectorAll('.slot-remover').forEach(btn => {
                btn.addEventListener('click', () => {
                    const i = parseInt(btn.dataset.slot);
                    slots[i] = { base64: null, tipo: 'image/jpeg', notas: null };
                    renderSlots();
                    atualizarBotaoAnalisar();
                    esconderResultado();
                });
            });

            // Hover nos slots
            container.querySelectorAll('.slot-zone').forEach(z => {
                z.addEventListener('mouseover', () => { z.style.borderColor='#1b8a00'; z.style.background='#f0fbf0'; });
                z.addEventListener('mouseout',  () => {
                    const i = parseInt(z.dataset.slot);
                    z.style.borderColor = slots[i].base64 ? '#1b8a00' : '#b2d8b0';
                    z.style.background  = slots[i].base64 ? '#f0fbf0' : '#fff';
                });
            });
        }

        function carregarArquivoSlot(i, file) {
            const tipo = file.type || 'image/jpeg';
            const reader = new FileReader();
            reader.onload = e => {
                slots[i].base64 = e.target.result.split(',')[1];
                slots[i].tipo   = tipo;
                slots[i].notas  = null;
                renderSlots();
                atualizarBotaoAnalisar();
                esconderResultado();
            };
            reader.readAsDataURL(file);
        }

        function atualizarBotaoAnalisar() {
            const temAlgum = slots.some(s => s.base64 !== null);
            btnAnali.disabled = !temAlgum;
            btnAnali.style.opacity = temAlgum ? '1' : '.45';
        }

        function esconderResultado() {
            resultado.style.display = 'none';
            btnUsar.style.display   = 'none';
            aviso.style.display     = 'none';
        }

        // ── Analisar: processa cada slot que tem arquivo ──
        btnAnali.addEventListener('click', async () => {
            const slotsParaAnalisar = slots.map((s, i) => ({ ...s, idx: i })).filter(s => s.base64 !== null);
            if (slotsParaAnalisar.length === 0) return;

            btnAnali.disabled = true; btnAnali.style.opacity = '.45';
            btnUsar.style.display = 'none';
            resultado.style.display = 'none';
            aviso.style.display = 'none';

            statusBox.style.cssText = 'display:flex;align-items:center;gap:12px;background:#f0f7f0;border-left:4px solid #1b8a00;border-radius:10px;padding:13px 16px;margin-top:14px;font-size:.88rem;color:#3a5c38;';
            document.getElementById('ocr-spinner').style.display = 'block';

            let erros = [];

            for (const s of slotsParaAnalisar) {
                statusMsg.textContent = `Lendo boletim do ${ANOS[s.idx]} com IA…`;
                try {
                    const resp = await fetch('ocr_proxy.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ imagem: s.base64, tipo: s.tipo })
                    });
                    const json = await resp.json();
                    if (json.erro) {
                        erros.push(`${ANOS[s.idx]}: ${json.erro}`);
                        slots[s.idx].notas = [];
                    } else if (!json.notas || json.notas.length === 0) {
                        erros.push(`${ANOS[s.idx]}: nenhuma nota encontrada.`);
                        slots[s.idx].notas = [];
                    } else {
                        slots[s.idx].notas = json.notas;
                    }
                } catch (err) {
                    erros.push(`${ANOS[s.idx]}: erro de conexão.`);
                    slots[s.idx].notas = [];
                }
            }

            document.getElementById('ocr-spinner').style.display = 'none';
            statusBox.style.display = 'none';

            // Consolida as notas: cada slot[i] preenche as 8 disciplinas do ano i+6
            notasFinais = new Array(CAMPOS_ORDEM.length).fill(null);
            slots.forEach((s, anoIdx) => {
                if (!s.notas || s.notas.length === 0) return;
                s.notas.forEach((val, discIdx) => {
                    if (discIdx >= DISCIPLINAS.length) return;
                    const campoIdx = anoIdx * DISCIPLINAS.length + discIdx;
                    notasFinais[campoIdx] = val;
                });
            });

            renderSlots(); // atualiza badges
            renderizarResultado();

            btnAnali.disabled = false; btnAnali.style.opacity = '1';

            if (erros.length > 0) {
                aviso.textContent = '⚠️ ' + erros.join(' | ');
                aviso.style.display = 'block';
            }
        });

        // ── Renderiza a lista consolidada com botão ✕ por nota ──
        function renderizarResultado() {
            const lista = document.getElementById('ocr-lista-notas');
            lista.innerHTML = '';

            const ativas = notasFinais.filter(n => n !== null);
            if (ativas.length === 0) {
                resultado.style.display = 'none';
                btnUsar.style.display = 'none';
                return;
            }

            notasFinais.forEach((val, i) => {
                if (val === null) return; // excluída ou vazia
                const label = LABELS_CAMPOS[i];
                const cor   = val >= 7 ? '#1b5e20' : val >= 5 ? '#e65100' : '#b71c1c';

                const card = document.createElement('div');
                card.className = 'nota-card';
                card.id = `nota-card-${i}`;
                card.innerHTML = `
                    <span class="nota-num" style="color:${cor};">${val.toFixed(2)}</span>
                    <span class="nota-pos" style="flex:1;padding:0 10px;">→ ${label}</span>
                    <button type="button" class="nota-excluir" data-idx="${i}"
                        title="Excluir esta nota"
                        style="background:#fff0f0;border:1.5px solid #ffcdd2;color:#c62828;border-radius:6px;padding:3px 8px;cursor:pointer;font-size:.75rem;font-weight:700;flex-shrink:0;">✕</button>`;
                lista.appendChild(card);
            });

            // Botões de exclusão individual
            lista.querySelectorAll('.nota-excluir').forEach(btn => {
                btn.addEventListener('click', () => {
                    const idx = parseInt(btn.dataset.idx);
                    notasFinais[idx] = null;
                    renderizarResultado();
                });
            });

            const totalAtivas = notasFinais.filter(n => n !== null).length;
            const soma = notasFinais.filter(n => n !== null).reduce((a, b) => a + b, 0);
            const media = totalAtivas > 0 ? soma / totalAtivas : 0;

            document.getElementById('ocr-resumo').textContent =
                `${totalAtivas} nota${totalAtivas !== 1 ? 's' : ''} · Média: ${media.toFixed(5)}`;

            resultado.style.display = 'block';
            btnUsar.style.display   = 'inline-block';
        }

        // ── Preencher automaticamente ──
        btnUsar.addEventListener('click', () => {
            let preenchidos = 0;
            notasFinais.forEach((val, i) => {
                if (val === null) return;
                const input = document.querySelector(`input[name="${CAMPOS_ORDEM[i]}"]`);
                if (input) { input.value = val.toFixed(2); preenchidos++; }
            });
            atualizarMedia();
            setTimeout(() => {
                fechar();
                if (preenchidos === 0) alert('Nenhuma nota pôde ser aplicada.');
            }, 300);
        });

        // ── Limpar tudo ──
        btnLimpar.addEventListener('click', () => {
            slots.forEach((s, i) => { slots[i] = { base64: null, tipo: 'image/jpeg', notas: null }; });
            notasFinais = new Array(CAMPOS_ORDEM.length).fill(null);
            renderSlots();
            atualizarBotaoAnalisar();
            esconderResultado();
            statusBox.style.display = 'none';
        });

        // ── Mensagens de erro quota ──
        function mostrarErroQuota(msg, segundos) {
            let restante = segundos;
            statusBox.style.cssText = 'display:flex;flex-direction:column;gap:8px;background:#fff8e1;border-left:4px solid #f59e0b;border-radius:10px;padding:14px 16px;margin-top:14px;font-size:.88rem;color:#92400e;';
            statusMsg.innerHTML = `⏳ ${msg} Tente novamente em <strong id="quota-count">${restante}s</strong>`;
            btnAnali.disabled = true; btnAnali.style.opacity = '.45';
            const iv = setInterval(() => {
                restante--;
                const el = document.getElementById('quota-count');
                if (el) el.textContent = restante + 's';
                if (restante <= 0) {
                    clearInterval(iv);
                    statusMsg.innerHTML = '✅ Pronto! Clique em <strong>Analisar</strong> para tentar novamente.';
                    btnAnali.disabled = false; btnAnali.style.opacity = '1';
                }
            }, 1000);
        }

    })();
    </script>

    <script>
    // Hamburguer sidebar
    (function(){
        const btn     = document.getElementById('btn-hamburguer');
        const sidebar = document.getElementById('sidebar-lateral');
        const overlay = document.getElementById('overlay-menu');
        if (!btn) return;
        function abrir()  { btn.classList.add('aberto');    sidebar.classList.add('aberta');    overlay.classList.add('aberto'); }
        function fechar() { btn.classList.remove('aberto'); sidebar.classList.remove('aberta'); overlay.classList.remove('aberto'); }
        btn.addEventListener('click', () => sidebar.classList.contains('aberta') ? fechar() : abrir());
        overlay.addEventListener('click', fechar);
    })();
    </script>

</body>
</html>