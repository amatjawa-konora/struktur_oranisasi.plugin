<?php
/**
 * Plugin Name: Struktur Organisasi
 * Plugin URI: -
 * Description: Menampilkan struktur organisasi perpustakaan
 * Version: 1.0.0
 * Author: Rahmatullah Ade
 * Author URI: https://www.instagram.com/r.jawakonora/
 */
use SLiMS\Plugins;
$plugins = Plugins::getInstance();

$plugins->registerMenu('opac', 'struktur_organisasi', __DIR__ . '/pages/struktur_organisasi.php');
