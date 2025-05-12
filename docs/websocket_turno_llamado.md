### WebSocket: Payload estándar para 'turno_llamado'

```json
{
  "type": "turno_llamado",
  "numero_turno": "[valor]",
  "tipo_atencion": "[valor]",
  "modulo": "[nombre del módulo]",
  "id_turno": [opcional],
  "id_modulo": [opcional]
}
```

- Lo importante es que los nombres sean iguales a los que el frontend espera (`numero_turno`, `tipo_atencion`, `modulo`).
- Puedes agregar más campos si los necesitas, pero estos 3 son mínimos.