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
