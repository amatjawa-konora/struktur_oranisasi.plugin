<?php
/**
 * install.php — Struktur Organisasi
 *
 *  - Hanya dijalankan dari halaman Struktur Organisasi (OPAC & admin),
 *    tidak di setiap request SLiMS.
 *  - Setelah sukses ditandai file images/struktur_organisasi/.schema_version,
 *    sehingga pemanggilan berikutnya tidak menjalankan query apa pun.
 *  - Tidak pernah mematikan SLiMS: error dicatat dan dikembalikan sebagai pesan.
 *  - Pada pemasangan pertama, struktur & foto lama diisikan otomatis.
 */

defined('INDEX_AUTH') OR die('Direct access not allowed!');

require_once __DIR__ . '/functions.php';

if (!function_exists('soInstall')) {
    function soInstall()
    {
        $marker = SO_UPLOAD_DIR . '.schema_version';
        if (is_file($marker) && trim((string) @file_get_contents($marker)) === SO_SCHEMA_VERSION) {
            return [];
        }

        $errors = [];
        $db = null;
        $locked = false;

        try {
            $db = soDb();

            $lock = $db->query("SELECT GET_LOCK('struktur_organisasi_install', 5) AS l");
            $locked = $lock && ((int) ($lock->fetch_assoc()['l'] ?? 0)) === 1;
            if (!$locked) {
                return [];
            }

            /* tabel yang sudah ada */
            $existing = [];
            $res = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES
                               WHERE TABLE_SCHEMA = DATABASE()
                                 AND TABLE_NAME IN ('" . SO_TABLE . "', '" . SO_SETTING_TABLE . "')");
            while ($res && ($r = $res->fetch_assoc())) {
                $existing[strtolower($r['TABLE_NAME'])] = true;
            }

            $freshTable = !isset($existing[SO_TABLE]);

            if (!isset($existing[SO_TABLE])) {
                $db->query("
                    CREATE TABLE IF NOT EXISTS `" . SO_TABLE . "` (
                        `id` INT NOT NULL AUTO_INCREMENT,
                        `parent_id` INT NULL DEFAULT NULL,
                        `node_type` ENUM('child','side') NOT NULL DEFAULT 'child',
                        `role` VARCHAR(150) NOT NULL,
                        `name` VARCHAR(150) NULL,
                        `nip` VARCHAR(50) NULL,
                        `photo` VARCHAR(255) NULL,
                        `sort_order` INT NOT NULL DEFAULT 0,
                        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NULL,
                        PRIMARY KEY (`id`),
                        KEY `idx_parent` (`parent_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ") or $errors[] = 'Gagal membuat tabel ' . SO_TABLE . ': ' . $db->error;
            }

            if (!isset($existing[SO_SETTING_TABLE])) {
                $db->query("
                    CREATE TABLE IF NOT EXISTS `" . SO_SETTING_TABLE . "` (
                        `setting_key` VARCHAR(50) NOT NULL,
                        `setting_value` TEXT NULL,
                        PRIMARY KEY (`setting_key`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ") or $errors[] = 'Gagal membuat tabel ' . SO_SETTING_TABLE . ': ' . $db->error;
            }

            if (!soEnsureUploadDir()) {
                $errors[] = 'Folder ' . SO_UPLOAD_DIR . ' tidak dapat dibuat / ditulis. Unggah foto tidak akan berfungsi.';
            }

            /* isi awal dari struktur lama (hanya saat tabel baru & kosong) */
            if (!$errors && $freshTable) {
                $count = soQuery('SELECT COUNT(*) AS c FROM ' . SO_TABLE)['rows'][0]['c'] ?? 0;
                if ((int) $count === 0) {
                    soSeed();
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'Instalasi Struktur Organisasi gagal: ' . $e->getMessage();
        } finally {
            if ($locked && $db) {
                try { $db->query("SELECT RELEASE_LOCK('struktur_organisasi_install')"); } catch (Throwable $e) {}
            }
        }

        if ($errors) {
            foreach ($errors as $err) {
                error_log('[STRUKTUR ORGANISASI] ' . $err);
            }
            return $errors;
        }

        @file_put_contents($marker, SO_SCHEMA_VERSION);
        return [];
    }
}

/* =========================================
 * DATA AWAL (sesuai struktur sebelumnya)
 * ========================================= */
if (!function_exists('soSeed')) {
    function soSeed()
    {
        $copy = function ($file) {
            $src = SO_PLUGIN_DIR . 'images' . DIRECTORY_SEPARATOR . $file;
            if (!is_file($src) || !is_dir(SO_UPLOAD_DIR)) {
                return '';
            }
            $dest = 'awal_' . $file;
            if (!is_file(SO_UPLOAD_DIR . $dest)) {
                @copy($src, SO_UPLOAD_DIR . $dest);
            }
            return is_file(SO_UPLOAD_DIR . $dest) ? $dest : '';
        };

        soSaveSetting('title', 'STRUKTUR ORGANISASI');
        soSaveSetting('subtitle', 'PERPUSTAKAAN SLiMS');
        soSaveSetting('logo_left', $copy('logo.png'));
        soSaveSetting('logo_right', $copy('blu.png'));
        soSaveSetting('accent', '#0ea5a4');

        $insert = function ($parent, $type, $role, $name, $nip, $photo, $order) use ($copy) {
            return soQuery(
                'INSERT INTO ' . SO_TABLE . ' (parent_id, node_type, role, name, nip, photo, sort_order, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                'isssssi',
                [$parent, $type, $role, $name, $nip, $copy($photo), $order]
            )['insert_id'];
        };

        $direktur = $insert(null, 'child', 'DIREKTUR', 'Direktur', 'xxxxxxxxxxxx', '', 1);
        $wadir    = $insert($direktur, 'child', 'WAKIL DIREKTUR III', 'Wakil Direktur III', 'xxxxxxxxxxxx', '', 1);
        $kepala   = $insert($wadir, 'child', 'KEPALA UNIT PERPUSTAKAAN', 'KEPALA', 'xxxxxxxxxxxx', '', 1);
        $insert($kepala, 'side', 'TATA USAHA', 'Tata Usaha', '', '', 1);
        $insert($kepala, 'child', 'BIDANG SIRKULASI', 'Sirkulasi', '', '', 1);
        $insert($kepala, 'child', 'BIDANG PELAYANAN', 'Layanan', '', '', 2);
        $teknis   = $insert($kepala, 'child', 'TENAGA TEKNIS', 'Teknis', '', '', 3);
        $insert($teknis, 'child', 'TEKNIS IT', 'Tenaga IT', '', '', 1);
    }
}
