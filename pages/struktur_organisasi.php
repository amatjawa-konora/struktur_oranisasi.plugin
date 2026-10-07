<?php
/**
 * Halaman OPAC: index.php?p=struktur_organisasi
 * Isi bagan diatur dari Admin → Keanggotaan → Struktur Organisasi.
 */

defined('INDEX_AUTH') OR die('Direct access not allowed!');

require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/install.php';

$page_title = 'Struktur Organisasi';

$soErrors = soInstall();

try {
    $soSettings = soSettings();
    $soTree     = soPruneInactive(soBuildTree(soNodes()));
    echo soRenderChart($soSettings, $soTree);
} catch (Throwable $e) {
    error_log('[STRUKTUR ORGANISASI] ' . $e->getMessage());
    echo '<div class="alert alert-warning" style="max-width:900px;margin:30px auto;">'
       . 'Struktur organisasi sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.</div>';
}
