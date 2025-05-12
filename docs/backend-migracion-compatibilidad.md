# Guía de migración: Backend oficina-600 (Compatibilidad total)

## 1. Cambios obligatorios de nombres

| Nombre viejo   | Nuevo nombre     | Notas                      |
| -------------- | --------------- | -------------------------- |
| id_turno       | numero_turno    | En la tabla turnos         |
| fecha_creacion | creado_en       | Tabla turnos               |
| fecha_finalizado| finalizado_en  | Tabla turnos               |

Recuerda: el identificador principal real de la tabla es `id` (int).

---

## 2. Ejemplo de SELECT corregido

```php
// Ejemplo viejo:
SELECT t.id_turno, t.estado, t.id_modulo FROM turnos t WHERE ...

// Ejemplo nuevo:
SELECT t.numero_turno AS id_turno, t.estado, t.id_modulo FROM turnos t WHERE ...
// (O: ... AS numero_turno ...)
```

## 3. Ejemplo de INSERT

```php
INSERT INTO turnos (numero_turno, tipo_atencion, id_modulo, estado, creado_en, fecha_actualizacion, llamado)
VALUES (?, ?, ?, 'pendiente', NOW(), NOW(), 0);
```

## 4. Ejemplo de UPDATE

```php
UPDATE turnos
SET estado = ?, fecha_actualizacion = NOW()
WHERE id = ?;
```

## 5. Ejemplo de respuesta JSON al frontend

```php
echo json_encode([
  'success' => true,
  'turno' => [
    'id' => $turno['id'],
    'numero_turno' => $turno['numero_turno'],
    'tipo_atencion' => $turno['tipo_atencion'],
    'modulo' => $turno['modulo_nombre'],
    'estado' => $turno['estado'],
    'creado_en' => $turno['creado_en']
  ]
]);
```

---

## 6. Zonas críticas a corregir (según tus scripts)

- `obtener_turnos.php`: Cambia todos los SELECT y arrays de salida a los nombres correctos (`numero_turno`)
- `turnos_actuales.php` y `llamar_turno.php`: Lo mismo
- `llamar_siguiente_turno.php`, `llamar_siguiente.php`: Ajusta nombres en resultsets y updates

---

## 7. Pruebas después de migrar

- Solicita, llama, finaliza un turno desde el frontend Totem y/o panel operador. Debe fluir todo sin errores de campo.
- Si algún mensaje dice "columna desconocida", revisa el nombre usado en ese script/backend.

---

**TIP:**  
Puedes buscar y reemplazar en tu IDE todas las apariciones de "id_turno" (NO reemplazar los casos de PK `id`), y decidir caso a caso si debes cambiar a `numero_turno` y/o ajustar el nombre en el JSON de respuesta.

---

¿Dudas puntuales sobre algún archivo?  
¿Necesitas un ejemplo de script backend entero ya corregido?  
¡Solo dime el archivo y te genero el fragmento corregido listo para pegar!