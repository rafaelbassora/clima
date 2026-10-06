-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 14/04/2026 às 11:57
-- Versão do servidor: 8.3.0
-- Versão do PHP: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `dados`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `leituras_completas`
--

CREATE TABLE `leituras_completas` (
  `id` int UNSIGNED NOT NULL,
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
  `hum` tinyint UNSIGNED DEFAULT NULL,
  `humin` tinyint UNSIGNED DEFAULT NULL,
  `bar` smallint UNSIGNED DEFAULT NULL,
  `wspd` smallint UNSIGNED DEFAULT NULL,
  `wspdhi` smallint UNSIGNED DEFAULT NULL,
  `wdir` smallint UNSIGNED DEFAULT NULL,
  `wspdavg` smallint UNSIGNED DEFAULT NULL,
  `wdiravg` smallint UNSIGNED DEFAULT NULL,
  `rain` mediumint UNSIGNED DEFAULT NULL,
  `rainrate` smallint UNSIGNED DEFAULT NULL,
  `solarrad` smallint UNSIGNED DEFAULT NULL,
  `uvi` tinyint UNSIGNED DEFAULT NULL,
  `battery` varchar(10) DEFAULT NULL,
  `humsoil` smallint DEFAULT NULL,
  `obs` text,
  `automatic` varchar(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `leituras_completas`
--
ALTER TABLE `leituras_completas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wid_date` (`wid`,`created_at`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `leituras_completas`
--
ALTER TABLE `leituras_completas`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
