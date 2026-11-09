<?php
/* Amanda Damayanti_202432027 */
require_once "config.php";

// ---------------------------------------------------------
// Keamanan: halaman ini tidak boleh diakses kalau belum
// login ATAU kalau role yang login bukan admin.
// Pengecekan dilakukan di backend (PHP), bukan cuma
// menyembunyikan tombol di HTML, supaya tidak bisa
// ditembus langsung lewat URL oleh role user biasa.
// ---------------------------------------------------------
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = "";

// ---------------------------------------------------------
// Proses aksi (POST) dari form-form di halaman ini
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'tambah_barang') {
        $nama = trim($_POST['nama_barang']);
        $stok = (int) $_POST['stok'];
        $lokasi = $_POST['lokasi_lab'];

        $stmt = $conn->prepare("INSERT INTO barang (nama_barang, stok, lokasi_lab) VALUES (:nama, :stok, :lokasi)");
        $stmt->execute([':nama' => $nama, ':stok' => $stok, ':lokasi' => $lokasi]);
        $msg = "tambah_sukses";
    }

    elseif ($action === 'hapus_barang') {
        $barang_id = (int) $_POST['barang_id'];

        // Syarat validasi delete: barang tidak boleh dihapus kalau masih
        // berstatus 'approved' (sedang dipinjam praktikan)
        $cek = $conn->prepare("SELECT COUNT(*) FROM peminjaman WHERE barang_id = :id AND status = 'approved'");
        $cek->execute([':id' => $barang_id]);

        if ($cek->fetchColumn() > 0) {
            $msg = "hapus_gagal";
        } else {
            $stmt = $conn->prepare("DELETE FROM barang WHERE id = :id");
            $stmt->execute([':id' => $barang_id]);
            $msg = "hapus_sukses";
        }
    }

    elseif ($action === 'approve') {
        $pinjam_id = (int) $_POST['peminjaman_id'];

        $stmt = $conn->prepare("SELECT barang_id FROM peminjaman WHERE id = :id");
        $stmt->execute([':id' => $pinjam_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $conn->prepare("UPDATE peminjaman SET status = 'approved' WHERE id = :id")
                 ->execute([':id' => $pinjam_id]);
            // Stok berkurang otomatis saat disetujui
            $conn->prepare("UPDATE barang SET stok = stok - 1 WHERE id = :bid AND stok > 0")
                 ->execute([':bid' => $row['barang_id']]);
        }
        $msg = "approve_sukses";
    }

    elseif ($action === 'tolak') {
        $pinjam_id = (int) $_POST['peminjaman_id'];
        $conn->prepare("UPDATE peminjaman SET status = 'ditolak' WHERE id = :id")
             ->execute([':id' => $pinjam_id]);
        $msg = "tolak_sukses";
    }

    elseif ($action === 'kembalikan') {
        $pinjam_id = (int) $_POST['peminjaman_id'];

        $stmt = $conn->prepare("SELECT barang_id FROM peminjaman WHERE id = :id");
        $stmt->execute([':id' => $pinjam_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $conn->prepare("UPDATE peminjaman SET status = 'dikembalikan' WHERE id = :id")
                 ->execute([':id' => $pinjam_id]);
            // Stok bertambah lagi saat barang dikembalikan
            $conn->prepare("UPDATE barang SET stok = stok + 1 WHERE id = :bid")
                 ->execute([':bid' => $row['barang_id']]);
        }
        $msg = "kembalikan_sukses";
    }

    // Redirect (Post/Redirect/Get) supaya form tidak ke-submit ulang saat refresh
    header("Location: admin.php?msg=" . $msg);
    exit;
}

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
}

// ---------------------------------------------------------
// Ambil data untuk ditampilkan
// ---------------------------------------------------------
$barangList = $conn->query("SELECT * FROM barang ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$peminjamanList = $conn->query("
    SELECT p.id, p.tanggal_pinjam, p.status, u.nama_lengkap, b.nama_barang
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN barang b ON p.barang_id = b.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$badgeClass = [
    'pending' => 'badge-pending',
    'approved' => 'badge-approved',
    'ditolak' => 'badge-ditolak',
    'dikembalikan' => 'badge-dikembalikan',
];
$badgeLabel = [
    'pending' => '⏳ Pending',
    'approved' => '✓ Approved',
    'ditolak' => '✕ Ditolak',
    'dikembalikan' => '↺ Dikembalikan',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin Panel | Lab-Ventory</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="navbar">
    <div class="brand">🧪 Lab-Ventory <span class="sub">Admin Panel</span></div>
    <div class="nav-right">
        👤 Halo, <b>&nbsp;<?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></b>
        &nbsp;&nbsp;<a href="logout.php" style="color:#dc2626; font-weight:600;">⏻ Logout</a>
    </div>
</div>

<div class="dashboard">
    <div class="grid-2">
        <div class="card">
            <h3>➕ Tambah Barang</h3>
            <form method="POST" action="admin.php">
                <input type="hidden" name="action" value="tambah_barang">
                <div class="form-group">
                    <input type="text" name="nama_barang" placeholder="Nama Barang" required>
                </div>
                <div class="form-group">
                    <input type="number" name="stok" placeholder="Jumlah Stok" min="0" required>
                </div>
                <div class="form-group">
                    <select name="lokasi_lab" required>
                        <option value="Software Engineering">Software Engineering</option>
                        <option value="Intelligent Computing">Intelligent Computing</option>
                        <option value="Program & Workshop">Program &amp; Workshop</option>
                    </select>
                </div>
                <button type="submit" class="btn">💾 Simpan Data</button>
            </form>
        </div>

        <div class="card">
            <h3>🗂 Master Data Inventaris</h3>
            <table>
                <thead>
                    <tr><th>Nama Barang</th><th>Stok</th><th>Laboratorium</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php if (empty($barangList)): ?>
                    <tr><td colspan="4" class="empty-note">Belum ada data barang.</td></tr>
                <?php else: foreach ($barangList as $b): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['nama_barang']); ?></td>
                        <td><?php echo (int) $b['stok']; ?> Unit</td>
                        <td><span class="badge badge-blue"><?php echo htmlspecialchars(strtoupper($b['lokasi_lab'])); ?></span></td>
                        <td>
                            <form method="POST" action="admin.php" style="display:inline"
                                  onsubmit="return confirmDelete(event, '<?php echo htmlspecialchars(addslashes($b['nama_barang'])); ?>', this)">
                                <input type="hidden" name="action" value="hapus_barang">
                                <input type="hidden" name="barang_id" value="<?php echo (int) $b['id']; ?>">
                                <button type="submit" class="icon-btn">🗑</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3>✅ Daftar Permintaan Peminjaman</h3>
        <table>
            <thead>
                <tr><th>Tanggal</th><th>Peminjam (Praktikan)</th><th>Alat yang Dipinjam</th><th>Status</th><th>Aksi Admin</th></tr>
            </thead>
            <tbody>
            <?php if (empty($peminjamanList)): ?>
                <tr><td colspan="5" class="empty-note">Belum ada permintaan peminjaman.</td></tr>
            <?php else: foreach ($peminjamanList as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['tanggal_pinjam']); ?></td>
                    <td><?php echo htmlspecialchars($p['nama_lengkap']); ?></td>
                    <td><?php echo htmlspecialchars($p['nama_barang']); ?></td>
                    <td><span class="badge <?php echo $badgeClass[$p['status']]; ?>"><?php echo $badgeLabel[$p['status']]; ?></span></td>
                    <td>
                        <?php if ($p['status'] === 'pending'): ?>
                            <form method="POST" action="admin.php" style="display:inline">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="peminjaman_id" value="<?php echo (int) $p['id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm">✓ Setujui</button>
                            </form>
                            <form method="POST" action="admin.php" style="display:inline">
                                <input type="hidden" name="action" value="tolak">
                                <input type="hidden" name="peminjaman_id" value="<?php echo (int) $p['id']; ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#dc2626;">✕ Tolak</button>
                            </form>
                        <?php elseif ($p['status'] === 'approved'): ?>
                            <form method="POST" action="admin.php" style="display:inline">
                                <input type="hidden" name="action" value="kembalikan">
                                <input type="hidden" name="peminjaman_id" value="<?php echo (int) $p['id']; ?>">
                                <button type="submit" class="btn btn-outline btn-sm">↺ Tandai Selesai / Kembali</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#94a3b8; font-size:12px;">🔒 Selesai</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(e, namaBarang, form) {
    e.preventDefault();
    Swal.fire({
        icon: 'warning',
        title: 'Yakin Hapus Barang?',
        text: 'Anda akan menghapus ' + namaBarang + ' secara permanen dari sistem!',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) form.submit();
    });
    return false;
}

<?php if ($msg === 'tambah_sukses'): ?>
Swal.fire({icon:'success', title:'Berhasil!', text:'Barang baru berhasil ditambahkan ke inventaris!', confirmButtonColor:'#2563eb'});
<?php elseif ($msg === 'hapus_sukses'): ?>
Swal.fire({icon:'success', title:'Berhasil!', text:'Barang berhasil dihapus dari inventaris.', confirmButtonColor:'#2563eb'});
<?php elseif ($msg === 'hapus_gagal'): ?>
Swal.fire({icon:'error', title:'Peringatan!', text:'Barang tidak bisa dihapus karena masih dipinjam praktikan!', confirmButtonColor:'#dc2626'});
<?php elseif ($msg === 'approve_sukses'): ?>
Swal.fire({icon:'success', title:'Berhasil!', text:'Peminjaman telah disetujui, stok alat dikurangi otomatis.', confirmButtonColor:'#2563eb'});
<?php elseif ($msg === 'tolak_sukses'): ?>
Swal.fire({icon:'warning', title:'Berhasil!', text:'Permintaan peminjaman ditolak.', confirmButtonColor:'#dc2626'});
<?php elseif ($msg === 'kembalikan_sukses'): ?>
Swal.fire({icon:'success', title:'Berhasil!', text:'Barang telah dikembalikan, stok alat kembali bertambah.', confirmButtonColor:'#2563eb'});
<?php endif; ?>
</script>

</body>
</html>
