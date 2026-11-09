<?php
/* Amanda Damayanti_202432027 */
require_once "config.php";

// Keamanan: halaman ini tidak boleh diakses kalau belum login
// atau kalau role yang login bukan 'user'. Sama seperti admin.php,
// pengecekan role dilakukan di backend PHP.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$msg = "";
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pinjam') {
    $barang_id = (int) $_POST['barang_id'];
    $tanggal_pinjam = trim($_POST['tanggal_pinjam'] ?? '');
    $hariIni = date('Y-m-d'); // tanggal server hari ini, patokan validasi

    // Validasi tanggal di BACKEND (jangan cuma andalkan atribut min="" di HTML,
    // karena itu bisa gampang diubah/di-bypass lewat inspect element).
    // Format harus YYYY-MM-DD dan tidak boleh tanggal yang sudah lewat.
    $formatValid = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_pinjam);

    if (!$formatValid || $tanggal_pinjam < $hariIni) {
        $msg = "tanggal_invalid";
    } else {
        // Cek ulang stok di backend (jangan percaya tombol disabled di frontend saja)
        $cek = $conn->prepare("SELECT stok FROM barang WHERE id = :id");
        $cek->execute([':id' => $barang_id]);
        $barang = $cek->fetch(PDO::FETCH_ASSOC);

        if ($barang && $barang['stok'] > 0) {
            $stmt = $conn->prepare("INSERT INTO peminjaman (user_id, barang_id, tanggal_pinjam, status)
                                     VALUES (:uid, :bid, :tanggal, 'pending')");
            $stmt->execute([':uid' => $user_id, ':bid' => $barang_id, ':tanggal' => $tanggal_pinjam]);
            $msg = "pinjam_sukses";
        } else {
            $msg = "pinjam_gagal";
        }
    }

    header("Location: user.php?msg=" . $msg);
    exit;
}

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
}

// Katalog barang (read only untuk praktikan)
$barangList = $conn->query("SELECT * FROM barang ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Riwayat peminjaman milik user yang sedang login saja
$stmtRiwayat = $conn->prepare("
    SELECT p.tanggal_pinjam, p.status, b.nama_barang
    FROM peminjaman p
    JOIN barang b ON p.barang_id = b.id
    WHERE p.user_id = :uid
    ORDER BY p.id DESC
");
$stmtRiwayat->execute([':uid' => $user_id]);
$riwayat = $stmtRiwayat->fetchAll(PDO::FETCH_ASSOC);

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
<title>Portal Praktikan | Lab-Ventory</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="navbar">
    <div class="brand">🧪 Lab-Ventory <span class="sub">Portal Praktikan</span></div>
    <div class="nav-right">
        👤 Halo, <b>&nbsp;<?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></b>
        &nbsp;&nbsp;<a href="logout.php" style="color:#dc2626; font-weight:600;">⏻ Logout</a>
    </div>
</div>

<div class="dashboard">
    <div class="card">
        <h3>📋 Katalog Alat Praktikum</h3>
        <table>
            <thead>
                <tr><th>Nama Barang</th><th>Stok Tersedia</th><th>Lokasi Laboratorium</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (empty($barangList)): ?>
                <tr><td colspan="4" class="empty-note">Belum ada data barang.</td></tr>
            <?php else: foreach ($barangList as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['nama_barang']); ?></td>
                    <td><?php echo (int) $b['stok']; ?> Unit</td>
                    <td><span class="badge badge-teal"><?php echo htmlspecialchars(strtoupper($b['lokasi_lab'])); ?></span></td>
                    <td>
                        <form method="POST" action="user.php" style="display:inline"
                              onsubmit="return confirmPinjam(event, '<?php echo htmlspecialchars(addslashes($b['nama_barang'])); ?>', this)">
                            <input type="hidden" name="action" value="pinjam">
                            <input type="hidden" name="barang_id" value="<?php echo (int) $b['id']; ?>">
                            <input type="hidden" name="tanggal_pinjam" class="tanggal-pinjam-field" value="">
                            <button type="submit" class="btn btn-sm" <?php echo ((int)$b['stok'] === 0) ? 'disabled style="opacity:.4;cursor:not-allowed;"' : ''; ?>>
                                ⇄ Pinjam
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>🕓 Riwayat Peminjaman Saya</h3>
        <table>
            <thead>
                <tr><th>Tanggal Pinjam</th><th>Nama Barang</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php if (empty($riwayat)): ?>
                <tr><td colspan="3" class="empty-note">Belum ada riwayat peminjaman.</td></tr>
            <?php else: foreach ($riwayat as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['tanggal_pinjam']); ?></td>
                    <td><?php echo htmlspecialchars($r['nama_barang']); ?></td>
                    <td><span class="badge <?php echo $badgeClass[$r['status']]; ?>"><?php echo $badgeLabel[$r['status']]; ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmPinjam(e, namaBarang, form) {
    e.preventDefault();

    // Ambil tanggal hari ini di device user (format YYYY-MM-DD) supaya
    // date picker tidak bisa memilih tanggal yang sudah lewat (atribut "min").
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayStr = `${yyyy}-${mm}-${dd}`;

    Swal.fire({
        icon: 'question',
        title: 'Konfirmasi Peminjaman',
        html: `Ajukan peminjaman untuk alat <b>${namaBarang}</b>?<br>Pilih tanggal pinjam:`,
        input: 'date',
        inputAttributes: {
            min: todayStr // tidak bisa pilih tanggal sebelum hari ini
        },
        inputValue: todayStr, // default terisi hari ini
        showCancelButton: true,
        confirmButtonText: 'Ya, Pinjam!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#2563eb',
        inputValidator: (value) => {
            if (!value) {
                return 'Tanggal pinjam wajib dipilih!';
            }
            if (value < todayStr) {
                return 'Tidak bisa memilih tanggal yang sudah lewat!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            form.querySelector('.tanggal-pinjam-field').value = result.value;
            form.submit();
        }
    });
    return false;
}

<?php if ($msg === 'pinjam_sukses'): ?>
Swal.fire({icon:'success', title:'Berhasil!', text:'Peminjaman berhasil diajukan! Menunggu persetujuan Aslab.', confirmButtonColor:'#2563eb'});
<?php elseif ($msg === 'pinjam_gagal'): ?>
Swal.fire({icon:'error', title:'Gagal!', text:'Stok barang habis, peminjaman tidak bisa diajukan.', confirmButtonColor:'#dc2626'});
<?php elseif ($msg === 'tanggal_invalid'): ?>
Swal.fire({icon:'error', title:'Tanggal Tidak Valid!', text:'Tanggal pinjam tidak boleh kosong atau tanggal yang sudah lewat.', confirmButtonColor:'#dc2626'});
<?php endif; ?>
</script>

</body>
</html>
