-- Actualiza la columna id_perfil en la tabla modulos
UPDATE modulos SET id_perfil = 1 WHERE id IN (1, 2, 3, 4);
UPDATE modulos SET id_perfil = 2 WHERE id IN (5, 6, 7);