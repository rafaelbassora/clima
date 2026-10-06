CREATE TABLE `umidade_solo` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `data` datetime DEFAULT NULL,
  `local` int DEFAULT NULL,
  `medida` decimal(10,2) DEFAULT NULL,
  `obs` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_local_data` (`local`, `data` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
