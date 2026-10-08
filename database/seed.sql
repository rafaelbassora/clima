-- Dados iniciais minimos para o sistema funcionar logo apos importar o schema.sql.
-- Sem essas linhas, as telas de admin /admin/estacao e a recalibracao do nowcasting
-- fazem UPDATE em uma linha que nao existe e nao salvam nada (silenciosamente).
--
-- Rode depois de importar o schema.sql:
--   mysql -u seu_usuario -p seu_banco < database/seed.sql

-- Fonte de dados da estacao (comeca em "spoofing" - troque pra "wunderground" em /admin/estacao se preferir)
INSERT INTO `estacao_config` (`id`, `fonte_dados`, `frequencia_minutos`)
VALUES (1, 'spoofing', 5);

-- Limiares iniciais do nowcasting (se autoajustam com o tempo, ver /admin/nowcasting)
INSERT INTO `nowcasting_config` (`id`, `limiar_alta`, `limiar_moderada`, `limiar_baixa`, `amostras_usadas`)
VALUES (1, 4, 2, 1, 0);
