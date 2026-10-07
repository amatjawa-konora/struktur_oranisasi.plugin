<?php
/**
 * Admin → Keanggotaan → Struktur Organisasi
 *
 * Dimuat SLiMS lewat admin/plugin_container.php ke dalam #mainContent.
 *  - Navigasi (GET)  : $('#mainContent').simbioAJAX(url)
 *  - Simpan (POST)   : form target="blindSubmit" (iframe tersembunyi SLiMS,
 *                      sama seperti modul Keanggotaan bawaan) — mendukung unggah foto.
 *
 * Parameter memakai awalan "so_" karena "id" & "mod" dipakai SLiMS.
 */

defined('INDEX_AUTH') OR die('Direct access not allowed!');

require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/install.php';

/* =========================================
 * SESI & HAK AKSES ADMIN
 * ========================================= */
if (session_status() !== PHP_SESSION_ACTIVE) {
    $soSessionFile = SB . 'admin' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'session.inc.php';
    if (is_file($soSessionFile)) {
        require_once $soSessionFile;
    }
}
if (empty($_SESSION['uid'])) {
    echo '<div class="alert alert-danger" style="margin:30px;">Silakan login sebagai admin untuk mengatur struktur organisasi.</div>';
    return;
}
if (empty($_SESSION['so_csrf'])) {
    $_SESSION['so_csrf'] = bin2hex(random_bytes(16));
}
$soCsrf = $_SESSION['so_csrf'];

/* =========================================
 * URL DASAR (plugin_container.php?mod=...&id=...)
 * ========================================= */
$soBase = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_filter([
    'mod' => $_GET['mod'] ?? '',
    'id'  => $_GET['id'] ?? '',
], 'strlen'));

if (!function_exists('soUrl')) {
    function soUrl($base, array $params = [])
    {
        $params = array_filter($params, function ($v) { return $v !== '' && $v !== null; });
        return $params ? $base . (strpos($base, '?') === false ? '?' : '&') . http_build_query($params) : $base;
    }
}

$soInstallErrors = soInstall();

/* =========================================================
 * PROSES SIMPAN (POST dari iframe blindSubmit)
 * ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['so_action'])) {

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $done = function ($params) use ($soBase) {
        $url = soUrl($soBase, $params);
        echo '<!DOCTYPE html><html><body><script>'
           . 'var u=' . json_encode($url) . ';'
           . 'if (window.parent && window.parent !== window && window.parent.jQuery && window.parent.jQuery.fn.simbioAJAX) {'
           . '  window.parent.jQuery("#mainContent").simbioAJAX(u);'
           . '} else { window.location.href = u; }'
           . '</script></body></html>';
        exit;
    };

    if (!hash_equals($soCsrf, (string) ($_POST['so_csrf'] ?? ''))) {
        $done(['so_err' => 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.']);
    }

    $action = (string) $_POST['so_action'];

    try {
        switch ($action) {

            /* ---------- PENGATURAN JUDUL & LOGO ---------- */
            case 'save_setting':
                $settings = soSettings();
                soSaveSetting('title', mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 150));
                soSaveSetting('subtitle', mb_substr(trim((string) ($_POST['subtitle'] ?? '')), 0, 200));
                $accent = (string) ($_POST['accent'] ?? '');
                soSaveSetting('accent', preg_match('/^#[0-9a-f]{6}$/i', $accent) ? $accent : '#0ea5a4');

                foreach (['logo_left' => 'logo_kiri', 'logo_right' => 'logo_kanan'] as $key => $prefix) {
                    $new = soHandleUpload($key . '_file', $prefix);
                    if ($new !== '') {
                        soDeleteImage($settings[$key] ?? '');
                        soSaveSetting($key, $new);
                    } elseif (!empty($_POST[$key . '_remove'])) {
                        soDeleteImage($settings[$key] ?? '');
                        soSaveSetting($key, '');
                    }
                }
                $done(['so_msg' => 'Pengaturan judul & logo disimpan.']);
                break;

            /* ---------- TAMBAH / UBAH JABATAN ---------- */
            case 'save_node':
                $id       = (int) ($_POST['so_id'] ?? 0);
                $role     = mb_substr(trim((string) ($_POST['role'] ?? '')), 0, 150);
                $name     = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 150);
                $nip      = mb_substr(trim((string) ($_POST['nip'] ?? '')), 0, 50);
                $parentId = (int) ($_POST['parent_id'] ?? 0);
                $type     = ($_POST['node_type'] ?? '') === 'side' ? 'side' : 'child';
                $order    = (int) ($_POST['sort_order'] ?? 0);
                $active   = !empty($_POST['is_active']) ? 1 : 0;

                $back = ['so_view' => 'form', 'so_id' => $id ?: ''];

                if ($role === '') {
                    $done($back + ['so_err' => 'Nama jabatan wajib diisi.']);
                }

                $all = soNodes();
                $existing = $id ? soNode($id) : null;
                if ($id && !$existing) {
                    $done(['so_err' => 'Data jabatan tidak ditemukan.']);
                }

                if ($parentId) {
                    if (!soNode($parentId)) {
                        $done($back + ['so_err' => 'Atasan yang dipilih tidak ditemukan.']);
                    }
                    if ($id && in_array($parentId, soDescendantIds($all, $id), true)) {
                        $done($back + ['so_err' => 'Atasan tidak boleh jabatan itu sendiri atau bawahannya.']);
                    }
                } else {
                    $type = 'child'; // jabatan paling atas tidak bisa menjadi staf samping
                }

                if ($order <= 0) {
                    $max = 0;
                    foreach ($all as $n) {
                        if ((int) $n['parent_id'] === $parentId && (int) $n['id'] !== $id) {
                            $max = max($max, (int) $n['sort_order']);
                        }
                    }
                    $order = $max + 1;
                }

                $photo = $existing['photo'] ?? '';
                $newPhoto = soHandleUpload('photo_file', 'foto');
                if ($newPhoto !== '') {
                    $photo = $newPhoto;
                } elseif (!empty($_POST['photo_remove'])) {
                    $photo = '';
                }

                $parentParam = $parentId ?: null;

                if ($existing) {
                    soQuery(
                        'UPDATE ' . SO_TABLE . ' SET parent_id = ?, node_type = ?, role = ?, name = ?, nip = ?, photo = ?,
                                sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?',
                        'isssssiii',
                        [$parentParam, $type, $role, $name, $nip, $photo, $order, $active, $id]
                    );
                    if (($existing['photo'] ?? '') !== $photo) {
                        soDeleteImageIfUnused($existing['photo']);
                    }
                    $done(['so_msg' => 'Jabatan "' . $role . '" diperbarui.']);
                } else {
                    soQuery(
                        'INSERT INTO ' . SO_TABLE . ' (parent_id, node_type, role, name, nip, photo, sort_order, is_active, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                        'isssssii',
                        [$parentParam, $type, $role, $name, $nip, $photo, $order, $active]
                    );
                    $done(['so_msg' => 'Jabatan "' . $role . '" ditambahkan.']);
                }
                break;

            /* ---------- HAPUS JABATAN ---------- */
            case 'delete_node':
                $id = (int) ($_POST['so_id'] ?? 0);
                $node = soNode($id);
                if (!$node) {
                    $done(['so_err' => 'Data jabatan tidak ditemukan.']);
                }
                // bawahan naik ke atasan jabatan yang dihapus (tidak ikut terhapus),
                // ditempatkan di urutan paling akhir pada barisan barunya
                $newParent = $node['parent_id'] === null ? 0 : (int) $node['parent_id'];
                $all  = soNodes();
                $next = 0;
                foreach ($all as $n) {
                    if ((int) $n['parent_id'] === $newParent && (int) $n['id'] !== $id) {
                        $next = max($next, (int) $n['sort_order']);
                    }
                }
                foreach ($all as $n) {
                    if ((int) $n['parent_id'] !== $id) {
                        continue;
                    }
                    $next++;
                    if ($newParent === 0) {
                        soQuery('UPDATE ' . SO_TABLE . " SET parent_id = NULL, node_type = 'child', sort_order = ? WHERE id = ?", 'ii', [$next, (int) $n['id']]);
                    } else {
                        soQuery('UPDATE ' . SO_TABLE . ' SET parent_id = ?, sort_order = ? WHERE id = ?', 'iii', [$newParent, $next, (int) $n['id']]);
                    }
                }
                soQuery('DELETE FROM ' . SO_TABLE . ' WHERE id = ?', 'i', [$id]);
                soDeleteImageIfUnused($node['photo']);
                $done(['so_msg' => 'Jabatan "' . $node['role'] . '" dihapus. Bawahannya dipindahkan ke atasan berikutnya.']);
                break;

            /* ---------- NAIK / TURUN URUTAN ---------- */
            case 'move':
                $id  = (int) ($_POST['so_id'] ?? 0);
                $dir = ($_POST['dir'] ?? '') === 'up' ? -1 : 1;
                $node = soNode($id);
                if (!$node) {
                    $done(['so_err' => 'Data jabatan tidak ditemukan.']);
                }
                $siblings = array_values(array_filter(soNodes(), function ($n) use ($node) {
                    return (int) $n['parent_id'] === (int) $node['parent_id'] && $n['node_type'] === $node['node_type'];
                }));
                $ids = array_map(function ($n) { return (int) $n['id']; }, $siblings);
                $pos = array_search($id, $ids, true);
                $swap = $pos + $dir;
                if ($pos !== false && $swap >= 0 && $swap < count($ids)) {
                    [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
                }
                foreach ($ids as $i => $sid) {
                    soQuery('UPDATE ' . SO_TABLE . ' SET sort_order = ? WHERE id = ?', 'ii', [$i + 1, $sid]);
                }
                $done([]);
                break;

            default:
                $done(['so_err' => 'Aksi tidak dikenal.']);
        }
    } catch (Throwable $e) {
        $params = ['so_err' => $e->getMessage()];
        if ($action === 'save_node') {
            $params += ['so_view' => 'form', 'so_id' => (int) ($_POST['so_id'] ?? 0) ?: ''];
        }
        $done($params);
    }
}

/* =========================================================
 * TAMPILAN
 * ========================================================= */
$view  = ($_GET['so_view'] ?? '') === 'form' ? 'form' : 'list';
$msg   = trim((string) ($_GET['so_msg'] ?? ''));
$err   = trim((string) ($_GET['so_err'] ?? ''));

try {
    $settings = soSettings();
    $nodes    = soNodes();
    $tree     = soBuildTree($nodes);
    $flat     = soFlatten($tree);
} catch (Throwable $e) {
    $settings = soDefaultSettings();
    $nodes = $tree = $flat = [];
    $soInstallErrors[] = $e->getMessage();
}
$byId = [];
foreach ($nodes as $n) {
    $byId[(int) $n['id']] = $n;
}
$opacURL = SWB . 'index.php?p=struktur_organisasi';
?>

<style>
.so-admin{ padding:24px 28px 40px; font-family:inherit; color:#0f172a; }
.so-admin h2{ font-size:26px; font-weight:800; margin:0 0 4px; }
.so-admin .so-sub{ color:#64748b; margin:0 0 20px; }
.so-admin .so-panel{ background:#fff; border:1px solid #e8edf5; border-radius:16px; padding:20px 22px; margin-bottom:22px; box-shadow:0 4px 14px rgba(15,23,42,.04); }
.so-admin .so-panel h3{ font-size:17px; font-weight:700; margin:0 0 14px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
.so-admin .so-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px 18px; }
.so-admin label.so-lbl{ display:block; font-weight:600; font-size:13px; margin-bottom:5px; color:#334155; }
.so-admin .so-help{ font-size:12px; color:#64748b; margin-top:4px; }
.so-admin input[type=text], .so-admin input[type=number], .so-admin select{ width:100%; padding:9px 12px; border:1px solid #d6dde8; border-radius:10px; font-size:14px; background:#fff; }
.so-admin input[type=color]{ width:60px; height:38px; border:1px solid #d6dde8; border-radius:8px; padding:2px; }
.so-admin .so-btn{ white-space:nowrap; display:inline-flex; align-items:center; gap:6px; border:0; border-radius:10px; padding:9px 16px; font-weight:600; font-size:14px; cursor:pointer; text-decoration:none !important; line-height:1.2; }
.so-admin .so-btn-primary{ background:#2563eb; color:#fff !important; }
.so-admin .so-btn-success{ background:#16a34a; color:#fff !important; }
.so-admin .so-btn-light{ background:#f1f5f9; color:#0f172a !important; }
.so-admin .so-btn-danger{ background:#fee2e2; color:#b91c1c !important; }
.so-admin .so-btn-sm{ padding:6px 10px; font-size:12.5px; border-radius:8px; }
.so-admin .so-btn[disabled]{ opacity:.4; cursor:not-allowed; }
.so-admin .so-alert{ border-radius:12px; padding:12px 16px; margin-bottom:18px; font-size:14px; }
.so-admin .so-alert-ok{ background:#dcfce7; color:#166534; }
.so-admin .so-alert-err{ background:#fee2e2; color:#991b1b; }
.so-admin .so-logo-box{ display:flex; align-items:center; gap:12px; margin-bottom:8px; min-height:56px; }
.so-admin .so-logo-box img{ height:56px; max-width:160px; object-fit:contain; border:1px dashed #cbd5e1; border-radius:8px; padding:4px; background:#fff; }
.so-admin table.so-table{ width:100%; border-collapse:collapse; font-size:14px; }
.so-admin .so-table th{ text-align:left; background:#f8fafc; color:#475569; font-weight:700; padding:10px 12px; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.so-admin .so-table td{ padding:9px 12px; border-bottom:1px solid #eef2f7; vertical-align:middle; }
.so-admin .so-table tr.so-hidden td{ opacity:.55; }
.so-admin .so-thumb{ width:40px; height:40px; border-radius:50%; object-fit:cover; object-position:center top; background:#e2e8f0; }
.so-admin .so-indent{ display:inline-block; color:#94a3b8; font-family:monospace; white-space:pre; }
.so-admin .so-badge{ display:inline-block; border-radius:999px; padding:2px 10px; font-size:12px; font-weight:600; white-space:nowrap; }
.so-admin .so-badge-side{ background:#fef3c7; color:#92400e; }
.so-admin .so-badge-child{ background:#e0f2fe; color:#075985; }
.so-admin .so-badge-off{ background:#f1f5f9; color:#64748b; }
.so-admin .so-actions{ display:flex; gap:5px; flex-wrap:nowrap; }
.so-admin .so-actions form{ margin:0; display:inline; }
.so-admin .so-radio{ display:flex; gap:8px; align-items:flex-start; margin-bottom:6px; font-weight:400; }
.so-admin .so-photo-preview{ width:110px; height:110px; border-radius:50%; object-fit:cover; object-position:center top; background:#e2e8f0; border:3px solid #fff; box-shadow:0 2px 8px rgba(0,0,0,.12); }
.so-admin .so-preview{ zoom:.8; }
.so-admin .so-table-wrap{ overflow-x:auto; }
</style>

<div class="so-admin" id="soAdminRoot" data-base="<?= soE($soBase); ?>">

    <h2>Struktur Organisasi</h2>
    <p class="so-sub">
        Atur judul, logo, jabatan, nama pejabat, dan foto. Perubahan langsung tampil di
        <a href="<?= soE($opacURL); ?>" target="_blank" rel="noopener" class="notAJAX" data-so-external="1">halaman Struktur Organisasi</a>.
    </p>

    <?php foreach ($soInstallErrors as $ie): ?>
        <div class="so-alert so-alert-err"><?= soE($ie); ?></div>
    <?php endforeach; ?>
    <?php if ($msg !== ''): ?>
        <div class="so-alert so-alert-ok"><?= soE($msg); ?></div>
    <?php endif; ?>
    <?php if ($err !== ''): ?>
        <div class="so-alert so-alert-err"><?= soE($err); ?></div>
    <?php endif; ?>

<?php if ($view === 'form'):
    /* =====================================================
     * FORM TAMBAH / UBAH JABATAN
     * ===================================================== */
    $editId = (int) ($_GET['so_id'] ?? 0);
    $node = $editId ? ($byId[$editId] ?? null) : null;
    if ($editId && !$node) {
        echo '<div class="so-alert so-alert-err">Data jabatan tidak ditemukan.</div>';
    }
    $node = $node ?: [
        'id' => 0, 'parent_id' => (int) ($_GET['so_parent'] ?? 0) ?: null, 'node_type' => 'child',
        'role' => '', 'name' => '', 'nip' => '', 'photo' => '', 'sort_order' => 0, 'is_active' => 1,
    ];
    $blocked = $node['id'] ? soDescendantIds($nodes, $node['id']) : [];
?>
    <div class="so-panel">
        <h3><?= $node['id'] ? 'Ubah Jabatan' : 'Tambah Jabatan'; ?></h3>

        <form method="post" action="<?= soE($soBase); ?>" target="blindSubmit" enctype="multipart/form-data" class="so-form">
            <input type="hidden" name="so_action" value="save_node">
            <input type="hidden" name="so_csrf" value="<?= soE($soCsrf); ?>">
            <input type="hidden" name="so_id" value="<?= (int) $node['id']; ?>">

            <div style="display:flex;gap:28px;flex-wrap:wrap;align-items:flex-start;">
                <div style="text-align:center;">
                    <img class="so-photo-preview" id="soPhotoPreview" src="<?= soE(soImageURL($node['photo'])); ?>" alt="Foto">
                    <div style="margin-top:10px;">
                        <input type="file" name="photo_file" id="soPhotoInput" accept="image/jpeg,image/png,image/webp,image/gif" style="max-width:220px;">
                        <div class="so-help">JPG/PNG/WEBP, maks. 2 MB.<br>Disarankan foto potret / persegi.</div>
                        <?php if ($node['photo']): ?>
                            <label class="so-radio" style="justify-content:center;margin-top:6px;">
                                <input type="checkbox" name="photo_remove" value="1"> Hapus foto
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="flex:1 1 420px;">
                    <div class="so-grid">
                        <div>
                            <label class="so-lbl">Nama Jabatan *</label>
                            <input type="text" name="role" required maxlength="150" value="<?= soE($node['role']); ?>" placeholder="mis. KEPALA UNIT PERPUSTAKAAN">
                        </div>
                        <div>
                            <label class="so-lbl">Nama Pejabat</label>
                            <input type="text" name="name" maxlength="150" value="<?= soE($node['name']); ?>" placeholder="Nama lengkap & gelar">
                        </div>
                        <div>
                            <label class="so-lbl">NIP</label>
                            <input type="text" name="nip" maxlength="50" value="<?= soE($node['nip']); ?>" placeholder="Kosongkan bila tidak ditampilkan">
                        </div>
                        <div>
                            <label class="so-lbl">Atasan</label>
                            <select name="parent_id">
                                <option value="0">— Tidak ada (posisi paling atas) —</option>
                                <?php foreach ($flat as $f):
                                    if (in_array((int) $f['id'], $blocked, true)) continue; ?>
                                    <option value="<?= (int) $f['id']; ?>" <?= (int) $f['id'] === (int) $node['parent_id'] ? 'selected' : ''; ?>>
                                        <?= str_repeat('— ', (int) $f['depth']) . soE($f['role']) . ($f['name'] ? ' (' . soE($f['name']) . ')' : ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="so-grid" style="margin-top:14px;">
                        <div>
                            <label class="so-lbl">Posisi di bagan</label>
                            <label class="so-radio"><input type="radio" name="node_type" value="child" <?= $node['node_type'] !== 'side' ? 'checked' : ''; ?>>
                                <span><b>Bawahan langsung</b> — tampil di baris bawah atasan</span></label>
                            <label class="so-radio"><input type="radio" name="node_type" value="side" <?= $node['node_type'] === 'side' ? 'checked' : ''; ?>>
                                <span><b>Staf samping</b> — tampil di samping garis atasan (mis. Tata Usaha)</span></label>
                        </div>
                        <div>
                            <label class="so-lbl">Urutan</label>
                            <input type="number" name="sort_order" min="0" value="<?= (int) $node['sort_order']; ?>" style="max-width:120px;">
                            <div class="so-help">Urutan dari kiri ke kanan di antara jabatan dengan atasan yang sama. Isi 0 untuk ditaruh paling akhir.</div>
                            <label class="so-radio" style="margin-top:10px;">
                                <input type="checkbox" name="is_active" value="1" <?= (int) $node['is_active'] === 1 ? 'checked' : ''; ?>>
                                <span>Tampilkan di bagan</span>
                            </label>
                        </div>
                    </div>

                    <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="submit" class="so-btn so-btn-primary">Simpan</button>
                        <a href="<?= soE($soBase); ?>" class="so-btn so-btn-light" data-so-nav="<?= soE($soBase); ?>">Batal</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

<?php else:
    /* =====================================================
     * HALAMAN UTAMA: PENGATURAN + DAFTAR JABATAN + PRATINJAU
     * ===================================================== */
?>
    <!-- PENGATURAN JUDUL & LOGO -->
    <div class="so-panel">
        <h3>Judul &amp; Logo</h3>
        <form method="post" action="<?= soE($soBase); ?>" target="blindSubmit" enctype="multipart/form-data" class="so-form">
            <input type="hidden" name="so_action" value="save_setting">
            <input type="hidden" name="so_csrf" value="<?= soE($soCsrf); ?>">
            <div class="so-grid">
                <div>
                    <label class="so-lbl">Judul</label>
                    <input type="text" name="title" maxlength="150" value="<?= soE($settings['title']); ?>">
                </div>
                <div>
                    <label class="so-lbl">Sub-judul</label>
                    <input type="text" name="subtitle" maxlength="200" value="<?= soE($settings['subtitle']); ?>">
                </div>
                <div>
                    <label class="so-lbl">Warna utama</label>
                    <input type="color" name="accent" value="<?= soE($settings['accent'] ?: '#0ea5a4'); ?>">
                </div>
            </div>
            <div class="so-grid" style="margin-top:16px;">
                <?php foreach (['logo_left' => 'Logo kiri', 'logo_right' => 'Logo kanan'] as $k => $label):
                    $url = soImageURL($settings[$k] ?? '', ''); ?>
                    <div>
                        <label class="so-lbl"><?= $label; ?></label>
                        <div class="so-logo-box">
                            <?php if ($url): ?>
                                <img src="<?= soE($url); ?>" alt="<?= $label; ?>">
                                <label class="so-radio"><input type="checkbox" name="<?= $k; ?>_remove" value="1"> Hapus</label>
                            <?php else: ?>
                                <span class="so-help">Belum ada logo</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="<?= $k; ?>_file" accept="image/jpeg,image/png,image/webp,image/gif">
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:16px;">
                <button type="submit" class="so-btn so-btn-primary">Simpan Judul &amp; Logo</button>
            </div>
        </form>
    </div>

    <!-- DAFTAR JABATAN -->
    <div class="so-panel">
        <h3>
            <span>Daftar Jabatan <small style="color:#64748b;font-weight:500;">(<?= count($nodes); ?>)</small></span>
            <a href="<?= soE(soUrl($soBase, ['so_view' => 'form'])); ?>" class="so-btn so-btn-success" data-so-nav="<?= soE(soUrl($soBase, ['so_view' => 'form'])); ?>">+ Tambah Jabatan</a>
        </h3>

        <div class="so-table-wrap">
        <table class="so-table">
            <thead>
                <tr>
                    <th style="width:56px;">Foto</th>
                    <th>Jabatan</th>
                    <th>Nama Pejabat</th>
                    <th>NIP</th>
                    <th>Posisi</th>
                    <th style="width:1%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$flat): ?>
                <tr><td colspan="6" style="text-align:center;padding:30px;color:#64748b;">Belum ada jabatan. Klik <b>+ Tambah Jabatan</b>.</td></tr>
            <?php endif; ?>
            <?php
            // posisi di antara saudara (untuk menonaktifkan tombol naik/turun)
            $siblingIndex = [];
            foreach ($flat as $f) {
                $siblingIndex[(int) $f['parent_id'] . '|' . $f['node_type']][] = (int) $f['id'];
            }
            foreach ($flat as $f):
                $group = $siblingIndex[(int) $f['parent_id'] . '|' . $f['node_type']];
                $pos = array_search((int) $f['id'], $group, true);
                $editURL = soUrl($soBase, ['so_view' => 'form', 'so_id' => (int) $f['id']]);
                $addURL  = soUrl($soBase, ['so_view' => 'form', 'so_parent' => (int) $f['id']]);
            ?>
                <tr class="<?= (int) $f['is_active'] === 1 ? '' : 'so-hidden'; ?>">
                    <td><img class="so-thumb" src="<?= soE(soImageURL($f['photo'])); ?>" alt=""></td>
                    <td>
                        <span class="so-indent"><?= $f['depth'] ? str_repeat('   ', (int) $f['depth'] - 1) . '└─ ' : ''; ?></span><b><?= soE($f['role']); ?></b>
                    </td>
                    <td><?= soE($f['name'] ?: '-'); ?></td>
                    <td><?= soE($f['nip'] ?: '-'); ?></td>
                    <td>
                        <?php if ($f['parent_id'] === null): ?>
                            <span class="so-badge so-badge-child">Paling atas</span>
                        <?php elseif ($f['node_type'] === 'side'): ?>
                            <span class="so-badge so-badge-side">Staf samping</span>
                        <?php else: ?>
                            <span class="so-badge so-badge-child">Bawahan</span>
                        <?php endif; ?>
                        <?php if ((int) $f['is_active'] !== 1): ?>
                            <span class="so-badge so-badge-off">Disembunyikan</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="so-actions">
                            <form method="post" action="<?= soE($soBase); ?>" target="blindSubmit">
                                <input type="hidden" name="so_action" value="move">
                                <input type="hidden" name="so_csrf" value="<?= soE($soCsrf); ?>">
                                <input type="hidden" name="so_id" value="<?= (int) $f['id']; ?>">
                                <input type="hidden" name="dir" value="up">
                                <button type="submit" class="so-btn so-btn-light so-btn-sm" title="Geser ke kiri / atas" <?= $pos === 0 ? 'disabled' : ''; ?>>&uarr;</button>
                            </form>
                            <form method="post" action="<?= soE($soBase); ?>" target="blindSubmit">
                                <input type="hidden" name="so_action" value="move">
                                <input type="hidden" name="so_csrf" value="<?= soE($soCsrf); ?>">
                                <input type="hidden" name="so_id" value="<?= (int) $f['id']; ?>">
                                <input type="hidden" name="dir" value="down">
                                <button type="submit" class="so-btn so-btn-light so-btn-sm" title="Geser ke kanan / bawah" <?= $pos === count($group) - 1 ? 'disabled' : ''; ?>>&darr;</button>
                            </form>
                            <a href="<?= soE($editURL); ?>" class="so-btn so-btn-primary so-btn-sm" data-so-nav="<?= soE($editURL); ?>">Ubah</a>
                            <a href="<?= soE($addURL); ?>" class="so-btn so-btn-light so-btn-sm" title="Tambah bawahan" data-so-nav="<?= soE($addURL); ?>">+ Bawahan</a>
                            <form method="post" action="<?= soE($soBase); ?>" target="blindSubmit" data-so-confirm="Hapus jabatan &quot;<?= soE($f['role']); ?>&quot;? Bawahannya akan dipindahkan ke atasan berikutnya.">
                                <input type="hidden" name="so_action" value="delete_node">
                                <input type="hidden" name="so_csrf" value="<?= soE($soCsrf); ?>">
                                <input type="hidden" name="so_id" value="<?= (int) $f['id']; ?>">
                                <button type="submit" class="so-btn so-btn-danger so-btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- PRATINJAU -->
    <div class="so-panel">
        <h3>
            <span>Pratinjau</span>
            <a href="<?= soE($opacURL); ?>" target="_blank" rel="noopener" class="so-btn so-btn-light so-btn-sm notAJAX" data-so-external="1">Buka halaman publik ↗</a>
        </h3>
        <div class="so-preview">
            <?= soRenderChart($settings, soPruneInactive($tree)); ?>
        </div>
    </div>
<?php endif; ?>
</div>

<script>
(function () {
    'use strict';
    var root = document.getElementById('soAdminRoot');
    if (!root) { return; }

    function go(url) {
        if (window.jQuery && jQuery.fn && jQuery.fn.simbioAJAX && jQuery('#mainContent').length) {
            jQuery('#mainContent').simbioAJAX(url);
        } else {
            window.location.href = url;
        }
    }

    /* navigasi di dalam panel (Ubah, Tambah, Batal) */
    root.addEventListener('click', function (ev) {
        var ext = ev.target.closest('[data-so-external]');
        if (ext) { ev.stopPropagation(); return; } // buka tab baru apa adanya
        var a = ev.target.closest('[data-so-nav]');
        if (!a || !root.contains(a)) { return; }
        ev.preventDefault();
        ev.stopPropagation();
        go(a.getAttribute('data-so-nav'));
    }, true);

    /* konfirmasi hapus + pastikan form tidak diproses ulang oleh skrip lain */
    root.querySelectorAll('form').forEach(function (f) {
        f.addEventListener('submit', function (ev) {
            var msg = f.getAttribute('data-so-confirm');
            if (msg && !window.confirm(msg)) {
                ev.preventDefault();
            }
            ev.stopImmediatePropagation();
        });
    });

    /* pratinjau foto sebelum disimpan */
    var input = document.getElementById('soPhotoInput');
    var prev  = document.getElementById('soPhotoPreview');
    if (input && prev && window.FileReader) {
        input.addEventListener('change', function () {
            if (input.files && input.files[0]) {
                var r = new FileReader();
                r.onload = function (e) { prev.src = e.target.result; };
                r.readAsDataURL(input.files[0]);
            }
        });
    }
})();
</script>
