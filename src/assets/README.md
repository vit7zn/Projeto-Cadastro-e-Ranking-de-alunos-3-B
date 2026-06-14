# 🏫 Projeto Cadastro e Ranking de Alunos - 3º B

Este é um sistema desenvolvido para gerenciar o cadastro de alunos, processar dados de desempenho e gerar um ranking escolar de forma automatizada e visual. O projeto combina interface web moderna com scripts de backend para leitura, diagnóstico e organização dos dados dos estudantes.

## 🚀 Funcionalidades

- **Cadastro de Alunos:** Interface amigável para inserir novos estudantes e suas respectivas notas/informações.
- **Leitura Inteligente (OCR):** Integração com inteligência artificial para processamento e diagnóstico de dados textuais ou documentos de alunos.
- **Ranking Automatizado:** Geração de relatórios e classificação dos alunos com base nos critérios estabelecidos.
- **Banco de Dados Estruturado:** Persistência de dados segura utilizando SQL para controle de acessos e informações do sistema.

## 📂 Estrutura do Projeto

Abaixo está a organização das principais pastas e arquivos do repositório:

├── src/
│   ├── assets/               # Scripts, estilos e arquivos core do sistema
│   │   ├── cadastro.html     # Tela de formulário para novos registros
│   │   ├── ocr_proxy.php     # Integração backend com API de IA (ex: OpenAI)
│   │   ├── ocr_diagnostico.jsx # Componente de interface para exibição de diagnósticos
│   │   ├── gerar_pdf_ranking.php # Script para exportação do ranking em PDF
│   │   └── style.css         # Estilização visual do projeto
│   └── sistema_login.sql     # Estrutura do banco de dados (tabelas e usuários)
└── README.md                 # Documentação do projeto

## 🛠️ Tecnologias Utilizadas

- **Frontend:** HTML5, CSS3, JavaScript / React (\`.jsx\`)
- **Backend:** PHP
- **Banco de Dados:** MySQL / MariaDB (\`.sql\`)
- **Integrações:** API da OpenAI (para a lógica de OCR e diagnóstico)

## 🔧 Como Executar o Projeto Localmente

### Pré-requisitos
Para rodar o ambiente de desenvolvimento, você precisará de:
1. Um servidor local Apache com PHP e MySQL instalado (Recomendado: **XAMPP** ou **WampServer**).
2. Um navegador web atualizado.

### Passo a Passo

1. **Clonar o Repositório:**
   Coloque os arquivos do projeto dentro da pasta pública do seu servidor local (ex: \`C:/xampp/htdocs/projeto_cadastro\`).
   \`\`\`bash
   git clone https://github.com/vit7zn/Projeto-Cadastro-e-Ranking-de-alunos-3-B.git
   \`\`\`

2. **Configurar o Banco de Dados:**
   - Abra o \`phpMyAdmin\` (geralmente em \`http://localhost/phpmyadmin\`).
   - Crie um novo banco de dados.
   - Importe o arquivo \`src/sistema_login.sql\` para criar as tabelas necessárias.

3. **Configurar as Chaves de API:**
   - Caso utilize as funções de OCR, configure suas credenciais de ambiente nos arquivos proxy PHP correspondentes.

4. **Acessar o Sistema:**
   - Inicie os módulos Apache e MySQL no painel do XAMPP.
   - Abra o navegador e acesse: \`http://localhost/projeto_cadastro/src/assets/cadastro.html\`.

---
Feito por [vit7zn](https://github.com/vit7zn). 😊
