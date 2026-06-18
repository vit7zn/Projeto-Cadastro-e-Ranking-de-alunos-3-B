# Biblioteca de Boletins — Guia de Instalação

## O que foi adicionado
Agora dá pra salvar os boletins (PDF ou imagem) na hora que fizer o OCR pela primeira vez, e depois reaproveitar — sem precisar enviar o arquivo de novo a cada novo cadastro.

## Arquivos

| Arquivo | O que é |
|---|---|
| `cadastro.php` | Substitui o original. Modal de OCR agora tem 2 abas: "Enviar novo" e "Meus boletins salvos". |
| `boletins_api.php` | Novo. API que faz upload, lista, busca e exclui os boletins salvos. |
| `adicionar_boletins_salvos.sql` | Novo. Cria a tabela `boletins_salvos` no banco. |
| `uploads_boletins_htaccess` | Novo. Renomeie para `.htaccess` e coloque dentro de `uploads/boletins/`. |

## Passo a passo

1. **Banco de dados**: abra o phpMyAdmin, selecione `sistema_login`, aba SQL, e rode o conteúdo de `adicionar_boletins_salvos.sql`.
   - Se der erro na `ALTER TABLE` do final (foreign key), pode ignorar — é opcional, só garante que ao excluir um usuário os boletins dele somem também.

2. **Arquivos PHP**: coloque `boletins_api.php` na mesma pasta de `cadastro.php`, `ocr_proxy.php`, etc. Substitua o `cadastro.php` antigo pelo novo.

3. **Pasta de uploads**: o `boletins_api.php` cria a pasta `uploads/boletins/` automaticamente na primeira vez que alguém salvar um boletim. Mas por segurança, depois que ela existir, copie `uploads_boletins_htaccess` pra dentro dela e renomeie para `.htaccess`.

4. Teste: abra o cadastro, clique pra abrir o OCR, marque "Salvar este boletim para uso futuro", analise um boletim. Depois feche o modal, abra de novo e clique na aba "Meus boletins salvos" — o arquivo deve aparecer lá.

## Como funciona pro usuário

**Aba "Enviar novo"** (igual já era, só com a opção extra):
- Sobe o arquivo, opcionalmente marca a caixinha "Salvar este boletim para uso futuro" e escreve uma descrição (ex: nome do aluno).
- Ao clicar em "Analisar com IA", além de rodar o OCR, o sistema salva uma cópia do arquivo original no servidor.

**Aba "Meus boletins salvos"**:
- Lista todos os boletins já salvos, com nome, descrição, data e tamanho.
- Botão "🔍 Usar OCR" — pega aquele arquivo do servidor, roda o OCR de novo e mostra as notas pra confirmar e preencher.
- Botão "🗑️" — exclui o boletim da biblioteca (apaga o arquivo físico e o registro do banco).

## Limites configurados
- Máximo 10 MB por arquivo (mesmo limite que já existia).
- Máximo 30 boletins salvos por usuário (dá pra mudar a constante `MAX_BOLETINS_POR_USUARIO` no topo do `boletins_api.php`).
- Cada boletim salvo só aparece pra quem fez o upload (filtra por `usuario_id` da sessão).

## Observação importante
Isso reaproveita a mesma sessão (`$_SESSION['usuario_id']`) e a mesma conexão `mysqli` que o `dashboard.php` já usa — não precisa configurar nada de banco separado.
