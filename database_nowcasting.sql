CREATE TABLE IF NOT EXISTS `nowcasting` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `local` VARCHAR(10) NOT NULL,
    `mensagem` VARCHAR(500) NOT NULL,
    `tipo_previsao` VARCHAR(20) DEFAULT 'firme',
    `acertou` TINYINT(1) DEFAULT NULL,
    `chuva_registrada` FLOAT DEFAULT 0,
    `valido_ate` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`local`, `valido_ate`),
    INDEX (`acertou`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
