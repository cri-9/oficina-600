<?php
/**
 * Script de limpieza de headers CORS duplicados en archivos PHP
 * ---------------------------------------------------------------------
 * Elimina líneas que contienen 'header('Access-Control-Allow-' en todos 
 * los archivos .php a partir del path actual o el que asignes a $baseDir.
 * ¡Haz siempre un respaldo antes de usarlo en producción!
 * 
 * USO (en consola, en la raíz del proyecto):
 *   php limpia_headers_cors.php
 */

$baseDir = __DIR__ . '/backend'; // Cambia esta línea por la carpeta que quieras procesar

$deletedLines = 0;
$filesProcessed = 0;

// Recorrer todos los archivos .php de forma recursiva
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
foreach ($rii as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') continue;

    $content = file($file->getPathname());
    $original = $content;
    $changed = false;

    // Eliminar líneas con Access-Control-Allow-
    $content = array_filter($content, function ($line) use (&$changed) {
        if (strpos($line, 'header(\'Access-Control-Allow-') !== false ||
            strpos($line, 'header("Access-Control-Allow-') !== false) {
            $changed = true;
            return false;
        }
        return true;
    });

    if ($changed) {
        file_put_contents($file->getPathname(), implode("", $content));
        $deletedLines += count($original) - count($content);
        $filesProcessed++;
        echo "✔ Limpiado: {$file->getPathname()}\n";
    }
}
echo "Proceso terminado. Archivos modificados: $filesProcessed, líneas eliminadas: $deletedLines\n";
?>