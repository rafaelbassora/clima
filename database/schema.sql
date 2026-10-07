-- Schema do banco de dados 'dados' - Sistema Clima
-- Gerado automaticamente a partir da instancia de producao (so estrutura, sem dados)

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Tabela: alertas
-- ----------------------------
DROP TABLE IF EXISTS `alertas`;
CREATE TABLE `alertas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('success','danger','warning','info') DEFAULT 'info',
  `mensagem` text NOT NULL,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Tabela: anos_consulta
-- ----------------------------
DROP TABLE IF EXISTS `anos_consulta`;
CREATE TABLE `anos_consulta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ano` int NOT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `status` enum('ativo','inativo','arquivado') COLLATE utf8mb4_unicode_ci DEFAULT 'inativo',
  `exigir_questionario` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ano` (`ano`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabela: chuva
-- ----------------------------
DROP TABLE IF EXISTS `chuva`;
CREATE TABLE `chuva` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `data` datetime DEFAULT NULL,
  `local` int DEFAULT NULL,
  `choveu` tinyint(1) DEFAULT NULL,
  `medida` decimal(10,2) DEFAULT NULL,
  `ph` decimal(10,2) DEFAULT NULL,
  `obs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `data` (`data`),
  KEY `medida` (`medida`),
  KEY `local` (`local`)
) ENGINE=InnoDB AUTO_INCREMENT=4711 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabela: chuva_cidada
-- ----------------------------
DROP TABLE IF EXISTS `chuva_cidada`;
CREATE TABLE `chuva_cidada` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cidadao_id` int NOT NULL,
  `data` date NOT NULL,
  `medida_mm` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `granizo` tinyint(1) NOT NULL DEFAULT '0',
  `chuva_mansa` tinyint(1) NOT NULL DEFAULT '0',
  `chuva_forte` tinyint(1) NOT NULL DEFAULT '0',
  `observacao` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cidadao` (`cidadao_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Tabela: chuva_correcoes
-- ----------------------------
DROP TABLE IF EXISTS `chuva_correcoes`;
CREATE TABLE `chuva_correcoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `local` varchar(10) NOT NULL,
  `data` date NOT NULL,
  `hora` time DEFAULT NULL,
  `valor_mm` decimal(10,2) NOT NULL,
  `choveu` tinyint(1) NOT NULL DEFAULT '1',
  `obs` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Tabela: chuva_old
-- ----------------------------
DROP TABLE IF EXISTS `chuva_old`;
CREATE TABLE `chuva_old` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data` datetime DEFAULT NULL,
  `local` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `choveu` tinyint(1) DEFAULT NULL,
  `medida` decimal(10,2) DEFAULT NULL,
  `ph` decimal(10,2) DEFAULT NULL,
  `obs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2343 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabela: cidadaos
-- ----------------------------
DROP TABLE IF EXISTS `cidadaos`;
CREATE TABLE `cidadaos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('pf','pj') NOT NULL,
  `nome` varchar(255) NOT NULL,
  `usa_nome_social` tinyint(1) NOT NULL DEFAULT '0',
  `nome_social` varchar(255) DEFAULT NULL,
  `sexo` varchar(20) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `cnpj` varchar(20) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `cep` varchar(9) NOT NULL,
  `bairro` varchar(150) NOT NULL,
  `cidade` varchar(150) NOT NULL,
  `uf` varchar(2) NOT NULL,
  `status` enum('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
  `station_code` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `approved_at` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `email_pendente` varchar(255) DEFAULT NULL,
  `email_token` varchar(64) DEFAULT NULL,
  `email_token_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Tabela: estacao_config
-- ----------------------------
DROP TABLE IF EXISTS `estacao_config`;
CREATE TABLE `estacao_config` (
  `id` int NOT NULL,
  `fonte_dados` enum('spoofing','wunderground') NOT NULL DEFAULT 'spoofing',
  `wunderground_api_key` varchar(100) DEFAULT NULL,
  `wunderground_station_id` varchar(50) DEFAULT NULL,
  `frequencia_minutos` int NOT NULL DEFAULT '5',
  `ultima_sincronizacao` datetime DEFAULT NULL,
  `ultimo_erro` varchar(255) DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Tabela: leituras_completas
-- ----------------------------
DROP TABLE IF EXISTS `leituras_completas`;
CREATE TABLE `leituras_completas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `local` varchar(2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `wid` varchar(50) DEFAULT NULL,
  `temp` smallint DEFAULT NULL,
  `tempin` smallint DEFAULT NULL,
  `dew` smallint DEFAULT NULL,
  `dewin` smallint DEFAULT NULL,
  `chill` smallint DEFAULT NULL,
  `heat` smallint DEFAULT NULL,
  `heatin` smallint DEFAULT NULL,
  `thw` smallint DEFAULT NULL,
  `hum` tinyint unsigned DEFAULT NULL,
  `humin` tinyint unsigned DEFAULT NULL,
  `bar` smallint unsigned DEFAULT NULL,
  `wspd` smallint unsigned DEFAULT NULL,
  `wspdhi` smallint unsigned DEFAULT NULL,
  `wdir` smallint unsigned DEFAULT NULL,
  `wspdavg` smallint unsigned DEFAULT NULL,
  `wdiravg` smallint unsigned DEFAULT NULL,
  `rain` mediumint unsigned DEFAULT NULL,
  `rainrate` smallint unsigned DEFAULT NULL,
  `solarrad` smallint unsigned DEFAULT NULL,
  `uvi` tinyint unsigned DEFAULT NULL,
  `battery` varchar(10) DEFAULT NULL,
  `humsoil` smallint DEFAULT NULL,
  `et` smallint DEFAULT NULL,
  `obs` text,
  `automatic` varchar(1) NOT NULL DEFAULT '1',
  `data_leitura` date GENERATED ALWAYS AS (cast(`created_at` as date)) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_wid_date` (`wid`,`created_at`),
  KEY `idx_local_date` (`local`,`created_at`),
  KEY `idx_data_local` (`local`,`data_leitura`)
) ENGINE=InnoDB AUTO_INCREMENT=1566264 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Tabela: local
-- ----------------------------
DROP TABLE IF EXISTS `local`;
CREATE TABLE `local` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nomeLocal` varchar(255) DEFAULT NULL,
  `nomeLocalDesc` varchar(255) DEFAULT NULL,
  `descLocal` varchar(255) DEFAULT NULL,
  `staLocal` enum('S','N') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'S',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Tabela: noticias
-- ----------------------------
DROP TABLE IF EXISTS `noticias`;
CREATE TABLE `noticias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resumo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conteudo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `imagem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_noticia` datetime DEFAULT NULL,
  `data_agendamento` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabela: nowcasting
-- ----------------------------
DROP TABLE IF EXISTS `nowcasting`;
CREATE TABLE `nowcasting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `local` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_previsao` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'firme',
  `acertou` tinyint(1) DEFAULT NULL,
  `chuva_registrada` float DEFAULT '0',
  `valido_ate` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `score` int DEFAULT NULL,
  `diferenca_orvalho` float DEFAULT NULL,
  `taxa_pressao` float DEFAULT NULL,
  `taxa_vento` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `local` (`local`,`valido_ate`),
  KEY `acertou` (`acertou`)
) ENGINE=InnoDB AUTO_INCREMENT=49471 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabela: nowcasting_config
-- ----------------------------
DROP TABLE IF EXISTS `nowcasting_config`;
CREATE TABLE `nowcasting_config` (
  `id` int NOT NULL,
  `limiar_alta` int NOT NULL DEFAULT '4',
  `limiar_moderada` int NOT NULL DEFAULT '2',
  `limiar_baixa` int NOT NULL DEFAULT '1',
  `amostras_usadas` int NOT NULL DEFAULT '0',
  `atualizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Tabela: opcoes_pergunta
-- ----------------------------
DROP TABLE IF EXISTS `opcoes_pergunta`;
CREATE TABLE `opcoes_pergunta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pergunta_id` int NOT NULL,
  `texto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordem` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pergunta_id` (`pergunta_id`),
  CONSTRAINT `opcoes_pergunta_ibfk_1` FOREIGN KEY (`pergunta_id`) REFERENCES `perguntas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabela: perguntas
-- ----------------------------
DROP TABLE IF EXISTS `perguntas`;
CREATE TABLE `perguntas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ano_consulta_id` int NOT NULL,
  `texto` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('unica_escolha','multipla_escolha','texto') COLLATE utf8mb4_unicode_ci DEFAULT 'unica_escolha',
  `ordem` int DEFAULT '0',
  `obrigatoria` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `ano_consulta_id` (`ano_consulta_id`),
  CONSTRAINT `perguntas_ibfk_1` FOREIGN KEY (`ano_consulta_id`) REFERENCES `anos_consulta` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabela: previsoes
-- ----------------------------
DROP TABLE IF EXISTS `previsoes`;
CREATE TABLE `previsoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cidade` varchar(50) DEFAULT NULL,
  `uf` varchar(2) DEFAULT NULL,
  `atualizacao` date DEFAULT NULL,
  `dia` date DEFAULT NULL,
  `tempo` varchar(255) DEFAULT NULL,
  `maxima` int DEFAULT NULL,
  `minima` int DEFAULT NULL,
  `iuv` float DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `local` int DEFAULT NULL,
  `staPrevisao` enum('S','N') DEFAULT NULL,
  `fonte` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `data` (`data`),
  KEY `local` (`local`)
) ENGINE=InnoDB AUTO_INCREMENT=388253 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Tabela: previsoesextendidas
-- ----------------------------
DROP TABLE IF EXISTS `previsoesextendidas`;
CREATE TABLE `previsoesextendidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cidade` varchar(50) DEFAULT NULL,
  `uf` varchar(2) DEFAULT NULL,
  `atualizacao` date DEFAULT NULL,
  `dia` date DEFAULT NULL,
  `tempo` varchar(255) DEFAULT NULL,
  `maxima` int DEFAULT NULL,
  `minima` int DEFAULT NULL,
  `iuv` float DEFAULT NULL,
  `data` datetime DEFAULT NULL,
  `local` int DEFAULT NULL,
  `atual` int DEFAULT NULL,
  `staPrevisao` enum('S','N') DEFAULT NULL,
  `fonte` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `data` (`data`),
  KEY `local` (`local`)
) ENGINE=InnoDB AUTO_INCREMENT=167769 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------
-- Tabela: temperatura
-- ----------------------------
DROP TABLE IF EXISTS `temperatura`;
CREATE TABLE `temperatura` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `data` datetime NOT NULL,
  `local` int DEFAULT NULL,
  `medida` decimal(10,2) DEFAULT NULL,
  `obs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `data` (`data`),
  KEY `medida` (`medida`)
) ENGINE=InnoDB AUTO_INCREMENT=715748 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabela: umidade
-- ----------------------------
DROP TABLE IF EXISTS `umidade`;
CREATE TABLE `umidade` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `data` datetime DEFAULT NULL,
  `local` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `medida` decimal(10,2) NOT NULL,
  `obs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `data` (`data`),
  KEY `medida` (`medida`)
) ENGINE=InnoDB AUTO_INCREMENT=715639 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabela: umidade_solo
-- ----------------------------
DROP TABLE IF EXISTS `umidade_solo`;
CREATE TABLE `umidade_solo` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `data` datetime DEFAULT NULL,
  `local` int DEFAULT NULL,
  `medida` decimal(10,2) DEFAULT NULL,
  `obs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_local_data` (`local`,`data` DESC)
) ENGINE=InnoDB AUTO_INCREMENT=2103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Tabela: usuarios
-- ----------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `totp_secret` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `totp_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `reset_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

