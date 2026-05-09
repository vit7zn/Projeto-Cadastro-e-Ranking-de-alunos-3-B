-- ══════════════════════════════════════════════════════════════════
--  ATUALIZAÇÃO DO BANCO — CallMeBot
--  Execute no phpMyAdmin → aba SQL
-- ══════════════════════════════════════════════════════════════════

USE sistema_login;

-- Adiciona os campos de contato (se ainda não existirem)
ALTER TABLE `alunos`
    ADD COLUMN IF NOT EXISTS `nome_responsavel`     VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `email_responsavel`    VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `telefone_responsavel` VARCHAR(20)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `callmebot_apikey`     VARCHAR(20)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `whatsapp_enviado`     TINYINT(1)   DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `whatsapp_enviado_em`  DATETIME     DEFAULT NULL;

-- Tabela de log de envios (se ainda não existir)
CREATE TABLE IF NOT EXISTS `log_whatsapp` (
    `id`           INT(11)      NOT NULL AUTO_INCREMENT,
    `aluno_id`     INT(11)      NOT NULL,
    `telefone`     VARCHAR(20)  NOT NULL,
    `mensagem`     TEXT         NOT NULL,
    `status`       ENUM('enviado','erro','pendente') DEFAULT 'pendente',
    `resposta_api` TEXT         DEFAULT NULL,
    `enviado_em`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `aluno_id` (`aluno_id`),
    CONSTRAINT `fk_log_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
