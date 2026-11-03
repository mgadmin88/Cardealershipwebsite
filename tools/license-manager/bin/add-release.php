<?php
declare(strict_types=1);
/**
 * Registers the core-file fingerprints of an official release, so the manager knows which files are genuine.
 *   php tools/license-manager/bin/add-release.php <site-folder> "نسخه ۶"
 * Run it after building every update/zip you send to customers.
 */
require __DIR__ . '/../lib.php';

[$_, $dir, $label] = array_pad($argv, 3, '');
if ($dir === '' || $label === '') { fwrite(STDERR, "usage: add-release.php <site-folder> <label>\n"); exit(1); }
$dir = rtrim(realpath($dir) ?: $dir, '/');
if (!is_file($dir . '/app/lib/License.php')) { fwrite(STDERR, "site folder not found: $dir\n"); exit(1); }

$fp = lm_site_fingerprints($dir);
$added = 0;
foreach ($fp as $file => $hash) {
    if ($hash === 'missing') { fwrite(STDERR, "missing core file: $file\n"); exit(1); }
    lm_run('INSERT OR IGNORE INTO releases (label, file, hash, added) VALUES (?,?,?,?)', [$label, $file, $hash, time()]);
    $added += lm_db()->query('SELECT changes()')->fetchColumn() ? 1 : 0;
}
echo "registered \"$label\": $added new fingerprints (" . count($fp) . " core files)\n";
