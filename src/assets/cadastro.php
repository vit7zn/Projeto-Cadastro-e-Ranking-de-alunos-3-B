<?php
session_start();
// ── Guard de sessão no servidor — sem depender de JS ──
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.HTML?erro=acesso_negado");
    exit();
}
$nomeUsuario = htmlspecialchars($_SESSION['nome'] ?? 'Usuário');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Estudante — EEEP Manoel Mano</title>
    <link rel="stylesheet" href="style.css">
    <!-- PDF.js — converte PDF em imagem no navegador, sem precisar de Ghostscript no servidor -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
</head>
<body>

    <!-- OVERLAY -->
    <div id="overlay-menu"></div>

    <!-- SIDEBAR LATERAL -->
    <nav id="sidebar-lateral">
        <a href="dashboard.php">📊 Painel</a>
        <a href="cadastro.php" class="active-link">📋 Cadastro</a>
        <a href="ranking.php">🏆 Ranking</a>
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
            <a href="index.HTML" class="btn-sair">Sair</a>
        </div>
    </header>

    <script>
    fetch('get_session.php')
        .then(r => r.json())
        .then(data => {
            const perfil = document.getElementById('nome-perfil-nav');
            if (data.nome && perfil) {
                perfil.textContent = data.nome;
            }
        })
        .catch(() => {});
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

                <!-- PCD: seletor inicial -->
                <div class="form-row-group" style="align-items:flex-start;">
                    <div class="field" style="max-width:320px;">
                        <label>Cota PCD (Pessoa com Deficiência)?</label>
                        <select name="pcd" id="pcd">
                            <option value="nao">Não</option>
                            <option value="sim">Sim</option>
                        </select>
                    </div>
                </div>

                <!-- Bloco PCD expandido (oculto por padrão) -->
                <div id="bloco-pcd" style="display:none; margin-top:18px; padding:18px 20px; background:#f0f7ff; border:1.5px solid #90caf9; border-radius:12px;">

                    <div style="font-weight:700; color:#1565c0; font-size:.95rem; margin-bottom:14px;">
                        ♿ Informações sobre a Deficiência
                    </div>

                    <!-- Linha 1: Categoria + Tipo específico -->
                    <div class="form-row-group" style="align-items:flex-start; gap:16px;">
                        <div class="field">
                            <label>Categoria da Deficiência <span style="color:#c0392b;">*</span></label>
                            <select name="categoria_deficiencia" id="categoria_deficiencia" required>
                                <option value="">Selecione a categoria...</option>
                                <optgroup label="♿ Deficiência Física / Motora">
                                    <option value="Fisica_Motora_Membros">Comprometimento de membros superiores ou inferiores</option>
                                    <option value="Fisica_Paraplegia">Paraplegia / Tetraplegia</option>
                                    <option value="Fisica_Hemiplegia">Hemiplegia</option>
                                    <option value="Fisica_Amputacao">Amputação</option>
                                    <option value="Fisica_Outra">Outra deficiência física</option>
                                </optgroup>
                                <optgroup label="👁️ Deficiência Visual">
                                    <option value="Visual_Cegueira">Cegueira total</option>
                                    <option value="Visual_Baixa">Baixa visão</option>
                                    <option value="Visual_Monocular">Visão monocular</option>
                                </optgroup>
                                <optgroup label="👂 Deficiência Auditiva">
                                    <option value="Auditiva_Surdez">Surdez (perda total)</option>
                                    <option value="Auditiva_Parcial">Perda auditiva parcial</option>
                                    <option value="Auditiva_Surdocegueira">Surdocegueira</option>
                                </optgroup>
                                <optgroup label="🧠 Deficiência Intelectual / Cognitiva">
                                    <option value="Intelectual_DI">Deficiência intelectual (DI)</option>
                                    <option value="Intelectual_Down">Síndrome de Down</option>
                                    <option value="Intelectual_Outra">Outra deficiência intelectual</option>
                                </optgroup>
                                <optgroup label="🔄 Transtornos do Neurodesenvolvimento">
                                    <option value="Neuro_TEA">Transtorno do Espectro Autista (TEA)</option>
                                    <option value="Neuro_TDAH">TDAH (com laudo e CID)</option>
                                    <option value="Neuro_Dislexia">Dislexia severa (com laudo)</option>
                                    <option value="Neuro_Outro">Outro transtorno do neurodesenvolvimento</option>
                                </optgroup>
                                <optgroup label="🔀 Deficiência Múltipla">
                                    <option value="Multipla">Múltipla (duas ou mais deficiências)</option>
                                </optgroup>
                                <optgroup label="📋 Outras">
                                    <option value="Outra">Outra — descrever abaixo</option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Campo "Outra" condicional -->
                        <div class="field" id="campo-outra-def" style="display:none;">
                            <label>Descreva a deficiência <span style="color:#c0392b;">*</span></label>
                            <input type="text" name="outra_deficiencia" id="outra_deficiencia"
                                   placeholder="Ex: Displasia óssea, paralisia cerebral leve..."
                                   maxlength="120">
                            <small style="color:#666;font-size:.71rem;">Máx. 120 caracteres</small>
                        </div>
                    </div>

                    <!-- Linha 2: CID-10 + grau -->
                    <div class="form-row-group" style="align-items:flex-start; gap:16px; margin-top:14px;">
                        <div class="field" style="max-width:200px;">
                            <label>Código CID-10 <span style="color:#c0392b;">*</span></label>
                            <input type="text" name="cid10" id="cid10"
                                   placeholder="Ex: F84.0, G80.0"
                                   maxlength="10"
                                   pattern="[A-Za-z]\d{2}(\.\d{0,2})?"
                                   style="text-transform:uppercase;">
                            <small style="color:#666;font-size:.71rem;">Formato: letra + 2 dígitos (ex: H54.0)</small>
                            <div id="erro-cid" style="display:none;color:#c0392b;font-size:.72rem;margin-top:3px;">⚠️ Formato CID inválido</div>
                        </div>
                        <div class="field" style="max-width:240px;">
                            <label>Grau / Nível da Deficiência <span style="color:#c0392b;">*</span></label>
                            <select name="grau_deficiencia" id="grau_deficiencia">
                                <option value="">Selecione...</option>
                                <option value="Leve">Leve</option>
                                <option value="Moderado">Moderado</option>
                                <option value="Grave">Grave</option>
                                <option value="Profundo">Profundo / Total</option>
                            </select>
                        </div>
                        <div class="field" style="max-width:220px;">
                            <label>A deficiência é congênita?</label>
                            <select name="congenita" id="congenita">
                                <option value="">Selecione...</option>
                                <option value="sim">Sim (desde o nascimento)</option>
                                <option value="nao">Não (adquirida)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Linha 3: Laudo médico -->
                    <div style="margin-top:16px; padding:14px 16px; background:#fff8e1; border:1px solid #ffe082; border-radius:8px;">
                        <div style="font-weight:600; font-size:.88rem; color:#e65100; margin-bottom:10px;">📄 Laudo Médico</div>
                        <div class="form-row-group" style="gap:16px; align-items:flex-start;">
                            <div class="field" style="max-width:260px;">
                                <label>Possui laudo médico? <span style="color:#c0392b;">*</span></label>
                                <select name="possui_laudo" id="possui_laudo">
                                    <option value="">Selecione...</option>
                                    <option value="sim_original">Sim — apresentarei o original</option>
                                    <option value="sim_copia">Sim — apresentarei cópia autenticada</option>
                                    <option value="em_processo">Em processo de obtenção</option>
                                    <option value="nao">Não possuo</option>
                                </select>
                            </div>
                            <div class="field" id="campo-validade-laudo" style="display:none; max-width:200px;">
                                <label>Validade do laudo</label>
                                <input type="date" name="validade_laudo" id="validade_laudo">
                                <div id="erro-laudo-vencido" style="display:none;color:#c0392b;font-size:.72rem;margin-top:3px;">⚠️ Laudo vencido — verifique com a escola</div>
                            </div>
                            <div class="field" id="campo-crm" style="display:none; max-width:220px;">
                                <label>CRM / Registro do médico</label>
                                <input type="text" name="crm_medico" id="crm_medico"
                                       placeholder="Ex: CRM-CE 12345"
                                       maxlength="20">
                            </div>
                        </div>
                        <div id="aviso-sem-laudo" style="display:none; margin-top:10px; padding:8px 12px; background:#ffebee; border-radius:6px; font-size:.78rem; color:#b71c1c;">
                            ⚠️ <strong>Atenção:</strong> a ausência de laudo médico pode inviabilizar a concessão da cota PCD. Entre em contato com a escola para mais informações.
                        </div>
                    </div>

                    <!-- Linha 4: Necessidades especiais / adaptações -->
                    <div style="margin-top:16px; padding:14px 16px; background:#f3e5f5; border:1px solid #ce93d8; border-radius:8px;">
                        <div style="font-weight:600; font-size:.88rem; color:#6a1b9a; margin-bottom:10px;">🛠️ Necessidades Especiais e Adaptações</div>
                        <div class="form-row-group" style="gap:16px; align-items:flex-start; flex-wrap:wrap;">
                            <div class="field">
                                <label>Recursos de acessibilidade necessários</label>
                                <div style="display:flex; flex-direction:column; gap:6px; margin-top:6px;">
                                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="interprete_libras"> Intérprete de Libras</label>
                                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="material_braille"> Material em Braille</label>
                                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="ampliacao_fonte"> Ampliação de fonte / Prova ampliada</label>
                                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="tempo_adicional"> Tempo adicional para provas</label>
                                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="sala_especial"> Sala de prova especial / acessível</label>
                                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="ledor"> Ledor / Transcritor</label>
                                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="computador"> Uso de computador / tecnologia assistiva</label>
                                    <label style="font-weight:normal;display:flex;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="outra_adaptacao"> Outra adaptação</label>
                                </div>
                            </div>
                            <div class="field" style="flex:2; min-width:200px;">
                                <label>Observações adicionais sobre as necessidades</label>
                                <textarea name="obs_necessidades" id="obs_necessidades"
                                          rows="4"
                                          maxlength="500"
                                          placeholder="Descreva aqui qualquer necessidade específica não listada acima, medicações de uso contínuo que a escola deve saber, restrições de atividade, etc."
                                          style="width:100%;resize:vertical;padding:8px;border:1px solid #ccc;border-radius:6px;font-family:inherit;font-size:.85rem;"></textarea>
                                <div style="text-align:right;font-size:.7rem;color:#888;" id="contador-obs">0/500</div>
                            </div>
                        </div>
                        <div style="margin-top:12px;">
                            <label style="font-weight:600; font-size:.85rem;">Usa equipamento de apoio?</label>
                            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:6px;">
                                <label style="font-weight:normal;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="equipamento[]" value="cadeira_rodas"> Cadeira de rodas</label>
                                <label style="font-weight:normal;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="equipamento[]" value="andador"> Andador / muleta</label>
                                <label style="font-weight:normal;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="equipamento[]" value="aparelho_auditivo"> Aparelho auditivo / implante coclear</label>
                                <label style="font-weight:normal;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="equipamento[]" value="protese"> Prótese</label>
                                <label style="font-weight:normal;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="equipamento[]" value="nenhum"> Nenhum</label>
                            </div>
                        </div>
                    </div>

                    <!-- Linha 5: Declaração -->
                    <div style="margin-top:14px; padding:10px 14px; background:#e8f5e9; border:1px solid #a5d6a7; border-radius:8px; font-size:.8rem; color:#2e7d32;">
                        <label style="display:flex;align-items:flex-start;gap:10px;font-weight:normal;cursor:pointer;">
                            <input type="checkbox" name="declara_pcd" id="declara_pcd" style="margin-top:2px;flex-shrink:0;">
                            <span>Declaro, sob as penas da lei, que as informações prestadas sobre a condição de Pessoa com Deficiência são verdadeiras e que o laudo médico estará disponível para conferência quando solicitado pela instituição. Estou ciente de que a prestação de informações falsas implicará na desclassificação do processo seletivo.</span>
                        </label>
                        <div id="erro-declara" style="display:none;color:#c0392b;font-size:.72rem;margin-top:5px;">⚠️ É obrigatório aceitar a declaração para concorrer à cota PCD.</div>
                    </div>

                </div><!-- /bloco-pcd -->
            </div>

            <script>
            (function () {
                /* ── Elementos ── */
                const selectPcd        = document.getElementById('pcd');
                const blocoPcd         = document.getElementById('bloco-pcd');
                const selCategoria     = document.getElementById('categoria_deficiencia');
                const campoOutra       = document.getElementById('campo-outra-def');
                const inputOutra       = document.getElementById('outra_deficiencia');
                const inputCid         = document.getElementById('cid10');
                const erroCid          = document.getElementById('erro-cid');
                const selGrau          = document.getElementById('grau_deficiencia');
                const selLaudo         = document.getElementById('possui_laudo');
                const campoValidade    = document.getElementById('campo-validade-laudo');
                const campoCrm         = document.getElementById('campo-crm');
                const inputValidade    = document.getElementById('validade_laudo');
                const erroLaudoVencido = document.getElementById('erro-laudo-vencido');
                const avisoSemLaudo    = document.getElementById('aviso-sem-laudo');
                const obsArea          = document.getElementById('obs_necessidades');
                const contadorObs      = document.getElementById('contador-obs');
                const checkDeclara     = document.getElementById('declara_pcd');
                const erroDeclara      = document.getElementById('erro-declara');

                /* ── PCD toggle ── */
                function togglePcd() {
                    const ativo = selectPcd.value === 'sim';
                    blocoPcd.style.display = ativo ? '' : 'none';
                    ['categoria_deficiencia','cid10','grau_deficiencia','possui_laudo'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.required = ativo;
                    });
                    if (!ativo) resetPcdFields();
                }

                function resetPcdFields() {
                    ['categoria_deficiencia','grau_deficiencia','congenita','possui_laudo'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.value = '';
                    });
                    ['outra_deficiencia','cid10','validade_laudo','crm_medico','obs_necessidades'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.value = '';
                    });
                    document.querySelectorAll('input[name="acessibilidade[]"], input[name="equipamento[]"]').forEach(c => c.checked = false);
                    if (checkDeclara) checkDeclara.checked = false;
                    campoOutra.style.display    = 'none';
                    campoValidade.style.display = 'none';
                    campoCrm.style.display      = 'none';
                    avisoSemLaudo.style.display = 'none';
                }

                /* ── Categoria → campo "Outra" ── */
                function toggleOutra() {
                    const isOutra = selCategoria.value === 'Outra';
                    campoOutra.style.display = isOutra ? '' : 'none';
                    inputOutra.required      = isOutra;
                    if (!isOutra) inputOutra.value = '';
                }

                /* ── Validação CID-10 ── */
                function validarCid() {
                    const v = inputCid.value.trim().toUpperCase();
                    inputCid.value = v;
                    const ok = v === '' || /^[A-Z]\d{2}(\.\d{0,2})?$/.test(v);
                    erroCid.style.display = ok ? 'none' : 'block';
                    return ok;
                }

                /* ── Laudo: validade + CRM ── */
                function toggleLaudo() {
                    const val = selLaudo.value;
                    const temLaudo = val === 'sim_original' || val === 'sim_copia';
                    campoValidade.style.display = temLaudo ? '' : 'none';
                    campoCrm.style.display      = temLaudo ? '' : 'none';
                    avisoSemLaudo.style.display = (val === 'nao') ? 'block' : 'none';
                    if (!temLaudo) {
                        inputValidade.value = '';
                        document.getElementById('crm_medico').value = '';
                        erroLaudoVencido.style.display = 'none';
                    }
                }

                /* ── Validade do laudo ── */
                function verificarValidade() {
                    if (!inputValidade.value) { erroLaudoVencido.style.display = 'none'; return; }
                    const venc = new Date(inputValidade.value);
                    const hoje = new Date();
                    hoje.setHours(0,0,0,0);
                    erroLaudoVencido.style.display = venc < hoje ? 'block' : 'none';
                }

                /* ── Contador textarea ── */
                function atualizarContador() {
                    contadorObs.textContent = obsArea.value.length + '/500';
                    contadorObs.style.color = obsArea.value.length > 450 ? '#e65100' : '#888';
                }

                /* ── Validação na submissão ── */
                const form = document.querySelector('.student-form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (selectPcd.value !== 'sim') return;

                        let ok = true;

                        if (!validarCid() && inputCid.value.trim() !== '') ok = false;

                        if (!checkDeclara.checked) {
                            erroDeclara.style.display = 'block';
                            checkDeclara.scrollIntoView({behavior:'smooth', block:'center'});
                            ok = false;
                        } else {
                            erroDeclara.style.display = 'none';
                        }

                        if (!ok) e.preventDefault();
                    });
                }

                /* ── Eventos ── */
                selectPcd.addEventListener('change', togglePcd);
                selCategoria.addEventListener('change', toggleOutra);
                inputCid.addEventListener('input', validarCid);
                selLaudo.addEventListener('change', toggleLaudo);
                inputValidade.addEventListener('change', verificarValidade);
                obsArea.addEventListener('input', atualizarContador);
                checkDeclara.addEventListener('change', () => {
                    erroDeclara.style.display = checkDeclara.checked ? 'none' : 'block';
                });

                /* ── Estado inicial ── */
                togglePcd();
            })();
            </script>

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
                            <tr>
                                <td>Ens. Religioso</td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[religiao][6]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[religiao][7]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[religiao][8]" placeholder="—"></td>
                                <td><input type="number" step="0.01" min="0" max="10" class="input-nota" name="nota[religiao][9]" placeholder="—"></td>
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
                            <div style="color:rgba(255,255,255,.65);font-size:.78rem;margin-top:3px;">Envie uma foto do boletim — a IA detecta o ano e preenche automaticamente</div>
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
    //  OCR MODAL — slot único (a IA detecta o ano)
    // ══════════════════════════════════════════
    (function () {

        const DISCIPLINAS        = ['portugues','matematica','ciencias','historia','geografia','artes','ingles','espanhol','edfisica','religiao','filosofia'];
        const DISC_LABELS        = ['Português','Matemática','Ciências','História','Geografia','Artes','Inglês','Espanhol','Ed. Física','Ens. Religioso','Filosofia'];
        const DISCIPLINAS_TABELA = ['portugues','matematica','ciencias','historia','geografia','artes','ingles','edfisica','religiao'];

        const CAMPOS_ORDEM  = [];
        const LABELS_CAMPOS = [];
        [6,7,8,9].forEach(ano => {
            DISCIPLINAS_TABELA.forEach(d => {
                const di = DISCIPLINAS.indexOf(d);
                CAMPOS_ORDEM.push(`nota[${d}][${ano}]`);
                LABELS_CAMPOS.push(`${DISC_LABELS[di]} ${ano}º`);
            });
        });

        let slot             = { base64: null, tipo: 'image/jpeg' };
        let discReconhecidas = {};
        let anoDetectado     = null;
        let notasFinais      = new Array(CAMPOS_ORDEM.length).fill(null);

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

        btnAbrir.addEventListener('click',  () => { overlay.classList.add('aberto'); renderSlot(); });
        btnFechar.addEventListener('click', fechar);
        overlay.addEventListener('click',  e => { if (e.target === overlay) fechar(); });
        function fechar() { overlay.classList.remove('aberto'); }

        // ── Renderiza o único slot de upload ──
        function renderSlot() {
            const container  = document.getElementById('ocr-slots');
            const temArquivo = !!slot.base64;

            container.innerHTML = `
                <div style="background:#f9fdf9;border:1.5px solid #d4e8d0;border-radius:12px;padding:14px;">
                    <div style="position:relative;border:2px dashed ${temArquivo ? '#1b8a00' : '#b2d8b0'};
                                border-radius:10px;padding:20px;cursor:pointer;
                                background:${temArquivo ? '#f0fbf0' : '#fff'};
                                text-align:center;transition:.2s;">
                        <input type="file" id="slot-file-input" accept="image/*,application/pdf"
                               style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
                        <div style="font-size:2.2rem;margin-bottom:6px;">${temArquivo ? '✅' : '📎'}</div>
                        <div style="font-size:.9rem;font-weight:700;color:${temArquivo ? '#1b8a00' : '#6b8268'};">
                            ${temArquivo ? 'Boletim carregado — clique para trocar' : 'Clique ou arraste o boletim aqui'}
                        </div>
                        <div style="font-size:.75rem;color:#888;margin-top:5px;">
                            ${temArquivo && anoDetectado
                                ? `🏫 Ano mais recente: <strong style="color:#0e5200">${anoDetectado}º ano</strong>`
                                : 'JPG, PNG, WEBP ou PDF — histórico completo ou boletim de um único ano'}
                        </div>
                    </div>
                    ${temArquivo ? `
                    <div style="display:flex;justify-content:flex-end;margin-top:10px;">
                        <button type="button" id="slot-remover"
                            style="background:#fff0f0;border:1.5px solid #ffcdd2;color:#c62828;
                                   border-radius:8px;padding:6px 14px;cursor:pointer;font-size:.8rem;font-weight:700;">
                            ✕ Remover arquivo
                        </button>
                    </div>` : ''}
                </div>
            `;

            document.getElementById('slot-file-input').addEventListener('change', e => {
                if (e.target.files[0]) carregarArquivo(e.target.files[0]);
            });

            const btnRem = document.getElementById('slot-remover');
            if (btnRem) {
                btnRem.addEventListener('click', () => {
                    slot = { base64: null, tipo: 'image/jpeg' };
                    discReconhecidas = {}; anoDetectado = null;
                    notasFinais = new Array(CAMPOS_ORDEM.length).fill(null);
                    renderSlot(); atualizarBotaoAnalisar(); esconderResultado();
                });
            }

            atualizarBotaoAnalisar();
        }

        // ── Carrega arquivo — converte PDF em JPEG via PDF.js (todas as páginas juntas) ──
        async function carregarArquivo(file) {
            const tipo = file.type || 'image/jpeg';
            esconderResultado(); discReconhecidas = {}; anoDetectado = null;

            if (tipo === 'application/pdf') {
                try {
                    const arrayBuffer = await file.arrayBuffer();
                    if (!pdfjsLib.GlobalWorkerOptions.workerSrc) {
                        pdfjsLib.GlobalWorkerOptions.workerSrc =
                            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                    }
                    const pdfDoc = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    const scale  = 2.0;

                    // Renderiza todas as páginas em canvases separados
                    const canvases = [];
                    for (let p = 1; p <= pdfDoc.numPages; p++) {
                        const page     = await pdfDoc.getPage(p);
                        const viewport = page.getViewport({ scale });
                        const cv       = document.createElement('canvas');
                        cv.width       = viewport.width;
                        cv.height      = viewport.height;
                        await page.render({ canvasContext: cv.getContext('2d'), viewport }).promise;
                        canvases.push(cv);
                    }

                    // Junta todas as páginas verticalmente num único canvas
                    const totalWidth  = Math.max(...canvases.map(c => c.width));
                    const totalHeight = canvases.reduce((s, c) => s + c.height, 0);
                    const merged      = document.createElement('canvas');
                    merged.width      = totalWidth;
                    merged.height     = totalHeight;
                    const ctx         = merged.getContext('2d');
                    ctx.fillStyle     = '#ffffff';
                    ctx.fillRect(0, 0, totalWidth, totalHeight);
                    let offsetY = 0;
                    for (const cv of canvases) {
                        ctx.drawImage(cv, 0, offsetY);
                        offsetY += cv.height;
                    }

                    slot.base64 = merged.toDataURL('image/jpeg', 0.92).split(',')[1];
                    slot.tipo   = 'image/jpeg';
                    renderSlot();
                } catch (err) {
                    aviso.textContent = '⚠️ Não foi possível ler o PDF. Tente converter para JPG/PNG.';
                    aviso.style.display = 'block';
                }
                return;
            }

            const reader = new FileReader();
            reader.onload = e => {
                slot.base64 = e.target.result.split(',')[1];
                slot.tipo   = tipo;
                renderSlot();
            };
            reader.readAsDataURL(file);
        }

        function atualizarBotaoAnalisar() {
            btnAnali.disabled      = !slot.base64;
            btnAnali.style.opacity = slot.base64 ? '1' : '.45';
        }

        function esconderResultado() {
            resultado.style.display = 'none';
            btnUsar.style.display   = 'none';
            aviso.style.display     = 'none';
        }

        // ── Analisar: envia UMA imagem, IA retorna o ano mais recente ──
        btnAnali.addEventListener('click', async () => {
            if (!slot.base64) return;

            btnAnali.disabled = true; btnAnali.style.opacity = '.45';
            btnUsar.style.display = 'none';
            resultado.style.display = 'none';
            aviso.style.display = 'none';

            statusBox.style.cssText = 'display:flex;align-items:center;gap:12px;background:#f0f7f0;border-left:4px solid #1b8a00;border-radius:10px;padding:13px 16px;margin-top:14px;font-size:.88rem;color:#3a5c38;';
            document.getElementById('ocr-spinner').style.display = 'block';
            statusMsg.textContent = 'Analisando boletim com IA…';

            try {
                const resp = await fetch('ocr_proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ imagem: slot.base64, tipo: slot.tipo })
                });

                document.getElementById('ocr-spinner').style.display = 'none';
                statusBox.style.display = 'none';

                // Tenta fazer parse do JSON — se o PHP retornou HTML de erro, mostra o texto bruto
                const textoResposta = await resp.text();
                let json;
                try {
                    json = JSON.parse(textoResposta);
                } catch (parseErr) {
                    // PHP retornou HTML (erro fatal, warning, etc.) — mostra diagnóstico
                    const erroHtml = textoResposta.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 300);
                    aviso.innerHTML = '⚠️ O servidor retornou um erro PHP:<br><code style="font-size:.75rem;background:#fff3cd;padding:4px 8px;border-radius:4px;display:block;margin-top:6px;white-space:pre-wrap;">' + erroHtml + '</code>';
                    aviso.style.display = 'block';
                    btnAnali.disabled = false; btnAnali.style.opacity = '1';
                    return;
                }

                if (json.erro) {
                    aviso.textContent = '⚠️ ' + json.erro;
                    aviso.style.display = 'block';
                    btnAnali.disabled = false; btnAnali.style.opacity = '1';
                    return;
                }

                // Proxy retorna ano6, ano7, ano8, ano9 diretamente (sem wrapper "anos")
                // Suporta também formato antigo com wrapper json.anos
                const fonteAnos = (json.anos && typeof json.anos === 'object') ? json.anos : json;
                const anosNoJSON = ['ano6','ano7','ano8','ano9'].filter(k => fonteAnos[k] && typeof fonteAnos[k] === 'object' && Object.keys(fonteAnos[k]).length > 0);

                if (anosNoJSON.length === 0) {
                    aviso.textContent = '⚠️ Nenhuma disciplina reconhecida. Tente com imagem mais nítida.';
                    aviso.style.display = 'block';
                    btnAnali.disabled = false; btnAnali.style.opacity = '1';
                    return;
                }

                // Define ano detectado para o badge (mais recente)
                const anosPresentes = anosNoJSON.map(k => parseInt(k.replace('ano',''))).sort((a,b) => b-a);
                anoDetectado = anosPresentes[0] || 9;

                // Preenche notasFinais para todos os anos retornados
                notasFinais = new Array(CAMPOS_ORDEM.length).fill(null);

                anosNoJSON.forEach(chaveAno => {
                    const anoNum = parseInt(chaveAno.replace('ano', ''));
                    if (isNaN(anoNum) || anoNum < 6 || anoNum > 9) return;
                    const anoIdx = anoNum - 6; // ano6→0, ano7→1, ano8→2, ano9→3

                    // Suporta {portugues: 8.5} direto OU {disciplinas: {portugues: 8.5}}
                    const dadosAno = fonteAnos[chaveAno];
                    const discs = (dadosAno.disciplinas && typeof dadosAno.disciplinas === 'object')
                        ? dadosAno.disciplinas
                        : dadosAno;

                    DISCIPLINAS_TABELA.forEach((disc, discIdx) => {
                        let chave = disc;
                        // filosofia → religiao se não houver religiao
                        if (disc === 'religiao' && discs['religiao'] === undefined && discs['filosofia'] !== undefined) {
                            chave = 'filosofia';
                        }
                        const val = discs[chave];
                        if (val === undefined || val === null || typeof val !== 'number') return;
                        const campoIdx = anoIdx * DISCIPLINAS_TABELA.length + discIdx;
                        notasFinais[campoIdx] = val;
                    });
                });

                renderSlot();
                renderizarResultado();

            } catch (err) {
                document.getElementById('ocr-spinner').style.display = 'none';
                statusBox.style.display = 'none';
                aviso.innerHTML = '⚠️ Erro ao contactar o servidor: <code style="font-size:.75rem;">' + err.message + '</code><br><small>Verifique se o XAMPP está rodando e o arquivo ocr_proxy.php está na mesma pasta.</small>';
                aviso.style.display = 'block';
            }

            btnAnali.disabled = false; btnAnali.style.opacity = '1';
        });

        // ── Lista de notas com exclusão individual ──
        function renderizarResultado() {
            const lista = document.getElementById('ocr-lista-notas');
            lista.innerHTML = '';

            const ativas = notasFinais.filter(n => n !== null);
            if (ativas.length === 0) {
                resultado.style.display = 'none';
                btnUsar.style.display   = 'none';
                return;
            }

            // Calcula quais anos têm notas para exibir no cabeçalho
            const anosComNotas = [];
            [6,7,8,9].forEach(ano => {
                const anoIdx = ano - 6;
                const temNota = DISCIPLINAS_TABELA.some((_, di) => notasFinais[anoIdx * DISCIPLINAS_TABELA.length + di] !== null);
                if (temNota) anosComNotas.push(ano + 'º');
            });
            const totalNotas = notasFinais.filter(n => n !== null).length;
            const anosLabel = anosComNotas.length > 0 ? anosComNotas.join(', ') : 'ano detectado';

            const cabec = document.createElement('div');
            cabec.style.cssText = 'font-size:.8rem;color:#555;margin-bottom:10px;padding:8px 12px;background:#e8f5e9;border-radius:8px;border-left:3px solid #1b8a00;';
            cabec.innerHTML = `🏫 <strong>${totalNotas} notas</strong> lidas dos anos: <strong>${anosLabel}</strong> — confira e clique em <strong>"Preencher automaticamente"</strong>`;
            lista.appendChild(cabec);

            notasFinais.forEach((val, i) => {
                if (val === null) return;
                const label = LABELS_CAMPOS[i];
                const cor   = val >= 7 ? '#1b5e20' : val >= 5 ? '#e65100' : '#b71c1c';
                const card  = document.createElement('div');
                card.className = 'nota-card';
                card.id = `nota-card-${i}`;
                card.innerHTML = `
                    <span class="nota-num" style="color:${cor};">${val.toFixed(2)}</span>
                    <span class="nota-pos" style="flex:1;padding:0 10px;">→ ${label}</span>
                    <button type="button" class="nota-excluir" data-idx="${i}"
                        style="background:#fff0f0;border:1.5px solid #ffcdd2;color:#c62828;border-radius:6px;padding:3px 8px;cursor:pointer;font-size:.75rem;font-weight:700;flex-shrink:0;">✕</button>`;
                lista.appendChild(card);
            });

            lista.querySelectorAll('.nota-excluir').forEach(btn => {
                btn.addEventListener('click', () => {
                    notasFinais[parseInt(btn.dataset.idx)] = null;
                    renderizarResultado();
                });
            });

            const totalAtivas = notasFinais.filter(n => n !== null).length;
            const soma  = notasFinais.filter(n => n !== null).reduce((a, b) => a + b, 0);
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
            slot = { base64: null, tipo: 'image/jpeg' };
            discReconhecidas = {}; anoDetectado = null;
            notasFinais = new Array(CAMPOS_ORDEM.length).fill(null);
            renderSlot(); esconderResultado();
            statusBox.style.display = 'none';
        });

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