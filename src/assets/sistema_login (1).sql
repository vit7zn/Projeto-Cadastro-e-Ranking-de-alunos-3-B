-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/04/2026 às 01:22
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistema_login`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos`
--

CREATE TABLE `alunos` (
  `id` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `sexo` varchar(20) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `curso` varchar(100) DEFAULT NULL,
  `nome_responsavel` varchar(255) DEFAULT NULL,
  `procedencia_escolar` enum('publica','privada') NOT NULL,
  `cota_local` enum('sim','nao') DEFAULT 'nao',
  `pcd` enum('sim','nao') DEFAULT 'nao',
  `categoria_ranking` varchar(100) DEFAULT NULL,
  `media_geral` decimal(4,2) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notas_detalhadas`
--

CREATE TABLE `notas_detalhadas` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) DEFAULT NULL,
  `materia` varchar(50) DEFAULT NULL,
  `nota_6_ano` decimal(4,2) DEFAULT 0.00,
  `nota_7_ano` decimal(4,2) DEFAULT 0.00,
  `nota_8_ano` decimal(4,2) DEFAULT 0.00,
  `nota_9_ano` decimal(4,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`, `senha`, `nome`) VALUES
(12345, 'victoremasoares123@gmail.com', '123456', 'victor'),
(12346, 'victor.lima141@aluno.ce.gov.br', '$2y$10$SzEVgubFe2ksCpVy0j.2B.S/D1MD9bffP3GEPDHKgxi8zHgbDN8oS', 'VICTOR EMANUEL SOARES CAVALCANTE LIMA'),
(12347, 'carlos.oliveira541@aluno.ce.gov.br', '$2y$10$ZwWMdbwHEqjUzSFAjTMu4ujvPyVh6gWHFhT1iwpNgcoiMgokzZ3Xa', 'Carlos Andre Cordeiro Oliveira'),
(12348, 'victor.lima141@aluno.ce.gov.br', '$2y$10$fkhaFTcnh27YM8g8QDkB7.wcf61zbIqOH3pORJxP8p7mzS4DlENOW', 'VICTOR EMANUEL SOARES CAVALCANTE LIMA'),
(12349, 'aaa@gmail.com', '$2y$10$NM5cp/SmPZhXRtjEvq1uJ.sZWL8.ubhkdN9w1NLo6OoKIS2lgwBz.', 'Carlos Andre Cordeiro Oliveira');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alunos`
--
ALTER TABLE `alunos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `notas_detalhadas`
--
ALTER TABLE `notas_detalhadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alunos`
--
ALTER TABLE `alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notas_detalhadas`
--
ALTER TABLE `notas_detalhadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12350;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `notas_detalhadas`
--
ALTER TABLE `notas_detalhadas`
  ADD CONSTRAINT `notas_detalhadas_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
