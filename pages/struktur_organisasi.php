<div class="org-wrapper">
    <div class="org-card-container">
        <div class="org-header-card">
            <div class="org-header-logos">
                <img src="plugins/struktur_organisasi/images/logo.png" class="org-logo">
            </div>
            <div class="org-header-text">
                STRUKTUR ORGANISASI <br>
                PERPUSTAKAAN SENAYANG LIBRARY MANAGEMENT SYSTEM
            </div>
        </div>

        <!-- Direktur -->
        <div class="org-level">
            <div class="org-card org-leader">
                <img src="plugins/struktur_organisasi/images/direktur.png" class="org-photo">
                <div class="org-info">
                    <div class="org-role">DIREKTUR</div>
                    <div class="org-name">Nama Pimpinan</div>
                    <div class="org-nip">NIP: 000000000000000</div>
                </div>
            </div>
        </div>

        <!-- Wakil -->
        <div class="org-line"></div>
        <div class="org-level">
            <div class="org-card org-leader">
                <img src="plugins/struktur_organisasi/images/wakil.png" class="org-photo">
                <div class="org-info">
                    <div class="org-role">WAKIL PIMPINAN</div>
                    <div class="org-name">Nama Wakil Pimpinan</div>
                    <div class="org-nip">NIP: 000000000000000</div>
                </div>
            </div>
        </div>

        <!-- Kepala -->
        <div class="org-line"></div>
        <div class="org-level">
            <div class="org-card org-leader">
                <img src="plugins/struktur_organisasi/images/kepala.png" class="org-photo">
                <div class="org-info">
                    <div class="org-role">KEPALA PERPUSTAKAAN</div>
                    <div class="org-name">Nama Kepala Perpustakaan</div>
                    <div class="org-nip">NIP: 000000000000000</div>
                </div>
            </div>
        </div>

        <!-- Staf -->
        <div class="org-line"></div>
        <div class="org-structure">

            <!-- GARIS UTAMA -->
            <div class="org-tree">

                <!-- garis vertikal dari kepala -->
                <div class="org-v-line"></div>

                <!-- cabang ke kanan (TU) -->
                <div class="org-tu-line"></div>

                <div class="org-card org-staff org-tu">
                    <img src="plugins/struktur_organisasi/images/tu.png" class="org-photo-sm">
                    <div class="org-info">
                        <div class="org-role">Tata Usaha</div>
                        <div class="org-name">Nama TU</div>
                    </div>
                </div>

                <!-- garis horizontal utama -->
                <div class="org-h-line"></div>

                <!-- kiri -->
                <div class="org-left">
                    <div class="org-card org-staff">
                        <img src="plugins/struktur_organisasi/images/sirkulasi.png " class="org-photo-sm">
                        <div class="org-info">
                            <div class="org-role">Bidang Sirkulasi</div>
                            <div class="org-name">Nama PJ Sirkulasi</div>
                        </div>
                    </div>

                    <div class="org-line"></div>

                    <div class="org-card org-staff">
                        <img src="plugins/struktur_organisasi/images/teknis.png" class="org-photo-sm">
                        <div class="org-info">
                            <div class="org-role">Tenaga Teknis</div>
                            <div class="org-name">Nama Tenaga Teknis</div>
                        </div>
                    </div>
                </div>

                <!-- tengah -->
                <div class="org-middle">
                    <div class="org-card org-staff">
                        <img src="plugins/struktur_organisasi/images/pelayanan.png" class="org-photo-sm">
                        <div class="org-info">
                            <div class="org-role">Bidang Pelayanan</div>
                            <div class="org-name">Nama PJ Pelayanan</div>
                        </div>
                    </div>
                </div>

                <!-- kanan -->
                <div class="org-right">
                    <div class="org-card org-staff">
                        <img src="plugins/struktur_organisasi/images/pengolahan.png" class="org-photo-sm">
                        <div class="org-info">
                            <div class="org-role">Pengelolaan & Pengembangan</div>
                            <div class="org-name">Nama PJ Pengelolaan</div>
                        </div>
                    </div>

                    <div class="org-line"></div>

                    <div class="org-card org-staff">
                        <img src="plugins/struktur_organisasi/images/it.png" class="org-photo-sm">
                        <div class="org-info">
                            <div class="org-role">Teknis IT</div>
                            <div class="org-name">Nama Teknis IT</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<style>
    /* Wrapper */
    .org-wrapper {
        font-family: 'Segoe UI', sans-serif;
        max-width: 1500px;
        width: 100%;
        margin: 0 auto;
        padding: -10px;
    }

    /* Background card utama */
    .org-card-container {
        background: #ffffff;
        border-radius: 22px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        padding: 40px 30px;
        text-align: center;
    }

    /* Level */
    .org-level {
        display: flex;
        justify-content: center;
        margin: 20px 0;
    }

    /* Horizontal staf */
    .org-horizontal {
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }

    /* Card umum */
    .org-card {
        display: flex;
        align-items: center;
        gap: 15px;
        border-radius: 14px;
        padding: 15px 18px;
        background: #ecfaff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transition: 0.3s;
    }

    .org-card:hover {
        transform: translateY(-3px);
    }

    /* Leader */
    .org-leader {
        width: 420px;
        border-left: 5px solid #0ea5a4;
    }

    /* Staff */
    .org-staff {
        width: 270px;
    }

    /* Foto */
    .org-photo {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        object-fit: cover;
    }

    .org-photo-sm {
        width: 50px;
        height: 50px;
        border-radius: 50%;
    }

    /* Info */
    .org-info {
        text-align: left;
    }

    .org-role {
        font-size: 13px;
        color: #64748b;
    }

    .org-name {
        font-weight: 600;
        font-size: 16px;
        color: #0f172a;
    }

    .org-nip {
        font-size: 12px;
        color: #94a3b8;
    }

    /* Garis */
    .org-line {
        width: 2px;
        height: 25px;
        background: #cbd5f5;
        margin: auto;
    }

    /* HEADER CARD */
    .org-header-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 20px 15px;
        margin-bottom: 30px;
        text-align: center;
        position: relative;
    }

    /* Logo kiri kanan */
    .org-header-logos {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 10px;
    }

    .org-logo {
        height: 45px;
        object-fit: contain;
    }

    /* Text header */
    .org-header-text {
        font-size: 28px;
        font-weight: 800;
        color: #00a884;
        line-height: 1.4;
    }

    /* TREE SYSTEM FIXED */
    .org-tree {
        position: relative;
        height: 520px;
    }

    /* garis vertikal utama */
    .org-v-line {
        position: absolute;
        top: 0;
        left: 50%;
        width: 2px;
        height: 100px;
        background: #94a3b8;
        transform: translateX(-50%);
    }

    /* garis horizontal utama (T utama) */
    .org-h-line {
        position: absolute;
        top: 105px;
        left: 50%;
        width: 70%;
        height: 2px;
        background: #94a3b8;
        transform: translateX(-50%);
    }

    /* garis ke TU */
    .org-tu-line {
        position: absolute;
        top: 10px;
        left: 50%;
        width: 220px;
        height: 2px;
        background: #94a3b8;
        transform: translateY(-50%);
    }

    /* posisi TU */
    .org-tu {
        position: absolute;
        top: 10px;
        left: calc(50% + 240px);
        transform: translateY(-50%);
    }

    /* KIRI */
    .org-left {
        position: absolute;
        top: 140px;
        left: 15%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    /* TENGAH */
    .org-middle {
        position: absolute;
        top: 140px;
        left: 50%;
        transform: translateX(-50%);
    }

    /* KANAN */
    .org-right {
        position: absolute;
        top: 140px;
        left: 85%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    /* ========================= */
    /* RESPONSIVE MODE */
    /* ========================= */

    @media (max-width: 768px) {

        /* container */
        .org-card-container {
            padding: 20px;
        }

        /* matikan sistem absolute */
        .org-tree {
            position: static;
            height: auto;
        }

        .org-v-line,
        .org-h-line,
        .org-tu-line {
            display: none;
        }

        /* reset semua posisi */
        .org-tu,
        .org-left,
        .org-middle,
        .org-right {
            position: static;
            transform: none;
            left: auto;
            right: auto;
            top: auto;
            margin-bottom: 20px;
        }

        /* ubah jadi stack */
        .org-tree {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        /* group kiri-kanan jadi satu kolom */
        .org-left,
        .org-middle,
        .org-right {
            width: 100%;
            align-items: center;
        }

        /* card full width */
        .org-card {
            width: 100%;
            max-width: 350px;
        }

        /* leader juga menyesuaikan */
        .org-leader {
            width: 100%;
            max-width: 400px;
        }

    }

    @media (max-width: 768px) {

        /* semua cabang wajib center */
        .org-left,
        .org-middle,
        .org-right {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .org-middle {
            margin: 0 auto;
        }

        /* card biar rata tengah */
        .org-card {
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
        }

    }
</style>