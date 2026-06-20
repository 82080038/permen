<?php
require '../config.php';
require '../helpers.php';

// Guard: only admin
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// Handle actions
$message = '';
$messageType = '';

// CSRF check for GET-based actions
$csrfToken = $_GET['token'] ?? '';
$actionRequested = isset($_GET['delete']) || isset($_GET['toggle_status']) || isset($_GET['reset_password']);
if ($actionRequested && !validateCsrf($csrfToken)) {
    $message = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    $messageType = 'danger';
} else {

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    // Cannot delete self or other admins
    $checkUser = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $checkUser->execute([$userId]);
    $targetUser = $checkUser->fetch();
    
    if (!$targetUser) {
        $message = 'Pengguna tidak ditemukan.';
        $messageType = 'danger';
    } elseif ($targetUser['role'] === 'admin') {
        $message = 'Tidak bisa menghapus akun admin.';
        $messageType = 'danger';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Delete related data
            $pdo->prepare("DELETE FROM answers WHERE session_id IN (SELECT id FROM tryout_sessions WHERE user_id = ?)")->execute([$userId]);
            $pdo->prepare("DELETE FROM session_subtes WHERE session_id IN (SELECT id FROM tryout_sessions WHERE user_id = ?)")->execute([$userId]);
            $pdo->prepare("DELETE FROM tryout_sessions WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM daily_quiz_answers WHERE session_id IN (SELECT id FROM daily_quiz_sessions WHERE user_id = ?)")->execute([$userId]);
            $pdo->prepare("DELETE FROM daily_quiz_sessions WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM daily_quiz_streaks WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM learning_analytics WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM learning_insights WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM materi_progress WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM materi_bookmarks WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM notification_preferences WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM push_subscriptions WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM personal_practice_sessions WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM scheduled_tryout_registrations WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM tryout_event_registrations WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM user_achievements WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM user_activity_log WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM user_audit_logs WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM user_feedback WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM user_quiz_difficulty WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM password_reset_requests WHERE user_id = ?")->execute([$userId]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            
            $pdo->commit();
            $message = 'Pengguna berhasil dihapus beserta seluruh datanya.';
            $messageType = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Gagal menghapus: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Toggle status (active/banned)
if (false) {
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $userId = (int)$_GET['toggle_status'];
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active','banned','active') WHERE id = ? AND role != 'admin'");
        $stmt->execute([$userId]);
        if ($stmt->rowCount() > 0) {
            $message = 'Status pengguna berhasil diubah.';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Gagal mengubah status: ' . $e->getMessage();
        $messageType = 'danger';
    }
}
} // End if (false) for toggle_status

// Reset password
if (false) {
    $userId = (int)$_GET['reset_password'];
    $newPassword = substr(str_shuffle('abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 10) . '!1';
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    try {
        // Update both columns for compatibility
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role != 'admin'");
        $stmt->execute([$hash, $userId]);
        if ($stmt->rowCount() > 0) {
            $message = "Password berhasil direset. Password baru: <strong>$newPassword</strong> (catat sekarang, tidak bisa dilihat lagi)";
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Gagal reset password: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

} // End CSRF check else block

// Search & filter
$search = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = "WHERE role = 'user'";
$params = [];

if ($search) {
    $where .= " AND (nama LIKE ? OR no_hp LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filterStatus) {
    $where .= " AND status = ?";
    $params[] = $filterStatus;
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users
$stmt = $pdo->prepare("SELECT id, nama, no_hp, email, sekolah_asal, instansi, status, created_at FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Stats
$statsTotal = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$statsActive = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user' AND status='active'")->fetchColumn();
$statsBanned = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user' AND status='banned'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<title>Kelola Pengguna — Admin SKD CAT-BKN</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f5f7fa;color:#222;line-height:1.6}
.container{max-width:1200px;margin:1rem auto;padding:0 1rem}
h1{color:#1a5276;margin-bottom:1rem;font-size:1.3rem}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.8rem;margin-bottom:1.5rem}
.stat-box{background:#fff;border-radius:8px;padding:1rem;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.stat-box .num{font-size:1.5rem;font-weight:bold;color:#2980b9}
.stat-box .label{font-size:.8rem;color:#666;margin-top:.2rem}
.filters{display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap;align-items:center}
.filters input,.filters select{padding:.5rem .7rem;border:1px solid #ddd;border-radius:6px;font-size:.85rem}
.filters input[type="text"]{flex:1;min-width:180px}
.btn{padding:.5rem 1rem;border:none;border-radius:6px;cursor:pointer;font-size:.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem}
.btn-primary{background:#2980b9;color:#fff}
.btn-danger{background:#e74c3c;color:#fff}
.btn-warning{background:#f39c12;color:#fff}
.btn-success{background:#27ae60;color:#fff}
.btn-sm{padding:.3rem .6rem;font-size:.75rem}
.alert{padding:.8rem 1rem;border-radius:6px;margin-bottom:1rem;font-size:.85rem}
.alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
.alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
.table-wrap{overflow-x:auto;background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.08)}
table{width:100%;border-collapse:collapse;font-size:.85rem;min-width:700px}
th,td{padding:.6rem .7rem;text-align:left;border-bottom:1px solid #eee}
th{background:#f8f9fa;color:#555;font-weight:600;white-space:nowrap}
tr:hover{background:#f8f9fa}
.badge{padding:.2rem .5rem;border-radius:4px;font-size:.7rem;font-weight:600}
.badge-active{background:#d4edda;color:#155724}
.badge-banned{background:#f8d7da;color:#721c24}
.pagination{display:flex;gap:.3rem;justify-content:center;margin-top:1rem;flex-wrap:wrap}
.pagination a,.pagination span{padding:.4rem .7rem;border-radius:4px;font-size:.8rem;text-decoration:none;border:1px solid #ddd}
.pagination a:hover{background:#2980b9;color:#fff;border-color:#2980b9}
.pagination .active{background:#2980b9;color:#fff;border-color:#2980b9}
.actions{display:flex;gap:.3rem;flex-wrap:wrap}
@media(max-width:768px){
  .filters{flex-direction:column}
  .filters input[type="text"]{width:100%}
}
</style>
</head>
<body>
<a href="#main-content" class="skip-link" style="position:absolute;top:-40px;left:0;background:#1a5276;color:#fff;padding:8px;z-index:1000">Lanjut ke konten utama</a>
<?php $pageTitle = 'Kelola Pengguna'; $activePage = 'admin_users'; ?>
<?php require '../includes/nav_admin.php'; ?>

<div class="container" id="main-content">
<h1>Kelola Pengguna</h1>

<?php if ($message): ?>
<div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
<?php endif; ?>

<div class="stats-row">
    <div class="stat-box">
        <div class="num"><?= $statsTotal ?></div>
        <div class="label">Total Peserta</div>
    </div>
    <div class="stat-box">
        <div class="num"><?= $statsActive ?></div>
        <div class="label">Aktif</div>
    </div>
    <div class="stat-box">
        <div class="num"><?= $statsBanned ?></div>
        <div class="label">Diblokir</div>
    </div>
</div>

<form method="get" class="filters">
    <input type="text" name="search" placeholder="Cari nama, no HP, email..." value="<?= e($search) ?>">
    <select name="status">
        <option value="">Semua Status</option>
        <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Aktif</option>
        <option value="banned" <?= $filterStatus === 'banned' ? 'selected' : '' ?>>Diblokir</option>
    </select>
    <button type="submit" class="btn btn-primary">Cari</button>
    <?php if ($search || $filterStatus): ?>
    <a href="admin_users.php" class="btn btn-warning">Reset</a>
    <?php endif; ?>
</form>

<div class="table-wrap">
<table>
<thead>
<tr>
    <th>No</th>
    <th>Nama</th>
    <th>No HP</th>
    <th>Email</th>
    <th>Asal</th>
    <th>Status</th>
    <th>Terdaftar</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>
<?php if (empty($users)): ?>
<tr><td colspan="8" style="text-align:center;padding:2rem;color:#999">Belum ada pengguna terdaftar.</td></tr>
<?php else: ?>
<?php foreach ($users as $i => $u): ?>
<tr>
    <td><?= $offset + $i + 1 ?></td>
    <td><strong><?= e($u['nama']) ?></strong></td>
    <td><?= e($u['no_hp']) ?></td>
    <td><?= e($u['email'] ?? '-') ?></td>
    <td><?= e($u['sekolah_asal'] ?? '-') ?></td>
    <td>
        <span class="badge badge-<?= $u['status'] ?? 'active' ?>">
            <?= ($u['status'] ?? 'active') === 'active' ? 'Aktif' : 'Diblokir' ?>
        </span>
    </td>
    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
    <td>
        <div class="actions">
            <a href="?toggle_status=<?= $u['id'] ?>&token=<?= e(csrfToken()) ?>" class="btn btn-sm <?= ($u['status'] ?? 'active') === 'active' ? 'btn-warning' : 'btn-success' ?>" onclick="return confirm('Ubah status pengguna ini?')" title="<?= ($u['status'] ?? 'active') === 'active' ? 'Blokir' : 'Aktifkan' ?>">
                <?= ($u['status'] ?? 'active') === 'active' ? '🚫' : '✅' ?>
            </a>
            <a href="?reset_password=<?= $u['id'] ?>&token=<?= e(csrfToken()) ?>" class="btn btn-sm btn-primary" onclick="return confirm('Reset password pengguna ini?')" title="Reset Password">🔑</a>
            <a href="?delete=<?= $u['id'] ?>&token=<?= e(csrfToken()) ?>" class="btn btn-sm btn-danger" onclick="return confirm('HAPUS pengguna ini beserta SELURUH datanya? Aksi ini tidak bisa dibatalkan!')" title="Hapus">🗑️</a>
        </div>
    </td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $filterStatus ?>">← Prev</a>
    <?php endif; ?>
    
    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
    <?php if ($p === $page): ?>
    <span class="active"><?= $p ?></span>
    <?php else: ?>
    <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= $filterStatus ?>"><?= $p ?></a>
    <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $filterStatus ?>">Next →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<div style="text-align:center;margin-top:1rem;font-size:.8rem;color:#888">
    Menampilkan <?= count($users) ?> dari <?= $totalUsers ?> pengguna
</div>

</div>

</body>
</html>
