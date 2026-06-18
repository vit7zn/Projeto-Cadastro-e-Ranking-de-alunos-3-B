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
    <title>Cadastro de Estudante — SIPS</title>
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
        <a href="disparar_resultado.php">📲 WhatsApp</a>
        <a href="index.HTML">🚪 Sair</a>
        <div class="sidebar-footer">SIPS © 2026</div>
    </nav>

    <header class="navbar">
        <div class="navbar-left">
            <button class="btn-hamburguer" id="btn-hamburguer" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <a class="navbar-brand" href="index.HTML">
                <img src="logo_sips.svg" alt="Logo">
                SIPS
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
            <a href="disparar_resultado.php" class="nav-card" style="background:rgba(37,211,102,.1);border-color:rgba(37,211,102,.22);color:rgba(37,211,102,.85);">
              <span class="nav-card-icon">📲</span><span>WhatsApp</span>
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

    <style>
    /* ══════════════════════════════════════════
       LAYOUT PRINCIPAL — coluna única centralizada
    ══════════════════════════════════════════ */
    .cadastro-layout {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: calc(100vh - 64px);
        padding: 32px 24px 60px;
        gap: 28px;
    }

    /* ══════════════════════════════════════════
       PAINEL DE BUSCA — topo, largura máxima
    ══════════════════════════════════════════ */
    .busca-panel {
        width: 100%;
        max-width: 860px;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #e2e8e0;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,.08);
        overflow: hidden;
    }

    /* Cabeçalho do painel de busca */
    .busca-panel-header {
        background: #1b8a00;
        padding: 18px 24px 14px;
        color: #fff;
        flex-shrink: 0;
    }
    .busca-panel-header h2 {
        font-size: 1rem;
        font-weight: 800;
        margin: 0 0 3px;
        letter-spacing: .3px;
    }
    .busca-panel-header p {
        font-size: .74rem;
        margin: 0;
        opacity: .7;
        line-height: 1.4;
    }

    /* Campo de busca */
    .busca-panel-search {
        padding: 14px 20px 12px;
        border-bottom: 1px solid #eef2ee;
        flex-shrink: 0;
        background: #f8faf8;
    }
    .busca-input-wrap {
        position: relative;
    }
    .busca-input-wrap input {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 42px 11px 16px;
        border: 1.5px solid #d4ddd4;
        border-radius: 10px;
        font-size: .92rem;
        background: #fff;
        transition: border-color .2s, box-shadow .2s;
        outline: none;
        font-family: inherit;
    }
    .busca-input-wrap input:focus {
        border-color: #1b8a00;
        box-shadow: 0 0 0 3px rgba(27,138,0,.1);
    }
    .busca-input-wrap .busca-icon {
        position: absolute;
        right: 13px; top: 50%;
        transform: translateY(-50%);
        font-size: .95rem; color: #aaa;
        pointer-events: none;
    }

    /* Corpo do painel — exibe resultados em grid horizontal */
    .busca-panel-body {
        max-height: 260px;
        overflow-y: auto;
        padding: 12px 16px 14px;
        scrollbar-width: thin;
        scrollbar-color: #c8dcc8 transparent;
    }
    .busca-panel-body::-webkit-scrollbar { height: 4px; width: 4px; }
    .busca-panel-body::-webkit-scrollbar-thumb { background: #c8dcc8; border-radius: 4px; }

    /* Resultados em grade */
    #busca-resultados {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 8px;
    }
    .busca-item {
        padding: 11px 13px;
        border-radius: 10px;
        cursor: pointer;
        transition: background .15s, transform .1s;
        background: transparent;
        border: 1.5px solid transparent;
    }
    .busca-item:hover {
        background: #f0fbf0;
        border-color: #c8e6c9;
        transform: translateY(-1px);
    }
    .busca-item.selecionado {
        background: #e8f5e9;
        border-color: #1b8a00;
    }
    .busca-item-nome {
        font-weight: 700;
        font-size: .88rem;
        color: #1a1a1a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .busca-item-meta {
        font-size: .72rem;
        color: #888;
        margin-top: 3px;
        line-height: 1.4;
    }
    .busca-item-media {
        display: inline-block;
        margin-top: 5px;
        font-size: .72rem;
        font-weight: 700;
        color: #fff;
        background: #1b8a00;
        border-radius: 20px;
        padding: 2px 9px;
    }

    /* Placeholder */
    .busca-placeholder {
        text-align: center;
        padding: 40px 16px;
        color: #bbb;
        font-size: .83rem;
        line-height: 1.6;
    }
    .busca-placeholder .busca-icon-big {
        font-size: 2.8rem;
        margin-bottom: 10px;
        display: block;
        opacity: .5;
    }

    /* Boletim vinculado */
    #busca-boletim-vinculado {
        background: #f0fbf0;
        border-top: 1.5px solid #a5d6a7;
        display: none;
    }
    #busca-boletim-vinculado .bv-titulo {
        font-size: .75rem;
        font-weight: 700;
        color: #1b8a00;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .bv-card {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #c8e6c9;
        border-radius: 8px;
        padding: 7px 10px;
        font-size: .77rem;
        max-width: 300px;
    }
    .bv-card-nome { flex: 1; font-weight: 600; color: #1b8a00; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .bv-card-meta { font-size: .69rem; color: #888; }

    /* Placeholder — ocupa todo o grid */
    .busca-placeholder {
        grid-column: 1 / -1;
        text-align: center;
        padding: 28px 16px;
        color: #bbb;
        font-size: .83rem;
        line-height: 1.6;
    }
    .busca-placeholder .busca-icon-big {
        font-size: 2.4rem;
        margin-bottom: 8px;
        display: block;
        opacity: .5;
    }

    /* ══════════════════════════════════════════
       COLUNA DO FORMULÁRIO — centralizada abaixo
    ══════════════════════════════════════════ */
    .form-column {
        width: 100%;
        max-width: 860px;
        padding: 0;
    }
    .form-column h1 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-main, #1a1a1a);
        margin: 0 0 24px;
    }

    /* Toggle switch */
    .toggle-salvar-wrap { display:flex; align-items:center; gap:12px; flex-wrap:wrap; flex:1; }
    .toggle-switch { position:relative; display:inline-block; width:44px; height:24px; flex-shrink:0; }
    .toggle-switch input { opacity:0; width:0; height:0; }
    .toggle-slider { position:absolute; cursor:pointer; inset:0; background:#ccc; border-radius:34px; transition:.25s; }
    .toggle-slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background:white; border-radius:50%; transition:.25s; box-shadow:0 1px 4px rgba(0,0,0,.25); }
    .toggle-switch input:checked + .toggle-slider { background:#1b8a00; }
    .toggle-switch input:checked + .toggle-slider:before { transform:translateX(20px); }
    .toggle-label-text { font-weight:600; font-size:.88rem; color:#1b8a00; cursor:pointer; user-select:none; }
    .toggle-desc-field { display:none; flex:1; min-width:160px; border:1.5px solid #a5d6a7; border-radius:8px; padding:6px 10px; font-size:.85rem; outline:none; transition:border-color .2s; }
    .toggle-desc-field:focus { border-color:#1b8a00; }

    @media (max-width: 900px) {
        .cadastro-layout { padding: 20px 12px 40px; }
        .busca-panel-body { max-height: 200px; }
        #busca-resultados { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
    }
    </style>

    <main style="background:#f4f6f4; min-height:calc(100vh - 64px);">
      <div class="cadastro-layout">

        <!-- ══════════════════════════════════════ -->
        <!--  PAINEL DE BUSCA (topo, centralizado)  -->
        <!-- ══════════════════════════════════════ -->
        <div class="busca-panel">
            <div class="busca-panel-header">
                <h2>🔍 Buscar Aluno</h2>
                <p>Selecione para carregar os dados já cadastrados</p>
            </div>
            <div class="busca-panel-search">
                <div class="busca-input-wrap">
                    <input type="text" id="busca-nome-input" placeholder="Digite o nome do aluno…" autocomplete="off">
                    <span class="busca-icon">🔍</span>
                </div>
            </div>
            <div class="busca-panel-body">
                <div id="busca-resultados">
                    <div class="busca-placeholder">
                        <span class="busca-icon-big">🎓</span>
                        Digite ao menos 2 letras para buscar
                    </div>
                </div>
            </div>

            <!-- Boletim vinculado ao aluno selecionado -->
            <div id="busca-boletim-vinculado" style="margin:0; border-radius:0; border-left:none; border-right:none; border-bottom:none; border-top:1px solid #c5dff8;">
                <div class="bv-titulo" style="padding:10px 20px 6px;">📄 Boletim(s) vinculado(s)</div>
                <div id="bv-lista" style="padding:0 16px 12px; display:flex; flex-wrap:wrap; gap:6px;"></div>
                <div id="bv-vazio" style="font-size:.76rem;color:#888;display:none;padding:0 20px 12px;">
                    Nenhum boletim encontrado para este aluno.
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════ -->
        <!--  COLUNA DO FORMULÁRIO (abaixo)         -->
        <!-- ══════════════════════════════════════ -->
        <div class="form-column">
        <h1>📋 Cadastro de Estudante</h1>

        <?php if (($_GET['cadastro'] ?? '') === 'ok'): ?>
        <div id="aviso-cadastro-ok" style="display:flex;align-items:center;gap:12px;background:#e8f5e9;border:2px solid #2e7d32;border-radius:12px;padding:14px 20px;margin-bottom:20px;font-size:.92rem;color:#1b5e20;font-weight:600;">
            ✅ Cadastro salvo com sucesso! Os dados do aluno foram registrados.
            <button type="button" onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;font-size:1.1rem;cursor:pointer;color:#2e7d32;">✕</button>
        </div>
        <?php endif; ?>

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
                        <label>Cidade</label>
                        <input type="text" name="cidade" id="cidade" placeholder="Ex: Crateús" required>
                    </div>
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

                    <div style="font-weight:700; color:#1b8a00; font-size:.95rem; margin-bottom:14px;">
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
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:start;">
                            <div>
                                <label style="font-weight:600;font-size:.85rem;display:block;margin-bottom:8px;">Recursos de acessibilidade necessários</label>
                                <div style="display:flex; flex-direction:column; gap:7px;">
                                    <label style="font-weight:normal;display:grid;grid-template-columns:16px 1fr;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="interprete_libras"> <span>Intérprete de Libras</span></label>
                                    <label style="font-weight:normal;display:grid;grid-template-columns:16px 1fr;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="material_braille"> <span>Material em Braille</span></label>
                                    <label style="font-weight:normal;display:grid;grid-template-columns:16px 1fr;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="ampliacao_fonte"> <span>Ampliação de fonte / Prova ampliada</span></label>
                                    <label style="font-weight:normal;display:grid;grid-template-columns:16px 1fr;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="tempo_adicional"> <span>Tempo adicional para provas</span></label>
                                    <label style="font-weight:normal;display:grid;grid-template-columns:16px 1fr;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="sala_especial"> <span>Sala de prova especial / acessível</span></label>
                                    <label style="font-weight:normal;display:grid;grid-template-columns:16px 1fr;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="ledor"> <span>Ledor / Transcritor</span></label>
                                    <label style="font-weight:normal;display:grid;grid-template-columns:16px 1fr;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="computador"> <span>Uso de computador / tecnologia assistiva</span></label>
                                    <label style="font-weight:normal;display:grid;grid-template-columns:16px 1fr;align-items:center;gap:8px;"><input type="checkbox" name="acessibilidade[]" value="outra_adaptacao"> <span>Outra adaptação</span></label>
                                </div>
                            </div>
                            <div>
                                <label style="font-weight:600;font-size:.85rem;display:block;margin-bottom:8px;">Observações adicionais sobre as necessidades</label>
                                <textarea name="obs_necessidades" id="obs_necessidades"
                                          rows="9"
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

            <!-- ═══════════════════════════════════════════════════════ -->
            <!--  MODAL OCR — com Biblioteca de Boletins                -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div id="ocr-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(3px);z-index:9000;align-items:center;justify-content:center;">
                <div style="background:#fff;border-radius:18px;width:min(700px,96vw);box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;animation:ocrFadeIn .25s ease;max-height:93vh;display:flex;flex-direction:column;">

                    <!-- Cabeçalho fixo -->
                    <div style="background:#1b8a00;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                        <div>
                            <div style="font-size:1.05rem;font-weight:800;color:#fff;">📷 OCR de Notas com IA</div>
                            <div id="ocr-modal-subtitulo" style="color:rgba(255,255,255,.65);font-size:.78rem;margin-top:3px;">Envie ou selecione um boletim salvo — a IA detecta o ano e preenche automaticamente</div>
                        </div>
                        <button type="button" id="btn-fechar-ocr" style="background:rgba(255,255,255,.15);border:none;color:#fff;font-size:1.2rem;width:34px;height:34px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
                    </div>

                    <!-- ABAS -->
                    <div style="display:flex;border-bottom:2px solid #e8f5e9;background:#f9fdf9;flex-shrink:0;">
                        <button type="button" class="ocr-tab ativa" data-tab="nova"
                            style="flex:1;padding:12px;border:none;background:transparent;cursor:pointer;font-weight:700;font-size:.9rem;color:#1b8a00;border-bottom:3px solid #1b8a00;transition:all .2s;">
                            📤 Enviar novo
                        </button>
                        <button type="button" class="ocr-tab" data-tab="salvos"
                            style="flex:1;padding:12px;border:none;background:transparent;cursor:pointer;font-weight:600;font-size:.9rem;color:#888;border-bottom:3px solid transparent;transition:all .2s;">
                            📁 Boletim salvo
                        </button>
                    </div>

                    <!-- Corpo com scroll -->
                    <div style="padding:22px 24px;overflow-y:auto;flex:1;">

                        <!-- ══ ABA 1: ENVIAR NOVO ══ -->
                        <div id="ocr-tab-nova">

                            <!-- Botão Salvar boletim -->
                            <div style="margin-bottom:16px;">
                                <button type="button" id="btn-salvar-boletim"
                                        style="background:#fff;color:#1b8a00;border:2px solid #1b8a00;padding:9px 20px;border-radius:8px;font-size:.88rem;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .18s,box-shadow .18s;">
                                    💾 Salvar boletim
                                </button>
                            </div>

                            <!-- Slot de upload -->
                            <div id="ocr-slots" style="display:flex;flex-direction:column;gap:12px;"></div>

                            <!-- Status / spinner -->
                            <div id="ocr-status" style="display:none;align-items:center;gap:12px;background:#f0f7f0;border-left:4px solid #1b8a00;border-radius:10px;padding:13px 16px;margin-top:14px;font-size:.88rem;color:#3a5c38;">
                                <div id="ocr-spinner" style="width:20px;height:20px;border:3px solid #d4e4d0;border-top-color:#1b8a00;border-radius:50%;animation:spin .8s linear infinite;flex-shrink:0;display:none;"></div>
                                <span id="ocr-status-msg">Analisando…</span>
                            </div>

                            <!-- Resultado -->
                            <div id="ocr-resultado" style="display:none;margin-top:18px;">
                                <div style="font-weight:700;color:#1b8a00;font-size:.9rem;margin-bottom:4px;">
                                    ✅ Notas prontas — confira e clique em <strong>"Preencher automaticamente"</strong>:
                                </div>
                                <div id="ocr-resumo" style="font-size:.8rem;color:#555;margin-bottom:14px;"></div>
                                <div id="ocr-lista-notas" style="display:flex;flex-direction:column;gap:6px;max-height:260px;overflow-y:auto;padding-right:4px;"></div>
                            </div>

                            <!-- Aviso de erro -->
                            <div id="ocr-aviso" style="display:none;background:#fff5f5;border:1px solid #ffcdd2;border-radius:10px;padding:12px 16px;margin-top:14px;font-size:.83rem;color:#c62828;"></div>

                            <!-- Botões -->
                            <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap;">
                                <button type="button" id="btn-ocr-analisar" disabled
                                    style="flex:1;min-width:140px;background:#1b8a00;color:#fff;border:none;padding:13px;border-radius:30px;font-weight:700;font-size:.92rem;cursor:pointer;opacity:.45;transition:opacity .2s,background .18s;">
                                    🔍 Analisar com IA
                                </button>
                                <button type="button" id="btn-ocr-usar" style="display:none;background:#1b8a00;color:#fff;border:none;padding:13px 18px;border-radius:30px;font-weight:700;font-size:.9rem;cursor:pointer;">
                                    ✅ Preencher automaticamente
                                </button>
                                <button type="button" id="btn-ocr-limpar" style="background:#f0f0f0;color:#555;border:none;padding:13px 18px;border-radius:30px;font-weight:600;font-size:.9rem;cursor:pointer;">
                                    🗑️ Limpar tudo
                                </button>
                            </div>
                        </div><!-- /aba nova -->

                        <!-- ══ ABA 2: BOLETINS SALVOS ══ -->
                        <div id="ocr-tab-salvos" style="display:none;">

                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:10px;flex-wrap:wrap;">
                                <span style="font-size:.85rem;color:#555;">Selecione um boletim já enviado para rodar o OCR sem precisar enviar novamente.</span>
                                <button type="button" id="btn-refresh-boletins"
                                        style="background:#e8f5e9;border:1.5px solid #a5d6a7;color:#2e7d32;border-radius:8px;padding:6px 12px;font-size:.8rem;font-weight:600;cursor:pointer;">
                                    🔄 Atualizar
                                </button>
                            </div>

                            <!-- Carregando -->
                            <div id="boletins-loading" style="text-align:center;padding:24px;color:#888;font-size:.9rem;">
                                <div style="width:28px;height:28px;border:3px solid #ddd;border-top-color:#1b8a00;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 10px;"></div>
                                Carregando boletins…
                            </div>

                            <!-- Vazio -->
                            <div id="boletins-vazio" style="display:none;text-align:center;padding:32px 16px;color:#888;">
                                <div style="font-size:2.5rem;margin-bottom:10px;">📂</div>
                                <div style="font-weight:600;margin-bottom:6px;">Nenhum boletim salvo ainda</div>
                                <div style="font-size:.82rem;">Use a aba <strong>"Enviar novo"</strong> e marque a opção de salvar.</div>
                            </div>

                            <!-- Lista -->
                            <div id="boletins-lista" style="display:none;flex-direction:column;gap:8px;max-height:340px;overflow-y:auto;padding-right:4px;"></div>

                            <!-- Resultado OCR do boletim selecionado -->
                            <div id="ocr-resultado-salvo" style="display:none;margin-top:18px;">
                                <div style="font-weight:700;color:#1b8a00;font-size:.9rem;margin-bottom:4px;">
                                    ✅ Notas prontas — confira e clique em <strong>"Preencher automaticamente"</strong>:
                                </div>
                                <div id="ocr-resumo-salvo" style="font-size:.8rem;color:#555;margin-bottom:14px;"></div>
                                <div id="ocr-lista-notas-salvo" style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow-y:auto;padding-right:4px;"></div>
                                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
                                    <button type="button" id="btn-usar-salvo" style="background:#1b8a00;color:#fff;border:none;padding:13px 22px;border-radius:30px;font-weight:700;font-size:.9rem;cursor:pointer;flex:1;">
                                        ✅ Preencher automaticamente
                                    </button>
                                    <button type="button" id="btn-cancelar-salvo" style="background:#f0f0f0;color:#555;border:none;padding:13px 18px;border-radius:30px;font-weight:600;font-size:.9rem;cursor:pointer;">
                                        ← Voltar
                                    </button>
                                </div>
                            </div>

                            <!-- Erro aba salvos -->
                            <div id="ocr-aviso-salvo" style="display:none;background:#fff5f5;border:1px solid #ffcdd2;border-radius:10px;padding:12px 16px;margin-top:14px;font-size:.83rem;color:#c62828;"></div>

                        </div><!-- /aba salvos -->

                    </div><!-- /corpo -->
                </div>
            </div><!-- /ocr-overlay -->

            <!-- ═══════════════════════════════════════════════════════ -->
            <!--  BOTÕES DE AÇÃO                                         -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <input type="hidden" name="modo_envio" id="modo_envio" value="">

            <div class="botoes-acao-wrap">
                <div class="botao-acao-grupo">
                    <button type="button" class="btn-salvar-dados" id="btn-salvar-dados" onclick="submeterFormulario('salvar')">
                        💾 Salvar Cadastro
                    </button>
                    <span class="botao-desc">Salva os dados do aluno sem enviar notas ao ranking</span>
                </div>
                <div class="separador-ou">ou</div>
                <div class="botao-acao-grupo">
                    <button type="button" class="btn-enviar-ranking" id="btn-enviar-ranking" onclick="submeterFormulario('ranking')">
                        🏆 Enviar para o Ranking
                    </button>
                    <span class="botao-desc">Salva os dados e publica as notas no ranking oficial</span>
                </div>
            </div>
        </form>

        <style>
        .botoes-acao-wrap {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 28px;
            padding: 24px 28px;
            background: #f9fdf9;
            border: 2px solid #d4e8d0;
            border-radius: 16px;
        }
        .botao-acao-grupo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 200px;
        }
        .botao-desc {
            font-size: .73rem;
            color: #888;
            text-align: center;
            max-width: 220px;
            line-height: 1.4;
        }
        .separador-ou {
            font-weight: 700;
            color: #bbb;
            font-size: .9rem;
            flex-shrink: 0;
        }
        .btn-salvar-dados {
            width: 100%;
            padding: 14px 20px;
            border-radius: 12px;
            border: 2.5px solid #1b8a00;
            background: #fff;
            color: #1b8a00;
            font-weight: 800;
            font-size: .95rem;
            cursor: pointer;
            letter-spacing: .4px;
            transition: background .18s, color .18s, box-shadow .18s;
            box-shadow: 0 2px 8px rgba(27,138,0,.1);
        }
        .btn-salvar-dados:hover {
            background: #e8f5e9;
            box-shadow: 0 4px 16px rgba(27,138,0,.18);
        }
        .btn-salvar-dados:active {
            background: #c8e6c9;
        }
        .btn-enviar-ranking {
            width: 100%;
            padding: 14px 20px;
            border-radius: 12px;
            border: none;
            background: #1b8a00;
            color: #fff;
            font-weight: 800;
            font-size: .95rem;
            cursor: pointer;
            letter-spacing: .4px;
            transition: background .18s, box-shadow .18s;
            box-shadow: 0 4px 14px rgba(27,138,0,.25);
        }
        .btn-enviar-ranking:hover {
            background: #156e00;
            box-shadow: 0 6px 20px rgba(27,138,0,.32);
        }
        .btn-enviar-ranking:active {
            background: #0e5200;
        }
        @media (max-width: 600px) {
            .botoes-acao-wrap { flex-direction: column; gap: 14px; }
            .separador-ou { display: none; }
            .botao-acao-grupo { width: 100%; }
        }
        </style>

        <script>
        function submeterFormulario(modo) {
            // Define o modo de envio antes de submeter
            document.getElementById('modo_envio').value = modo;

            // Se for só salvar os dados, não precisa recalcular categoria/média
            if (modo === 'ranking') {
                // Recalcular categoria e média antes de enviar para o ranking
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
            } else {
                // Modo "salvar": zera média e categoria para não interferir no ranking
                document.getElementById('media_final').value       = '0.00000';
                document.getElementById('categoria_ranking').value = '';
            }

            document.querySelector('.student-form').submit();
        }
        </script>
    </main>

    <style>
    @keyframes ocrFadeIn { from { opacity:0; transform:translateY(-16px); } to { opacity:1; transform:none; } }
    @keyframes spin      { to { transform:rotate(360deg); } }
    #ocr-overlay { display:none; }
    #ocr-overlay.aberto { display:flex; }

    /* Cartão de cada nota na lista vertical */
    .nota-card {
        display:flex; align-items:center; gap:8px;
        background:#fff; border:1.5px solid #d4e8d0;
        border-radius:10px; padding:7px 10px; font-size:.9rem;
    }
    .nota-card .nota-num { font-weight:800; font-size:1rem; min-width:40px; }
    .nota-card .nota-pos { font-size:.72rem; color:#888; font-family:monospace; flex:1; }
    .nota-card.aplicada  { border-color:#1b8a00; background:#f0fbf0; opacity:.6; }
    .nota-card.aplicada .nota-num { color:#1b8a00; }

    /* Cartão de boletim salvo */
    .boletim-card {
        display:flex; align-items:center; gap:12px;
        background:#fff; border:1.5px solid #e0e0e0;
        border-radius:12px; padding:12px 14px;
        transition:border-color .2s, box-shadow .2s;
    }
    .boletim-card:hover { border-color:#1b8a00; box-shadow:0 2px 12px rgba(27,138,0,.1); }
    .boletim-card-info  { flex:1; min-width:0; }
    .boletim-card-nome  { font-weight:700; font-size:.9rem; color:#222; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .boletim-card-meta  { font-size:.75rem; color:#888; margin-top:2px; }
    .boletim-card-acoes { display:flex; gap:8px; flex-shrink:0; }

    /* Abas */
    .ocr-tab       { border-bottom:3px solid transparent !important; }
    .ocr-tab.ativa { color:#1b8a00 !important; border-bottom-color:#1b8a00 !important; font-weight:700 !important; }
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
    //  LÓGICA DE CATEGORIA — movida para
    //  submeterFormulario() nos botões de ação
    // ══════════════════════════════════════════

    // ══════════════════════════════════════════════════════════
    //  OCR MODAL — com Biblioteca de Boletins Salvos
    // ══════════════════════════════════════════════════════════
    (function () {

        // ── Constantes de disciplinas ──────────────────────────
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

        // ── Estado ─────────────────────────────────────────────
        let slot             = { base64: null, tipo: 'image/jpeg', arquivoOriginal: null };
        let anoDetectado     = null;
        let notasFinais      = new Array(CAMPOS_ORDEM.length).fill(null);
        let notasSalvas      = new Array(CAMPOS_ORDEM.length).fill(null);

        // ── Elementos — compartilhados ──────────────────────────
        const overlay   = document.getElementById('ocr-overlay');
        const btnAbrir  = document.getElementById('btn-abrir-ocr');
        const btnFechar = document.getElementById('btn-fechar-ocr');

        // ── Elementos — aba "nova" ──────────────────────────────
        const btnAnali   = document.getElementById('btn-ocr-analisar');
        const btnUsar    = document.getElementById('btn-ocr-usar');
        const btnLimpar  = document.getElementById('btn-ocr-limpar');
        const statusBox  = document.getElementById('ocr-status');
        const statusMsg  = document.getElementById('ocr-status-msg');
        const aviso      = document.getElementById('ocr-aviso');
        const resultado  = document.getElementById('ocr-resultado');
        const btnSalvarBoletim = document.getElementById('btn-salvar-boletim');

        // ── Elementos — aba "salvos" ────────────────────────────
        const btnRefresh      = document.getElementById('btn-refresh-boletins');
        const boletimsLista   = document.getElementById('boletins-lista');
        const boletimsVazio   = document.getElementById('boletins-vazio');
        const boletimsLoad    = document.getElementById('boletins-loading');
        const resultadoSalvo  = document.getElementById('ocr-resultado-salvo');
        const avisoSalvo      = document.getElementById('ocr-aviso-salvo');
        const btnUsarSalvo    = document.getElementById('btn-usar-salvo');
        const btnCancSalvo    = document.getElementById('btn-cancelar-salvo');

        // ── Abrir / fechar ──────────────────────────────────────
        btnAbrir.addEventListener('click', () => {
            overlay.classList.add('aberto');
            const subtitulo = document.getElementById('ocr-modal-subtitulo');
            if (window.alunoSelecionadoId) {
                // Aluno selecionado: abre direto nos boletins salvos dele
                if (subtitulo) subtitulo.innerHTML = `🎓 Boletins de <strong style="color:#fff;">${window.alunoSelecionadoNome}</strong>`;
                ativarAba('salvos');
                carregarBoletins();
            } else {
                if (subtitulo) subtitulo.textContent = 'Envie ou selecione um boletim salvo — a IA detecta o ano e preenche automaticamente';
                ativarAba('nova');
                renderSlot();
            }
        });
        btnFechar.addEventListener('click', fechar);
        overlay.addEventListener('click', e => { if (e.target === overlay) fechar(); });
        function fechar() { overlay.classList.remove('aberto'); }

        // ── Navegação de abas ────────────────────────────────────
        document.querySelectorAll('.ocr-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                ativarAba(tab.dataset.tab);
                if (tab.dataset.tab === 'salvos') carregarBoletins();
            });
        });

        function ativarAba(nome) {
            document.querySelectorAll('.ocr-tab').forEach(t => {
                const ativa = t.dataset.tab === nome;
                t.classList.toggle('ativa', ativa);
                t.style.color      = ativa ? '#1b8a00' : '#888';
                t.style.fontWeight = ativa ? '700' : '600';
                t.style.borderBottom = ativa ? '3px solid #1b8a00' : '3px solid transparent';
            });
            document.getElementById('ocr-tab-nova').style.display   = nome === 'nova'   ? '' : 'none';
            document.getElementById('ocr-tab-salvos').style.display = nome === 'salvos' ? '' : 'none';
        }

        // ── Botão salvar boletim ─────────────────────────────────
        btnSalvarBoletim.addEventListener('click', async () => {
            if (!slot.arquivoOriginal) {
                alert('Selecione um arquivo antes de salvar.');
                return;
            }
            btnSalvarBoletim.disabled = true;
            btnSalvarBoletim.textContent = '⏳ Salvando…';
            await salvarBoletimNoServidor();
            btnSalvarBoletim.disabled = false;
            btnSalvarBoletim.textContent = '💾 Salvar boletim';
        });

        // ── PDF.js worker ────────────────────────────────────────
        if (window.pdfjsLib && !pdfjsLib.GlobalWorkerOptions.workerSrc) {
            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }

        // ═══════════════════════════════════════════════════════
        //  ABA 1 — ENVIAR NOVO
        // ═══════════════════════════════════════════════════════

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
                </div>`;

            document.getElementById('slot-file-input').addEventListener('change', e => {
                if (e.target.files[0]) carregarArquivo(e.target.files[0]);
            });

            const btnRem = document.getElementById('slot-remover');
            if (btnRem) {
                btnRem.addEventListener('click', () => {
                    slot = { base64: null, tipo: 'image/jpeg', arquivoOriginal: null };
                    anoDetectado = null;
                    notasFinais = new Array(CAMPOS_ORDEM.length).fill(null);
                    renderSlot(); atualizarBotaoAnalisar(); esconderResultado();
                });
            }

            atualizarBotaoAnalisar();
        }

        // ── Carrega arquivo (PDF → canvas ou imagem direta) ──────
        async function carregarArquivo(file) {
            const tipo = file.type || 'image/jpeg';
            esconderResultado(); anoDetectado = null;

            if (tipo === 'application/pdf') {
                try {
                    const arrayBuffer = await file.arrayBuffer();
                    const pdfDoc = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    const scale  = 2.0;
                    const canvases = [];
                    for (let p = 1; p <= pdfDoc.numPages; p++) {
                        const page     = await pdfDoc.getPage(p);
                        const viewport = page.getViewport({ scale });
                        const cv       = document.createElement('canvas');
                        cv.width  = viewport.width;
                        cv.height = viewport.height;
                        await page.render({ canvasContext: cv.getContext('2d'), viewport }).promise;
                        canvases.push(cv);
                    }
                    const totalWidth  = Math.max(...canvases.map(c => c.width));
                    const totalHeight = canvases.reduce((s, c) => s + c.height, 0);
                    const merged      = document.createElement('canvas');
                    merged.width  = totalWidth;
                    merged.height = totalHeight;
                    const ctx = merged.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, totalWidth, totalHeight);
                    let offsetY = 0;
                    for (const cv of canvases) { ctx.drawImage(cv, 0, offsetY); offsetY += cv.height; }
                    slot.base64 = merged.toDataURL('image/jpeg', 0.92).split(',')[1];
                    slot.tipo   = 'image/jpeg';
                    slot.arquivoOriginal = file; // guarda PDF original para salvar
                    renderSlot();
                } catch {
                    aviso.textContent = '⚠️ Não foi possível ler o PDF. Tente converter para JPG/PNG.';
                    aviso.style.display = 'block';
                }
                return;
            }

            const reader = new FileReader();
            reader.onload = e => {
                slot.base64 = e.target.result.split(',')[1];
                slot.tipo   = tipo;
                slot.arquivoOriginal = file;
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

        // ── Botão Analisar ───────────────────────────────────────
        btnAnali.addEventListener('click', async () => {
            if (!slot.base64) return;

            btnAnali.disabled = true; btnAnali.style.opacity = '.45';
            btnUsar.style.display   = 'none';
            resultado.style.display = 'none';
            aviso.style.display     = 'none';

            statusBox.style.cssText = 'display:flex;align-items:center;gap:12px;background:#f0f7f0;border-left:4px solid #1b8a00;border-radius:10px;padding:13px 16px;margin-top:14px;font-size:.88rem;color:#3a5c38;';
            document.getElementById('ocr-spinner').style.display = 'block';
            statusMsg.textContent = 'Analisando boletim com IA…';

            try {
                const resp = await fetch('ocr_proxy.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ imagem: slot.base64, tipo: slot.tipo })
                });

                document.getElementById('ocr-spinner').style.display = 'none';
                statusBox.style.display = 'none';

                const textoResposta = await resp.text();
                let json;
                try { json = JSON.parse(textoResposta); }
                catch {
                    const erroHtml = textoResposta.replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim().slice(0,300);
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

                const fonteAnos  = (json.anos && typeof json.anos === 'object') ? json.anos : json;
                const anosNoJSON = ['ano6','ano7','ano8','ano9'].filter(k =>
                    fonteAnos[k] && typeof fonteAnos[k] === 'object' && Object.keys(fonteAnos[k]).length > 0
                );

                if (anosNoJSON.length === 0) {
                    aviso.textContent = '⚠️ Nenhuma disciplina reconhecida. Tente com imagem mais nítida.';
                    aviso.style.display = 'block';
                    btnAnali.disabled = false; btnAnali.style.opacity = '1';
                    return;
                }

                const anosPresentes = anosNoJSON.map(k => parseInt(k.replace('ano',''))).sort((a,b) => b-a);
                anoDetectado = anosPresentes[0] || 9;
                notasFinais  = new Array(CAMPOS_ORDEM.length).fill(null);
                preencherNotasDeJSON(fonteAnos, anosNoJSON, notasFinais);

                renderSlot();
                renderizarLista('ocr-lista-notas', 'ocr-resumo', notasFinais);
                resultado.style.display = 'block';
                btnUsar.style.display   = 'inline-block';

                // Salvar boletim se marcado
                if (slot.arquivoOriginal) {
                    await salvarBoletimNoServidor();
                }

            } catch (err) {
                document.getElementById('ocr-spinner').style.display = 'none';
                statusBox.style.display = 'none';
                aviso.innerHTML = '⚠️ Erro ao contactar o servidor: <code style="font-size:.75rem;">' + err.message + '</code><br><small>Verifique se o XAMPP está rodando e o arquivo ocr_proxy.php está na mesma pasta.</small>';
                aviso.style.display = 'block';
            }

            btnAnali.disabled = false; btnAnali.style.opacity = '1';
        });

        // ── Preenche notasArray a partir do JSON do proxy ────────
        function preencherNotasDeJSON(fonteAnos, anosNoJSON, destArray) {
            anosNoJSON.forEach(chaveAno => {
                const anoNum = parseInt(chaveAno.replace('ano', ''));
                if (isNaN(anoNum) || anoNum < 6 || anoNum > 9) return;
                const anoIdx  = anoNum - 6;
                const dadosAno = fonteAnos[chaveAno];
                const discs    = (dadosAno.disciplinas && typeof dadosAno.disciplinas === 'object')
                    ? dadosAno.disciplinas : dadosAno;

                DISCIPLINAS_TABELA.forEach((disc, discIdx) => {
                    let chave = disc;
                    if (disc === 'religiao' && discs['religiao'] === undefined && discs['filosofia'] !== undefined) {
                        chave = 'filosofia';
                    }
                    const val = discs[chave];
                    if (val === undefined || val === null || typeof val !== 'number') return;
                    destArray[anoIdx * DISCIPLINAS_TABELA.length + discIdx] = val;
                });
            });
        }

        // ── Renderiza lista de notas (usada pelas duas abas) ─────
        function renderizarLista(listaId, resumoId, notas) {
            const lista   = document.getElementById(listaId);
            const resumoEl = document.getElementById(resumoId);
            lista.innerHTML = '';

            const ativas = notas.filter(n => n !== null);
            if (ativas.length === 0) return;

            const anosComNotas = [];
            [6,7,8,9].forEach(ano => {
                const anoIdx = ano - 6;
                if (DISCIPLINAS_TABELA.some((_, di) => notas[anoIdx * DISCIPLINAS_TABELA.length + di] !== null))
                    anosComNotas.push(ano + 'º');
            });

            const cabec = document.createElement('div');
            cabec.style.cssText = 'font-size:.8rem;color:#555;margin-bottom:10px;padding:8px 12px;background:#e8f5e9;border-radius:8px;border-left:3px solid #1b8a00;';
            cabec.innerHTML = `🏫 <strong>${ativas.length} notas</strong> dos anos: <strong>${anosComNotas.join(', ') || 'detectado'}</strong>`;
            lista.appendChild(cabec);

            notas.forEach((val, i) => {
                if (val === null) return;
                const cor  = val >= 7 ? '#1b5e20' : val >= 5 ? '#e65100' : '#b71c1c';
                const card = document.createElement('div');
                card.className = 'nota-card';
                card.innerHTML = `
                    <span class="nota-num" style="color:${cor};">${val.toFixed(2)}</span>
                    <span class="nota-pos" style="flex:1;padding:0 10px;">→ ${LABELS_CAMPOS[i]}</span>
                    <button type="button" class="nota-excluir" data-idx="${i}" data-lista="${listaId}"
                        style="background:#fff0f0;border:1.5px solid #ffcdd2;color:#c62828;border-radius:6px;padding:3px 8px;cursor:pointer;font-size:.75rem;font-weight:700;flex-shrink:0;">✕</button>`;
                lista.appendChild(card);
            });

            lista.querySelectorAll('.nota-excluir').forEach(btn => {
                btn.addEventListener('click', () => {
                    const idx = parseInt(btn.dataset.idx);
                    if (btn.dataset.lista === 'ocr-lista-notas') notasFinais[idx] = null;
                    else notasSalvas[idx] = null;
                    renderizarLista(btn.dataset.lista, resumoId,
                        btn.dataset.lista === 'ocr-lista-notas' ? notasFinais : notasSalvas);
                });
            });

            const soma  = ativas.reduce((a, b) => a + b, 0);
            resumoEl.textContent = `${ativas.length} nota${ativas.length !== 1 ? 's' : ''} · Média: ${(soma / ativas.length).toFixed(5)}`;
        }

        // ── Aplicar notas no formulário ──────────────────────────
        function aplicarNotas(notas) {
            let preenchidos = 0;
            notas.forEach((val, i) => {
                if (val === null) return;
                const input = document.querySelector(`input[name="${CAMPOS_ORDEM[i]}"]`);
                if (input) { input.value = val.toFixed(2); preenchidos++; }
            });
            atualizarMedia();
            fechar();
            if (preenchidos === 0) alert('Nenhuma nota pôde ser aplicada.');
        }

        btnUsar.addEventListener('click',     () => aplicarNotas(notasFinais));
        btnUsarSalvo.addEventListener('click', () => aplicarNotas(notasSalvas));

        btnLimpar.addEventListener('click', () => {
            slot = { base64: null, tipo: 'image/jpeg', arquivoOriginal: null };
            anoDetectado = null;
            notasFinais  = new Array(CAMPOS_ORDEM.length).fill(null);
            renderSlot(); esconderResultado();
            statusBox.style.display = 'none';
        });

        btnCancSalvo.addEventListener('click', () => {
            resultadoSalvo.style.display = 'none';
            avisoSalvo.style.display     = 'none';
            boletimsLista.style.display  = 'flex';
        });

        renderSlot(); // inicializa slot vazio

        // ═══════════════════════════════════════════════════════
        //  SALVAR BOLETIM NO SERVIDOR
        // ═══════════════════════════════════════════════════════
        async function salvarBoletimNoServidor() {
            const file = slot.arquivoOriginal;
            if (!file) return;

            // Usa o nome do aluno selecionado ou, se não houver, o que está digitado no campo
            const nomeParaDescricao = window.alunoSelecionadoNome
                || (document.querySelector('[name="nome_aluno"]')?.value?.trim() ?? '');

            const fd = new FormData();
            fd.append('acao',      'upload');
            fd.append('boletim',   file, file.name);
            fd.append('descricao', nomeParaDescricao);
            // Vincula ao aluno selecionado (se já existe no banco)
            if (window.alunoSelecionadoId) {
                fd.append('aluno_id', window.alunoSelecionadoId);
            }

            try {
                const resp = await fetch('boletins_api.php', { method: 'POST', body: fd });
                const json = await resp.json();
                if (json.ok) {
                    const badge = document.createElement('div');
                    badge.style.cssText = 'background:#e8f5e9;border:1px solid #a5d6a7;border-radius:8px;padding:8px 12px;font-size:.82rem;color:#2e7d32;margin-top:10px;';
                    badge.textContent = '✔ Boletim salvo na sua biblioteca!';
                    document.getElementById('ocr-tab-nova').appendChild(badge);
                    setTimeout(() => badge.remove(), 4000);
                } else {
                    alert('Erro ao salvar: ' + (json.erro || 'Tente novamente.'));
                }
            } catch { alert('Erro de rede ao salvar o boletim.'); }
        }

        // ═══════════════════════════════════════════════════════
        //  ABA 2 — BOLETINS SALVOS
        // ═══════════════════════════════════════════════════════

        async function carregarBoletins() {
            boletimsLoad.style.display   = 'block';
            boletimsVazio.style.display  = 'none';
            boletimsLista.style.display  = 'none';
            resultadoSalvo.style.display = 'none';
            avisoSalvo.style.display     = 'none';

            const alunoId = window.alunoSelecionadoId;

            try {
                let boletins = [];

                if (alunoId) {
                    // buscar_aluno faz auto-vínculo no servidor antes de retornar
                    const resp = await fetch('buscar_aluno.php?acao=carregar&id=' + alunoId);
                    const json = await resp.json();
                    boletimsLoad.style.display = 'none';
                    if (json.ok) boletins = json.boletins || [];
                } else {
                    // Sem aluno selecionado: lista todos
                    const resp = await fetch('boletins_api.php?acao=listar');
                    const json = await resp.json();
                    boletimsLoad.style.display = 'none';
                    if (json.ok) boletins = json.boletins || [];
                }

                if (boletins.length === 0) {
                    boletimsVazio.style.display = 'block';
                    boletimsVazio.innerHTML = alunoId
                        ? `<div style="font-size:2.5rem;margin-bottom:10px;">📂</div>
                           <div style="font-weight:600;margin-bottom:6px;">Nenhum boletim salvo para este aluno</div>
                           <div style="font-size:.82rem;">Use a aba <strong>"Enviar novo"</strong> para salvar o boletim.</div>`
                        : `<div style="font-size:2.5rem;margin-bottom:10px;">📂</div>
                           <div style="font-weight:600;margin-bottom:6px;">Nenhum boletim salvo ainda</div>
                           <div style="font-size:.82rem;">Use a aba <strong>"Enviar novo"</strong> e clique em salvar boletim.</div>`;
                    return;
                }

                boletimsLista.innerHTML = '';
                boletins.forEach(b => {
                    const card = document.createElement('div');
                    card.className = 'boletim-card';
                    card.innerHTML = `
                        <div style="font-size:1.8rem;flex-shrink:0;">📄</div>
                        <div class="boletim-card-info">
                            <div class="boletim-card-nome" title="${b.nome_original}">${b.nome_original}</div>
                            <div class="boletim-card-meta">
                                📅 ${b.criado_formatado}  ·  💾 ${b.tamanho_legivel}
                            </div>
                        </div>
                        <div class="boletim-card-acoes">
                            <button type="button" class="btn-usar-boletim" data-id="${b.id}"
                                style="background:#1b8a00;color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:.82rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                                🔍 Usar OCR
                            </button>
                            <button type="button" class="btn-excluir-boletim" data-id="${b.id}"
                                style="background:#fff0f0;border:1.5px solid #ffcdd2;color:#c62828;border-radius:8px;padding:7px 10px;font-size:.82rem;cursor:pointer;"
                                title="Excluir boletim">🗑️</button>
                        </div>`;
                    boletimsLista.appendChild(card);
                });

                boletimsLista.style.display = 'flex';

                boletimsLista.querySelectorAll('.btn-usar-boletim').forEach(btn => {
                    btn.addEventListener('click', () => usarBoletimSalvo(parseInt(btn.dataset.id), btn));
                });
                boletimsLista.querySelectorAll('.btn-excluir-boletim').forEach(btn => {
                    btn.addEventListener('click', () => excluirBoletim(parseInt(btn.dataset.id)));
                });

            } catch (err) {
                boletimsLoad.style.display  = 'none';
                boletimsVazio.style.display = 'block';
                boletimsVazio.innerHTML = '<div style="color:#c62828;">⚠️ Erro ao carregar boletins: ' + err.message + '</div>';
            }
        }

        async function usarBoletimSalvo(id, btnOrigem) {
            btnOrigem.textContent = '⏳ Carregando…';
            btnOrigem.disabled    = true;
            avisoSalvo.style.display     = 'none';
            resultadoSalvo.style.display = 'none';

            // Spinner inline
            const spinnerDiv = document.createElement('div');
            spinnerDiv.style.cssText = 'display:flex;align-items:center;gap:12px;background:#f0f7f0;border-left:4px solid #1b8a00;border-radius:10px;padding:13px 16px;margin-bottom:12px;font-size:.88rem;color:#3a5c38;';
            spinnerDiv.innerHTML = '<div style="width:20px;height:20px;border:3px solid #d4e4d0;border-top-color:#1b8a00;border-radius:50%;animation:spin .8s linear infinite;flex-shrink:0;"></div> Analisando com IA…';
            boletimsLista.style.display = 'none';
            resultadoSalvo.parentNode.insertBefore(spinnerDiv, resultadoSalvo);

            try {
                // 1. Busca base64 do arquivo no servidor
                const respGet = await fetch(`boletins_api.php?acao=get&id=${id}`);
                const jsonGet = await respGet.json();
                if (!jsonGet.ok || !jsonGet.base64) throw new Error(jsonGet.erro || 'Arquivo não encontrado.');

                // 2. Se for PDF, converte primeira página com PDF.js
                let imagemB64  = jsonGet.base64;
                let imagemTipo = 'image/jpeg';

                if (jsonGet.tipo === 'application/pdf') {
                    const buffer = Uint8Array.from(atob(jsonGet.base64), c => c.charCodeAt(0)).buffer;
                    const pdfDoc = await pdfjsLib.getDocument({ data: buffer }).promise;
                    const scale  = 2.0;
                    const canvases = [];
                    for (let p = 1; p <= pdfDoc.numPages; p++) {
                        const page     = await pdfDoc.getPage(p);
                        const viewport = page.getViewport({ scale });
                        const cv       = document.createElement('canvas');
                        cv.width  = viewport.width;
                        cv.height = viewport.height;
                        await page.render({ canvasContext: cv.getContext('2d'), viewport }).promise;
                        canvases.push(cv);
                    }
                    const totalWidth  = Math.max(...canvases.map(c => c.width));
                    const totalHeight = canvases.reduce((s, c) => s + c.height, 0);
                    const merged = document.createElement('canvas');
                    merged.width = totalWidth; merged.height = totalHeight;
                    const ctx = merged.getContext('2d');
                    ctx.fillStyle = '#fff'; ctx.fillRect(0,0,totalWidth,totalHeight);
                    let offsetY = 0;
                    for (const cv of canvases) { ctx.drawImage(cv,0,offsetY); offsetY += cv.height; }
                    imagemB64 = merged.toDataURL('image/jpeg', 0.92).split(',')[1];
                }

                // 3. OCR
                const respOCR = await fetch('ocr_proxy.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ imagem: imagemB64, tipo: imagemTipo })
                });
                const jsonOCR = await respOCR.json();
                spinnerDiv.remove();

                if (!respOCR.ok || jsonOCR.erro) throw new Error(jsonOCR.erro || 'Erro no OCR.');

                const fonteAnos  = (jsonOCR.anos && typeof jsonOCR.anos === 'object') ? jsonOCR.anos : jsonOCR;
                const anosNoJSON = ['ano6','ano7','ano8','ano9'].filter(k =>
                    fonteAnos[k] && typeof fonteAnos[k] === 'object' && Object.keys(fonteAnos[k]).length > 0
                );

                if (anosNoJSON.length === 0) throw new Error('Nenhuma disciplina reconhecida neste boletim.');

                notasSalvas = new Array(CAMPOS_ORDEM.length).fill(null);
                preencherNotasDeJSON(fonteAnos, anosNoJSON, notasSalvas);
                renderizarLista('ocr-lista-notas-salvo', 'ocr-resumo-salvo', notasSalvas);
                resultadoSalvo.style.display = 'block';

            } catch (err) {
                const sp = document.querySelector('#ocr-tab-salvos > div[style*="display:flex"]');
                if (sp && sp !== boletimsLista) sp.remove();
                spinnerDiv.remove && spinnerDiv.remove();
                avisoSalvo.textContent = '⚠️ ' + err.message;
                avisoSalvo.style.display = 'block';
                boletimsLista.style.display = 'flex';
                btnOrigem.textContent = '🔍 Usar OCR';
                btnOrigem.disabled    = false;
            }
        }

        async function excluirBoletim(id) {
            if (!confirm('Excluir este boletim permanentemente?')) return;
            try {
                const fd = new FormData();
                fd.append('acao', 'excluir');
                fd.append('id',   id);
                const resp = await fetch('boletins_api.php', { method: 'POST', body: fd });
                const json = await resp.json();
                if (json.ok) carregarBoletins();
                else alert('Erro ao excluir: ' + (json.erro || 'Tente novamente.'));
            } catch { alert('Erro de rede ao excluir.'); }
        }

        btnRefresh.addEventListener('click', carregarBoletins);

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

        </form>
        </div><!-- /form-column -->
      </div><!-- /cadastro-layout -->
    </main>

    <!-- ══════════════════════════════════════════════════════ -->
    <!--  BUSCA DE ALUNO — lógica completa                      -->
    <!-- ══════════════════════════════════════════════════════ -->
    <script>
    (function () {
        // Aluno atualmente selecionado no painel de busca. Usado para
        // filtrar a "Biblioteca de Boletins" e vincular novos uploads.
        window.alunoSelecionadoId   = null;
        window.alunoSelecionadoNome = null;

        const inputBusca    = document.getElementById('busca-nome-input');
        const resultadosDiv = document.getElementById('busca-resultados');
        const vinculadoDiv  = document.getElementById('busca-boletim-vinculado');
        const bvLista       = document.getElementById('bv-lista');
        const bvVazio       = document.getElementById('bv-vazio');

        let debounceTimer = null;

        const MATERIA_MAP = {
            'Português':        'portugues',
            'Matematica':       'matematica',
            'Matemática':       'matematica',
            'Ciências':         'ciencias',
            'Ciencias':         'ciencias',
            'História':         'historia',
            'Historia':         'historia',
            'Geografia':        'geografia',
            'Artes':            'artes',
            'Inglês':           'ingles',
            'Ingles':           'ingles',
            'Ed. Física':       'edfisica',
            'Educacao Fisica':  'edfisica',
            'Educação Física':  'edfisica',
            'Ens. Religioso':   'religiao',
            'Ensino Religioso': 'religiao',
        };

        inputBusca.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const q = inputBusca.value.trim();
            if (q.length < 2) { renderPlaceholder(); return; }
            debounceTimer = setTimeout(() => buscarAluno(q), 300);
        });

        async function buscarAluno(q) {
            resultadosDiv.innerHTML = `
                <div style="text-align:center;padding:20px;color:#888;font-size:.83rem;">
                    <div style="width:22px;height:22px;border:3px solid #ddd;border-top-color:#1b8a00;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 8px;"></div>
                    Buscando…
                </div>`;
            try {
                const resp = await fetch('buscar_aluno.php?acao=buscar&q=' + encodeURIComponent(q));
                const json = await resp.json();
                if (!json.ok || json.alunos.length === 0) {
                    resultadosDiv.innerHTML = `<div class="busca-placeholder"><div class="busca-icon-big">🔎</div>Nenhum aluno encontrado para "<strong>${q}</strong>"</div>`;
                    return;
                }
                renderResultados(json.alunos);
            } catch {
                resultadosDiv.innerHTML = `<div class="busca-placeholder" style="color:#c62828;">⚠️ Erro ao buscar.</div>`;
            }
        }

        function renderResultados(alunos) {
            resultadosDiv.innerHTML = '';
            alunos.forEach(a => {
                const item = document.createElement('div');
                item.className = 'busca-item';
                item.dataset.id = a.id;
                const media = a.media_geral ? parseFloat(a.media_geral).toFixed(2) : null;
                item.innerHTML = `
                    <div class="busca-item-nome">${a.nome_completo}</div>
                    <div class="busca-item-meta">${a.curso || '—'} · ${a.data_cadastro_fmt}</div>
                    ${media ? `<span class="busca-item-media">Média ${media}</span>` : ''}`;
                item.addEventListener('click', () => selecionarAluno(a.id, item));
                resultadosDiv.appendChild(item);
            });
        }

        async function selecionarAluno(id, itemEl) {
            document.querySelectorAll('.busca-item').forEach(i => i.classList.remove('selecionado'));
            itemEl.classList.add('selecionado');

            const spin = document.createElement('span');
            spin.style.cssText = 'font-size:.7rem;color:#1b8a00;margin-left:6px;';
            spin.textContent = '⏳ carregando…';
            itemEl.appendChild(spin);

            try {
                const resp = await fetch('buscar_aluno.php?acao=carregar&id=' + id);
                const json = await resp.json();
                spin.remove();
                if (!json.ok) throw new Error(json.erro || 'Erro');

                window.alunoSelecionadoId   = json.aluno.id;
                window.alunoSelecionadoNome = json.aluno.nome_completo;
                document.dispatchEvent(new CustomEvent('aluno-selecionado', {
                    detail: { id: json.aluno.id, nome: json.aluno.nome_completo }
                }));

                preencherFormulario(json.aluno, json.notas);
                mostrarBoletinsVinculados(json.boletins);
            } catch (err) {
                spin.remove();
                alert('Erro ao carregar aluno: ' + err.message);
            }
        }

        function preencherFormulario(aluno, notas) {
            const set = (name, val) => {
                if (val === null || val === undefined) return;
                const el = document.querySelector('[name="' + name + '"]');
                if (!el) return;
                if (el.tagName === 'SELECT') {
                    for (const opt of el.options) {
                        if (opt.value === val || opt.value.toLowerCase() === String(val).toLowerCase()) {
                            el.value = opt.value; break;
                        }
                    }
                } else {
                    el.value = val;
                }
            };

            set('nome_aluno',           aluno.nome_completo);
            set('sexo',                 aluno.sexo);
            set('bairro',               aluno.bairro);
            set('curso',                aluno.curso);
            set('procedencia',          aluno.procedencia_escolar);
            set('pcd',                  aluno.pcd);
            set('nome_responsavel',     aluno.nome_responsavel);
            set('email_responsavel',    aluno.email_responsavel);
            set('telefone_responsavel', aluno.telefone_responsavel);
            set('optou_cota_local',     aluno.cota_local);

            if (notas && notas.length > 0) {
                notas.forEach(n => {
                    const disc = MATERIA_MAP[n.materia] || n.materia;
                    [6,7,8,9].forEach(ano => {
                        const val   = n['nota_' + ano + '_ano'];
                        const input = document.querySelector('input[name="nota[' + disc + '][' + ano + ']"]');
                        if (input && val !== null && val !== undefined) {
                            input.value = parseFloat(val).toFixed(2);
                        }
                    });
                });
                atualizarMedia();
            }

            const pcdSel = document.getElementById('pcd');
            if (pcdSel) pcdSel.dispatchEvent(new Event('change'));

            document.querySelector('.student-form').scrollIntoView({ behavior: 'smooth', block: 'start' });

            const badge = document.createElement('div');
            badge.style.cssText = 'position:fixed;bottom:28px;right:28px;background:#e8f5e9;color:#2e7d32;border:1.5px solid #2e7d32;border-radius:12px;padding:12px 20px;font-size:.88rem;font-weight:700;box-shadow:0 4px 16px rgba(0,0,0,.12);z-index:9999;animation:ocrFadeIn .2s ease;';
            badge.textContent = '✅ Dados carregados com sucesso!';
            document.body.appendChild(badge);
            setTimeout(() => badge.remove(), 3500);
        }

        function mostrarBoletinsVinculados(boletins) {
            bvLista.innerHTML = '';
            bvVazio.style.display = 'none';
            vinculadoDiv.style.display = 'block';

            const lista = boletins && boletins.length > 0 ? boletins : [];
            if (lista.length === 0) {
                bvVazio.style.display = 'block';
                return;
            }
            lista.forEach(b => bvLista.appendChild(renderBVCard(b, true)));
        }

        function escaparHTML(texto) {
            const div = document.createElement('div');
            div.textContent = texto ?? '';
            return div.innerHTML;
        }

        function renderBVCard(b, vinculado) {
            const card = document.createElement('div');
            card.className = 'bv-card';
            card.innerHTML = `
                <span style="font-size:1.2rem;">📄</span>
                <div style="flex:1;min-width:0;">
                    <div class="bv-card-nome">${b.nome_original}</div>
                    <div class="bv-card-meta">${b.descricao ? b.descricao + ' · ' : ''}${b.criado_formatado} · ${b.tamanho_legivel}</div>
                </div>
                ${vinculado ? '<span style="font-size:.7rem;background:#e8f5e9;color:#1b8a00;border-radius:5px;padding:2px 6px;font-weight:700;flex-shrink:0;">vinculado</span>' : ''}`;
            return card;
        }

        function renderPlaceholder() {
            resultadosDiv.innerHTML = `<div class="busca-placeholder"><div class="busca-icon-big">🎓</div>Digite ao menos 2 letras para buscar</div>`;
            vinculadoDiv.style.display = 'none';
        }
    })();
    </script>

</body>
</html>