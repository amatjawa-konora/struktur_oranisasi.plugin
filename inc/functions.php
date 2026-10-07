<?php
/**
 * functions.php — Struktur Organisasi
 * Semua fungsi diberi awalan "so" agar tidak bentrok dengan plugin lain.
 */

defined('INDEX_AUTH') OR die('Direct access not allowed!');

if (!defined('SO_TABLE')) {
    define('SO_TABLE', 'struktur_organisasi');
    define('SO_SETTING_TABLE', 'struktur_organisasi_setting');
    define('SO_SCHEMA_VERSION', '1');
    define('SO_PLUGIN_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    define('SO_PLUGIN_URL', SWB . 'plugins/' . basename(dirname(__DIR__)) . '/');
    // Foto & logo disimpan di luar folder plugin agar tidak hilang saat plugin diperbarui
    define('SO_UPLOAD_DIR', SB . 'images' . DIRECTORY_SEPARATOR . 'struktur_organisasi' . DIRECTORY_SEPARATOR);
    define('SO_UPLOAD_URL', SWB . 'images/struktur_organisasi/');
    define('SO_MAX_UPLOAD', 2 * 1024 * 1024); // 2 MB
}

/* =========================================
 * ESCAPE HTML
 * ========================================= */
if (!function_exists('soE')) {
    function soE($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

/* =========================================
 * KONEKSI (mysqli milik SLiMS)
 * ========================================= */
if (!function_exists('soDb')) {
    function soDb()
    {
        global $dbs;
        if (!($dbs instanceof mysqli)) {
            throw new RuntimeException('Koneksi database SLiMS tidak tersedia.');
        }
        return $dbs;
    }
}

/* =========================================
 * QUERY AMAN (prepared statement)
 * ========================================= */
if (!function_exists('soQuery')) {
    function soQuery($sql, $types = '', array $params = [])
    {
        $db   = soDb();
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException($db->error, (int) $db->errno);
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            throw new RuntimeException($stmt->error, (int) $stmt->errno);
        }
        $res  = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $out  = ['rows' => $rows, 'insert_id' => $stmt->insert_id, 'affected' => $stmt->affected_rows];
        $stmt->close();
        return $out;
    }
}

/* =========================================
 * PENGATURAN (judul, logo)
 * ========================================= */
if (!function_exists('soDefaultSettings')) {
    function soDefaultSettings()
    {
        return [
            'title'      => 'STRUKTUR ORGANISASI',
            'subtitle'   => 'PERPUSTAKAAN POLTEKKES KEMENKES MANADO',
            'logo_left'  => '',
            'logo_right' => '',
            'accent'     => '#0ea5a4',
        ];
    }
}

if (!function_exists('soSettings')) {
    function soSettings()
    {
        $s = soDefaultSettings();
        try {
            foreach (soQuery('SELECT setting_key, setting_value FROM ' . SO_SETTING_TABLE)['rows'] as $r) {
                $s[$r['setting_key']] = (string) $r['setting_value'];
            }
        } catch (Throwable $e) {
            // tabel belum siap: pakai default
        }
        return $s;
    }
}

if (!function_exists('soSaveSetting')) {
    function soSaveSetting($key, $value)
    {
        soQuery(
            'INSERT INTO ' . SO_SETTING_TABLE . ' (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            'ss',
            [$key, (string) $value]
        );
    }
}

/* =========================================
 * DATA JABATAN
 * ========================================= */
if (!function_exists('soNodes')) {
    function soNodes($onlyActive = false)
    {
        $sql = 'SELECT * FROM ' . SO_TABLE . ($onlyActive ? ' WHERE is_active = 1' : '')
             . ' ORDER BY sort_order ASC, id ASC';
        return soQuery($sql)['rows'];
    }
}

if (!function_exists('soNode')) {
    function soNode($id)
    {
        $rows = soQuery('SELECT * FROM ' . SO_TABLE . ' WHERE id = ?', 'i', [(int) $id])['rows'];
        return $rows[0] ?? null;
    }
}

/**
 * Susun data datar menjadi pohon.
 * Node yang atasannya tidak ada / nonaktif dijadikan akar (tidak hilang).
 */
if (!function_exists('soBuildTree')) {
    function soBuildTree(array $nodes)
    {
        $byId = [];
        foreach ($nodes as $n) {
            $n['children'] = [];
            $n['side']     = [];
            $byId[(int) $n['id']] = $n;
        }
        $roots = [];
        foreach (array_keys($byId) as $id) {
            $pid = (int) ($byId[$id]['parent_id'] ?? 0);
            if ($pid && isset($byId[$pid]) && $pid !== $id) {
                $bucket = ($byId[$id]['node_type'] === 'side') ? 'side' : 'children';
                $byId[$pid][$bucket][] = $id;
            } else {
                $roots[] = $id;
            }
        }
        $build = function ($id, $depth = 0) use (&$build, &$byId) {
            $n = $byId[$id];
            if ($depth > 30) { // pengaman bila data atasan berputar
                $n['children'] = [];
                $n['side'] = [];
                return $n;
            }
            $n['children'] = array_map(function ($c) use ($build, $depth) { return $build($c, $depth + 1); }, $n['children']);
            $n['side']     = array_map(function ($c) use ($build, $depth) { return $build($c, $depth + 1); }, $n['side']);
            return $n;
        };
        return array_map(function ($r) use ($build) { return $build($r); }, $roots);
    }
}

/**
 * Buang jabatan yang disembunyikan; bawahannya naik ke atasan berikutnya.
 */
if (!function_exists('soPruneInactive')) {
    function soPruneInactive(array $list)
    {
        $out = [];
        foreach ($list as $n) {
            $n['children'] = soPruneInactive($n['children']);
            $n['side']     = soPruneInactive($n['side']);
            if ((int) $n['is_active'] === 1) {
                $out[] = $n;
            } else {
                foreach (array_merge($n['side'], $n['children']) as $lifted) {
                    $out[] = $lifted;
                }
            }
        }
        return $out;
    }
}

/** Daftar datar berurutan seperti pohon (untuk tabel admin & pilihan atasan) */
if (!function_exists('soFlatten')) {
    function soFlatten(array $tree, $depth = 0, array &$out = [])
    {
        foreach ($tree as $n) {
            $n['depth'] = $depth;
            $kids = array_merge($n['side'], $n['children']);
            unset($n['children'], $n['side']);
            $out[] = $n;
            soFlatten($kids, $depth + 1, $out);
        }
        return $out;
    }
}

/** id node + semua turunannya (agar atasan tidak bisa dipilih dari bawahannya sendiri) */
if (!function_exists('soDescendantIds')) {
    function soDescendantIds(array $nodes, $id)
    {
        $children = [];
        foreach ($nodes as $n) {
            $children[(int) $n['parent_id']][] = (int) $n['id'];
        }
        $ids = [(int) $id];
        for ($i = 0; $i < count($ids); $i++) {
            foreach ($children[$ids[$i]] ?? [] as $c) {
                if (!in_array($c, $ids, true)) {
                    $ids[] = $c;
                }
            }
        }
        return $ids;
    }
}

/* =========================================
 * GAMBAR
 * ========================================= */
if (!function_exists('soImageURL')) {
    function soImageURL($file, $fallback = 'default_avatar.png')
    {
        $file = basename((string) $file);
        if ($file !== '' && is_file(SO_UPLOAD_DIR . $file)) {
            return SO_UPLOAD_URL . rawurlencode($file) . '?v=' . filemtime(SO_UPLOAD_DIR . $file);
        }
        return $fallback ? SO_PLUGIN_URL . 'images/' . $fallback : '';
    }
}

/**
 * Simpan unggahan gambar dengan aman.
 * Return: nama file baru, '' bila tidak ada file, atau lempar exception bila tidak valid.
 */
if (!function_exists('soHandleUpload')) {
    function soHandleUpload($field, $prefix)
    {
        if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        $f = $_FILES[$field];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE  => 'ukuran file melebihi batas server (upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'ukuran file terlalu besar',
                UPLOAD_ERR_PARTIAL   => 'file hanya terunggah sebagian',
            ];
            throw new RuntimeException('Gagal mengunggah gambar: ' . ($map[$f['error']] ?? 'kode error ' . $f['error']));
        }
        if ($f['size'] > SO_MAX_UPLOAD) {
            throw new RuntimeException('Ukuran gambar maksimal 2 MB.');
        }
        $info = @getimagesize($f['tmp_name']);
        $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
        if (!$info || !isset($allowed[$info[2]])) {
            throw new RuntimeException('File harus berupa gambar JPG, PNG, WEBP, atau GIF.');
        }
        soEnsureUploadDir();
        $name = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $allowed[$info[2]];
        if (!@move_uploaded_file($f['tmp_name'], SO_UPLOAD_DIR . $name)) {
            if (!@rename($f['tmp_name'], SO_UPLOAD_DIR . $name)) {
                throw new RuntimeException('Gambar tidak dapat disimpan. Pastikan folder images/struktur_organisasi dapat ditulis.');
            }
        }
        @chmod(SO_UPLOAD_DIR . $name, 0644);
        return $name;
    }
}

if (!function_exists('soDeleteImage')) {
    function soDeleteImage($file)
    {
        $file = basename((string) $file);
        if ($file !== '' && is_file(SO_UPLOAD_DIR . $file)) {
            @unlink(SO_UPLOAD_DIR . $file);
        }
    }
}

if (!function_exists('soDeleteImageIfUnused')) {
    function soDeleteImageIfUnused($file)
    {
        $file = basename((string) $file);
        if ($file === '') {
            return;
        }
        $used = soQuery('SELECT COUNT(*) AS c FROM ' . SO_TABLE . ' WHERE photo = ?', 's', [$file])['rows'][0]['c'] ?? 0;
        if ((int) $used === 0) {
            soDeleteImage($file);
        }
    }
}

if (!function_exists('soEnsureUploadDir')) {
    function soEnsureUploadDir()
    {
        if (!is_dir(SO_UPLOAD_DIR)) {
            @mkdir(SO_UPLOAD_DIR, 0775, true);
        }
        // larang eksekusi skrip di folder unggahan
        $ht = SO_UPLOAD_DIR . '.htaccess';
        if (is_dir(SO_UPLOAD_DIR) && !is_file($ht)) {
            @file_put_contents($ht,
                "Options -Indexes\n"
                . "<FilesMatch \"\\.(php|phtml|php[0-9]|phar|pl|py|cgi|sh)$\">\n"
                . "  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n"
                . "  <IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n  </IfModule>\n"
                . "</FilesMatch>\n");
        }
        return is_dir(SO_UPLOAD_DIR) && is_writable(SO_UPLOAD_DIR);
    }
}

/* =========================================
 * RENDER BAGAN (dipakai OPAC & pratinjau admin)
 * ========================================= */
if (!function_exists('soRenderCard')) {
    function soRenderCard(array $n, $isLeader)
    {
        $photo = soImageURL($n['photo']);
        $html  = '<div class="so-card' . ($isLeader ? ' so-card-leader' : '') . '">';
        $html .= '<img class="so-photo" src="' . soE($photo) . '" alt="' . soE($n['name']) . '" loading="lazy">';
        $html .= '<div class="so-info">';
        $html .= '<div class="so-role">' . soE($n['role']) . '</div>';
        if (trim((string) $n['name']) !== '') {
            $html .= '<div class="so-name">' . soE($n['name']) . '</div>';
        }
        if (trim((string) $n['nip']) !== '') {
            $html .= '<div class="so-nip">NIP: ' . soE($n['nip']) . '</div>';
        }
        $html .= '</div></div>';
        return $html;
    }
}

if (!function_exists('soRenderNode')) {
    function soRenderNode(array $n, $siblings = 1, $isRoot = true)
    {
        // Kartu pimpinan (lebih besar) hanya untuk rantai pimpinan utama:
        // jabatan paling atas, atau satu-satunya bawahan yang juga punya bawahan.
        // Jabatan yang sejajar dengan jabatan lain tetap berukuran sama agar barisnya rata.
        $hasSub   = !empty($n['children']) || !empty($n['side']);
        $isLeader = $isRoot || ($siblings === 1 && $hasSub);
        $html  = '<li>';
        $html .= soRenderCard($n, $isLeader);

        // Staf samping (mis. Tata Usaha): cabang ke kanan dari garis vertikal
        if (!empty($n['side'])) {
            $html .= '<div class="so-side' . (empty($n['children']) ? ' so-side-last' : '') . '">';
            foreach ($n['side'] as $s) {
                $html .= '<div class="so-side-item">'
                       . '<span class="so-side-pad"></span>'
                       . '<span class="so-side-v"></span>'
                       . '<div class="so-side-right"><span class="so-side-link"></span>' . soRenderCard($s, false) . '</div>'
                       . '</div>';
            }
            $html .= '</div>';
        }

        if (!empty($n['children'])) {
            $html .= '<ul>';
            $count = count($n['children']);
            foreach ($n['children'] as $c) {
                $html .= soRenderNode($c, $count, false);
            }
            $html .= '</ul>';
        }
        $html .= '</li>';
        return $html;
    }
}

if (!function_exists('soRenderChart')) {
    function soRenderChart(array $settings, array $tree)
    {
        $accent = preg_match('/^#[0-9a-f]{6}$/i', $settings['accent'] ?? '') ? $settings['accent'] : '#0ea5a4';

        $html  = '<link rel="stylesheet" href="' . soE(SO_PLUGIN_URL . 'assets/org.css?v=' . (int) @filemtime(SO_PLUGIN_DIR . 'assets/org.css')) . '">';
        $html .= '<div class="so-wrapper" style="--so-accent:' . soE($accent) . ';">';
        $html .= '<div class="so-container">';

        $html .= '<div class="so-header">';
        $logos = '';
        foreach (['logo_left', 'logo_right'] as $k) {
            $url = soImageURL($settings[$k] ?? '', '');
            if ($url !== '') {
                $logos .= '<img class="so-logo" src="' . soE($url) . '" alt="Logo">';
            }
        }
        if ($logos !== '') {
            $html .= '<div class="so-logos">' . $logos . '</div>';
        }
        $html .= '<h1 class="so-title">' . soE($settings['title'] ?? '');
        if (trim((string) ($settings['subtitle'] ?? '')) !== '') {
            $html .= '<span>' . soE($settings['subtitle']) . '</span>';
        }
        $html .= '</h1></div>';

        if (!$tree) {
            $html .= '<p class="so-empty">Struktur organisasi belum diisi.</p>';
        } else {
            $html .= '<div class="so-scroll"><div class="so-tree"><ul>';
            foreach ($tree as $root) {
                $html .= soRenderNode($root, count($tree), true);
            }
            $html .= '</ul></div></div>';
        }

        $html .= '</div></div>';
        return $html;
    }
}
