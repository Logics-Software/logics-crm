<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Klien.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Klien model
$klien = new Klien($db);

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Validate limit
$allowed_limits = [5, 10, 25, 50, 100];
if (!in_array($limit, $allowed_limits)) {
    $limit = 10;
}

// Validate sort parameters
$allowed_sort_fields = ['namaklien', 'alamatklien', 'kotaklien', 'sistem', 'pekerjaan', 'status', 'created_at'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'created_at';
}

if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

// Get klien data with pagination
$stmt = $klien->getKlienWithPagination($page, $limit, $search, '', '', $sort_by, $sort_order);
$klien_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$total_klien = $klien->getTotalKlien($search);
$total_pages = ceil($total_klien / $limit);

// Get current user info
$current_user = new User($db);
$current_user->getUserById($_SESSION['user_id']);

// Start output buffering
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Daftar Klien</h1>
    </div>
    
    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Cari Klien</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nama, alamat, kota, atau sistem...">
                </div>
                <div class="col-md-2">
                    <label for="limit" class="form-label">Limit</label>
                    <select class="form-select" id="limit" name="limit" onchange="this.form.submit()">
                        <?php foreach($allowed_limits as $l): ?>
                            <option value="<?php echo $l; ?>" <?php echo $limit == $l ? 'selected' : ''; ?>>
                                <?php echo $l; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <a href="daftar_klien.php" class="btn btn-secondary">
                            <i class="fas fa-refresh"></i> Reset
                        </a>    
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Klien Data Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($klien_data)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada data klien</h5>
                    <p class="text-muted">Belum ada data klien yang tersedia.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>
                                    <a href="<?php echo getSortUrl('namaklien', $sort_by, $sort_order); ?>" 
                                       class="text-white text-decoration-none">
                                        Nama Klien
                                        <?php if ($sort_by == 'namaklien'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Alamat</th>
                                <th>
                                    <a href="<?php echo getSortUrl('sistem', $sort_by, $sort_order); ?>" 
                                       class="text-white text-decoration-none">
                                        Sistem
                                        <?php if ($sort_by == 'sistem'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortUrl('pekerjaan', $sort_by, $sort_order); ?>" 
                                       class="text-white text-decoration-none">
                                        Pekerjaan
                                        <?php if ($sort_by == 'pekerjaan'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?php echo getSortUrl('status', $sort_by, $sort_order); ?>" 
                                       class="text-white text-decoration-none">
                                        Status
                                        <?php if ($sort_by == 'status'): ?>
                                            <i class="fas fa-sort-<?php echo $sort_order == 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $start_number = ($page - 1) * $limit + 1;
                            foreach ($klien_data as $index => $k): 
                            ?>
                                <tr>
                                    <td><?php echo $start_number + $index; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($k['namaklien']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?php echo htmlspecialchars($k['alamatklien'] ?: '-'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-primary">
                                            <?php echo htmlspecialchars($k['sistem'] ?: '-'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $k['pekerjaan'] === 'perawatan' ? 'warning' : 
                                                ($k['pekerjaan'] === 'develop' ? 'info' : 'success'); 
                                        ?>">
                                            <?php echo ucfirst($k['pekerjaan']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $k['status'] === 'aktif' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($k['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Klien pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo getPaginationUrl($page - 1); ?>">
                                        <i class="fas fa-chevron-left"></i> Sebelumnya
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo getPaginationUrl($i); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo getPaginationUrl($page + 1); ?>">
                                        Selanjutnya <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Helper functions
function getSortUrl($field, $current_sort, $current_order) {
    $params = $_GET;
    $params['sort_by'] = $field;
    $params['sort_order'] = ($current_sort == $field && $current_order == 'ASC') ? 'DESC' : 'ASC';
    return '?' . http_build_query($params);
}

function getPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// Get the buffered content
$content = ob_get_clean();

// Include layout
include 'includes/layout.php';
?>
