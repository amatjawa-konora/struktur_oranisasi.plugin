<?php
/**
 * Plugin Name: Struktur Organisasi
 * Plugin URI: -
 * Description: Menampilkan struktur organisasi perpustakaan. Data, foto & logo diatur dari Admin → Keanggotaan → Struktur Organisasi.
 * Version: 2.0.0
 * Author: Rahmatullah Ade
 * Author URI: https://github.com/amatjawa-konora
 */

use SLiMS\Plugins;

defined('INDEX_AUTH') OR die('Direct access not allowed!');

$plugins = Plugins::getInstance();

/* Halaman publik (OPAC): index.php?p=struktur_organisasi */
$plugins->registerMenu('opac', 'struktur_organisasi', __DIR__ . '/pages/struktur_organisasi.php');

/* Panel pengaturan di admin: Keanggotaan → Struktur Organisasi */
$plugins->registerMenu('membership', 'Struktur Organisasi', __DIR__ . '/admin/index.php');
