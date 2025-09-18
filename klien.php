<?php
require_once 'config/database.php';
require_once 'models/Klien.php';
require_once 'models/User.php';
require_once 'includes/session.php';
require_once 'includes/layout_helper.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$klien = new Klien($db);

// Get users for iduser dropdown (exclude client role)
$user = new User($db);
$stmt = $db->prepare("SELECT id, nama, role FROM users WHERE role != 'client' AND status = 'aktif' ORDER BY nama ASC");
$stmt->execute();
$support_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$message_type = '';

// Get parameters for pagination, search, and sorting
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$pekerjaan_filter = isset($_GET['pekerjaan']) ? $_GET['pekerjaan'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'namaklien';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Records per page

// Validate limit (allowed values: 5, 10, 25, 50, 100)
$allowed_limits = [5, 10, 25, 50, 100];
if (!in_array($limit, $allowed_limits)) {
    $limit = 10;
}

// Validate sort parameters
$allowed_sort_fields = ['namaklien', 'alamatklien', 'tanggaltagihan', 'jumlahtagihan', 'status', 'created_at'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'namaklien';
}

if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

// Ensure page is at least 1
if($page < 1) $page = 1;

// Handle form submissions
if($_POST) {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'create':
                $klien->namaklien = $_POST['namaklien'];
                $klien->alamatklien = $_POST['alamatklien'];
                $klien->kotaklien = $_POST['kotaklien'];
                $klien->sistem = $_POST['sistem'];
                
                // Set default values for non-perawatan pekerjaan
                if($_POST['pekerjaan'] !== 'perawatan') {
                    $klien->tanggaltagihan = 0;
                    $klien->jumlahtagihan = 0;
                    $klien->keterangantagihan = '';
                } else {
                    $klien->tanggaltagihan = $_POST['tanggaltagihan'];
                    $klien->jumlahtagihan = (int)str_replace('.', '', $_POST['jumlahtagihan']);
                    $klien->keterangantagihan = $_POST['keterangantagihan'];
                }
                
                $klien->pekerjaan = $_POST['pekerjaan'];
                $klien->iduser = $_SESSION['user_id'];
                
                // Auto set status to non aktif if pekerjaan is selesai
                if($_POST['pekerjaan'] === 'selesai') {
                    $klien->status = 'non aktif';
                } else {
                    $klien->status = $_POST['status'];
                }
                
                if($klien->create()) {
                    $message = 'Data klien berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menambahkan data klien!';
                    $message_type = 'danger';
                }
                break;
                
            case 'update':
                $klien->id = $_POST['id'];
                $klien->namaklien = $_POST['namaklien'];
                $klien->alamatklien = $_POST['alamatklien'];
                $klien->kotaklien = $_POST['kotaklien'];
                $klien->sistem = $_POST['sistem'];
                
                // Set default values for non-perawatan pekerjaan
                if($_POST['pekerjaan'] !== 'perawatan') {
                    $klien->tanggaltagihan = 0;
                    $klien->jumlahtagihan = 0;
                    $klien->keterangantagihan = '';
                } else {
                    $klien->tanggaltagihan = $_POST['tanggaltagihan'];
                    $klien->jumlahtagihan = (int)str_replace('.', '', $_POST['jumlahtagihan']);
                    $klien->keterangantagihan = $_POST['keterangantagihan'];
                }
                
                $klien->pekerjaan = $_POST['pekerjaan'];
                $klien->iduser = $_POST['iduser'];
                
                // Auto set status to non aktif if pekerjaan is selesai
                if($_POST['pekerjaan'] === 'selesai') {
                    $klien->status = 'non aktif';
                } else {
                    $klien->status = $_POST['status'];
                }
                
                if($klien->update()) {
                    $message = 'Data klien berhasil diperbarui!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal memperbarui data klien!';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $klien->id = $_POST['id'];
                if($klien->delete()) {
                    $message = 'Data klien berhasil dihapus!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menghapus data klien!';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Function to generate sort URL
function getSortUrl($field, $current_sort, $current_order, $search, $status_filter, $pekerjaan_filter, $limit) {
    $new_order = ($current_sort == $field && $current_order == 'ASC') ? 'DESC' : 'ASC';
    $params = [
        'sort' => $field,
        'order' => $new_order,
        'search' => $search,
        'status' => $status_filter,
        'pekerjaan' => $pekerjaan_filter,
        'limit' => $limit
    ];
    return '?' . http_build_query($params);
}

// Get users for dropdown
$user = new User($db);
$users = $user->getAllUsers();

// Get klien data with pagination
$klien_data = $klien->getKlienWithPagination($page, $limit, $search, $status_filter, $pekerjaan_filter, $sort_by, $sort_order);
$total_klien = $klien->getTotalKlien($search, $status_filter, $pekerjaan_filter);
$total_pages = ceil($total_klien / $limit);

// Start layout with buffer
startLayoutBuffer('Data Klien - Logics Software');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Data Klien</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKlienModal">
            <i class="fas fa-plus"></i> Tambah Klien
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Cari Klien</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nama klien atau sistem...">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="aktif" <?php echo $status_filter === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="non aktif" <?php echo $status_filter === 'non aktif' ? 'selected' : ''; ?>>Non Aktif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="pekerjaan" class="form-label">Pekerjaan</label>
                    <select class="form-select" id="pekerjaan" name="pekerjaan">
                        <option value="">Semua Pekerjaan</option>
                        <option value="perawatan" <?php echo $pekerjaan_filter === 'perawatan' ? 'selected' : ''; ?>>Perawatan</option>
                        <option value="develop" <?php echo $pekerjaan_filter === 'develop' ? 'selected' : ''; ?>>Develop</option>
                        <option value="selesai" <?php echo $pekerjaan_filter === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="limit" class="form-label">Limit</label>
                    <select class="form-select" id="limit" name="limit">
                        <option value="5" <?php echo $limit === 5 ? 'selected' : ''; ?>>5</option>
                        <option value="10" <?php echo $limit === 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $limit === 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <a href="solving.php" class="btn btn-secondary">
                            <i class="fas fa-refresh"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th><a href="<?php echo getSortUrl('namaklien', $sort_by, $sort_order, $search, $status_filter, $pekerjaan_filter, $limit); ?>" class="text-white text-decoration-none">Nama Klien <?php echo $sort_by == 'namaklien' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('alamatklien', $sort_by, $sort_order, $search, $status_filter, $pekerjaan_filter, $limit); ?>" class="text-white text-decoration-none">Alamat <?php echo $sort_by == 'alamatklien' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th>Pekerjaan</th>
                            <th>Support User</th>
                            <th><a href="<?php echo getSortUrl('tanggaltagihan', $sort_by, $sort_order, $search, $status_filter, $pekerjaan_filter, $limit); ?>" class="text-white text-decoration-none">Tanggal <?php echo $sort_by == 'tanggaltagihan' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th>Jumlah Tagihan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($klien_data)): ?>
                            <tr>
                                <td colspan="12" class="text-center">Tidak ada data klien</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($klien_data as $index => $k): ?>
                                <tr>
                                    <td><?php echo ($page - 1) * $limit + $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($k['namaklien']); ?></td>
                                    <td><?php echo htmlspecialchars($k['kotaklien']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $k['pekerjaan'] === 'perawatan' ? 'primary' : ($k['pekerjaan'] === 'develop' ? 'warning' : 'success'); ?>">
                                            <?php echo ucfirst($k['pekerjaan']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(!empty($k['nama_user'])): ?>
                                            <span class="badge bg-info" title="<?php echo htmlspecialchars($k['nama_user']); ?>">
                                                <?php echo htmlspecialchars($k['nama_user']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($k['tanggaltagihan'] > 0): ?>
                                            <?php echo $k['tanggaltagihan']; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($k['jumlahtagihan'] > 0): ?>
                                            Rp <?php echo number_format($k['jumlahtagihan'], 0, ',', '.'); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $k['status'] === 'aktif' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($k['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-btn" 
                                                data-klien='<?php echo json_encode($k, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                                                data-id="<?php echo $k['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($k['namaklien']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&pekerjaan=<?php echo urlencode($pekerjaan_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&limit=<?php echo $limit; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&pekerjaan=<?php echo urlencode($pekerjaan_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&limit=<?php echo $limit; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&pekerjaan=<?php echo urlencode($pekerjaan_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&limit=<?php echo $limit; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Klien Modal -->
<div class="modal fade" id="addKlienModal" tabindex="-1" aria-labelledby="addKlienModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addKlienModalLabel">Tambah Klien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="namaklien" class="form-label">Nama Klien <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="namaklien" name="namaklien" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kotaklien" class="form-label">Kota <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kotaklien" name="kotaklien" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="alamatklien" class="form-label">Alamat <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="alamatklien" name="alamatklien" rows="1" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sistem" class="form-label">Sistem <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sistem" name="sistem" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pekerjaan" class="form-label">Pekerjaan <span class="text-danger">*</span></label>
                            <select class="form-select" id="pekerjaan" name="pekerjaan" required onchange="handlePekerjaanChange(this)">
                                <option value="">Pilih Pekerjaan</option>
                                <option value="perawatan">Perawatan</option>
                                <option value="develop">Develop</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Fields for perawatan -->
                    <div class="row" id="tanggal_tagihan_field">
                        <div class="col-md-6 mb-3">
                            <label for="tanggaltagihan" class="form-label">Tanggal Tagihan (1-31)</label>
                            <input type="number" class="form-control" id="tanggaltagihan" name="tanggaltagihan" min="1" max="31">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="jumlahtagihan" class="form-label">Jumlah Tagihan</label>
                            <input type="text" class="form-control" id="jumlahtagihan" name="jumlahtagihan" placeholder="0">
                        </div>
                    </div>
                    
                    <div class="mb-3" id="keterangan_tagihan_field">
                        <label for="keterangantagihan" class="form-label">Keterangan Tagihan</label>
                        <textarea class="form-control" id="keterangantagihan" name="keterangantagihan" rows="1"></textarea>
                    </div>
                    
                    <!-- Hidden inputs for default values -->
                    <input type="hidden" id="hidden_tanggaltagihan" name="tanggaltagihan" value="0">
                    <input type="hidden" id="hidden_jumlahtagihan" name="jumlahtagihan" value="0">
                    <input type="hidden" id="hidden_keterangantagihan" name="keterangantagihan" value="">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="iduser" class="form-label">Support User</label>
                            <select class="form-select" id="iduser" name="iduser">
                                <option value="">Pilih Support User</option>
                                <?php foreach($support_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['nama']); ?> (<?php echo ucfirst($user['role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="aktif">Aktif</option>
                                <option value="non aktif">Non Aktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Klien Modal -->
<div class="modal fade" id="editKlienModal" tabindex="-1" aria-labelledby="editKlienModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editKlienModalLabel">Edit Klien</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="iduser" id="edit_iduser">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_namaklien" class="form-label">Nama Klien <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_namaklien" name="namaklien" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_kotaklien" class="form-label">Kota <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_kotaklien" name="kotaklien" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_alamatklien" class="form-label">Alamat <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_alamatklien" name="alamatklien" rows="1" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_sistem" class="form-label">Sistem <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_sistem" name="sistem" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_pekerjaan" class="form-label">Pekerjaan <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_pekerjaan" name="pekerjaan" required onchange="handlePekerjaanChange(this, true)">
                                <option value="">Pilih Pekerjaan</option>
                                <option value="perawatan">Perawatan</option>
                                <option value="develop">Develop</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Fields for perawatan -->
                    <div class="row" id="edit_tanggal_tagihan_field">
                        <div class="col-md-6 mb-3">
                            <label for="edit_tanggaltagihan" class="form-label">Tanggal Tagihan (1-31)</label>
                            <input type="number" class="form-control" id="edit_tanggaltagihan" name="tanggaltagihan" min="1" max="31">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_jumlahtagihan" class="form-label">Jumlah Tagihan</label>
                            <input type="text" class="form-control" id="edit_jumlahtagihan" name="jumlahtagihan" placeholder="0">
                        </div>
                    </div>
                    
                    <div class="mb-3" id="edit_keterangan_tagihan_field">
                        <label for="edit_keterangantagihan" class="form-label">Keterangan Tagihan</label>
                        <textarea class="form-control" id="edit_keterangantagihan" name="keterangantagihan" rows="1"></textarea>
                    </div>
                    
                    <!-- Hidden inputs for default values -->
                    <input type="hidden" id="hidden_edit_tanggaltagihan" name="tanggaltagihan" value="0">
                    <input type="hidden" id="hidden_edit_jumlahtagihan" name="jumlahtagihan" value="0">
                    <input type="hidden" id="hidden_edit_keterangantagihan" name="keterangantagihan" value="">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_iduser_select" class="form-label">Support User</label>
                            <select class="form-select" id="edit_iduser_select" name="iduser">
                                <option value="">Pilih Support User</option>
                                <?php foreach($support_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['nama']); ?> (<?php echo ucfirst($user['role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="aktif">Aktif</option>
                                <option value="non aktif">Non Aktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus klien <strong id="deleteKlienName"></strong>?</p>
                <p class="text-danger">Tindakan ini tidak dapat dibatalkan!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteKlienId">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Alert Modal -->
<div class="modal fade alert-modal" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertModalLabel">Pemberitahuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-info-circle text-primary modal-icon-large"></i>
                    <p id="alertMessage" class="mb-0"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>


<script>
// Format number input with thousand separators
document.addEventListener('DOMContentLoaded', function() {
    // Format jumlahtagihan input
    const jumlahtagihanInputs = document.querySelectorAll('#jumlahtagihan, #edit_jumlahtagihan');
    jumlahtagihanInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/[^\d]/g, '');
            if (value) {
                this.value = parseInt(value).toLocaleString('id-ID');
                // Update hidden input with clean value
                const hiddenInput = this.id === 'jumlahtagihan' ? 
                    document.getElementById('hidden_jumlahtagihan') : 
                    document.getElementById('hidden_edit_jumlahtagihan');
                if (hiddenInput) {
                    hiddenInput.value = value;
                }
            } else {
                // Update hidden input with 0 if empty
                const hiddenInput = this.id === 'jumlahtagihan' ? 
                    document.getElementById('hidden_jumlahtagihan') : 
                    document.getElementById('hidden_edit_jumlahtagihan');
                if (hiddenInput) {
                    hiddenInput.value = '0';
                }
            }
        });
    });

    // Clean number inputs before form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            // Clean jumlahtagihan inputs
            const jumlahtagihanInputs = this.querySelectorAll('#jumlahtagihan, #edit_jumlahtagihan');
            jumlahtagihanInputs.forEach(input => {
                if (input.value) {
                    input.value = input.value.replace(/[^\d]/g, '');
                }
            });
        });
    });

    // Handle delete button clicks
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            document.getElementById('deleteKlienId').value = id;
            document.getElementById('deleteKlienName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

    // Handle edit button clicks
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const klienData = JSON.parse(this.getAttribute('data-klien'));
            editKlien(klienData);
        });
    });
});

function editKlien(klien) {
    try {
        document.getElementById('edit_id').value = klien.id;
        document.getElementById('edit_namaklien').value = klien.namaklien;
        document.getElementById('edit_alamatklien').value = klien.alamatklien;
        document.getElementById('edit_kotaklien').value = klien.kotaklien;
        document.getElementById('edit_sistem').value = klien.sistem;
        document.getElementById('edit_tanggaltagihan').value = klien.tanggaltagihan;
        document.getElementById('edit_jumlahtagihan').value = klien.jumlahtagihan ? parseInt(klien.jumlahtagihan).toLocaleString('id-ID') : '';
        document.getElementById('edit_keterangantagihan').value = klien.keterangantagihan;
        document.getElementById('edit_pekerjaan').value = klien.pekerjaan;
        document.getElementById('edit_iduser').value = klien.iduser;
        document.getElementById('edit_iduser_select').value = klien.iduser;
        document.getElementById('edit_status').value = klien.status;
        
        handlePekerjaanChange(document.getElementById('edit_pekerjaan'), true);
        
        const modal = new bootstrap.Modal(document.getElementById('editKlienModal'));
        modal.show();
    } catch (error) {
        showAlertModal('Error: ' + error.message);
    }
}

function handlePekerjaanChange(selectElement, isEditForm = false) {
    // Get elements based on form type
    const tanggalTagihanField = isEditForm ? 
        document.getElementById('edit_tanggal_tagihan_field') : 
        document.getElementById('tanggal_tagihan_field');
    const jumlahTagihanField = isEditForm ? 
        document.getElementById('edit_jumlahtagihan').closest('.mb-3') : 
        document.getElementById('jumlahtagihan').closest('.mb-3');
    const keteranganTagihanField = isEditForm ? 
        document.getElementById('edit_keterangan_tagihan_field') : 
        document.getElementById('keterangan_tagihan_field');
    
    const tanggalTagihanInput = isEditForm ? 
        document.getElementById('edit_tanggaltagihan') : 
        document.getElementById('tanggaltagihan');
    const jumlahTagihanInput = isEditForm ? 
        document.getElementById('edit_jumlahtagihan') : 
        document.getElementById('jumlahtagihan');
    const keteranganTagihanElement = isEditForm ? 
        document.getElementById('edit_keterangantagihan') : 
        document.getElementById('keterangantagihan');
    const keteranganTagihanInput = keteranganTagihanElement;
    
    const statusSelect = isEditForm ? 
        document.getElementById('edit_status') : 
        document.getElementById('status');
    
    // Show/hide fields based on pekerjaan value
    
    // Handle status logic
    if (statusSelect) {
        if (selectElement.value === 'selesai') {
            statusSelect.value = 'non aktif';
            statusSelect.disabled = true;
            statusSelect.style.backgroundColor = '#f8f9fa';
        } else {
            statusSelect.disabled = false;
            statusSelect.style.backgroundColor = '';
        }
    }
    
    // Handle field visibility and default values
    if (selectElement.value === 'perawatan') {
        // Show fields for perawatan
        if (tanggalTagihanField) tanggalTagihanField.style.display = 'flex';
        if (jumlahTagihanField) jumlahTagihanField.style.display = 'block';
        if (keteranganTagihanField) keteranganTagihanField.style.display = 'block';
        
        // Disable hidden inputs
        const hiddenTanggal = isEditForm ? 
            document.getElementById('hidden_edit_tanggaltagihan') : 
            document.getElementById('hidden_tanggaltagihan');
        const hiddenJumlah = isEditForm ? 
            document.getElementById('hidden_edit_jumlahtagihan') : 
            document.getElementById('hidden_jumlahtagihan');
        const hiddenKeterangan = isEditForm ? 
            document.getElementById('hidden_edit_keterangantagihan') : 
            document.getElementById('hidden_keterangantagihan');
        
        if (hiddenTanggal) hiddenTanggal.disabled = true;
        if (hiddenJumlah) hiddenJumlah.disabled = true;
        if (hiddenKeterangan) hiddenKeterangan.disabled = true;
    } else {
        // Hide fields and set default values for non-perawatan
        if (tanggalTagihanField) tanggalTagihanField.style.display = 'none';
        if (jumlahTagihanField) jumlahTagihanField.style.display = 'none';
        if (keteranganTagihanField) keteranganTagihanField.style.display = 'none';
        
        // Set default values for visible inputs
        if (tanggalTagihanInput) tanggalTagihanInput.value = '0';
        if (jumlahTagihanInput) jumlahTagihanInput.value = '0';
        if (keteranganTagihanInput) keteranganTagihanInput.value = '';
        
        // Enable and set hidden inputs
        const hiddenTanggal = isEditForm ? 
            document.getElementById('hidden_edit_tanggaltagihan') : 
            document.getElementById('hidden_tanggaltagihan');
        const hiddenJumlah = isEditForm ? 
            document.getElementById('hidden_edit_jumlahtagihan') : 
            document.getElementById('hidden_jumlahtagihan');
        const hiddenKeterangan = isEditForm ? 
            document.getElementById('hidden_edit_keterangantagihan') : 
            document.getElementById('hidden_keterangantagihan');
        
        if (hiddenTanggal) {
            hiddenTanggal.disabled = false;
            hiddenTanggal.value = '0';
        }
        if (hiddenJumlah) {
            hiddenJumlah.disabled = false;
            hiddenJumlah.value = '0';
        }
        if (hiddenKeterangan) {
            hiddenKeterangan.disabled = false;
            hiddenKeterangan.value = '';
        }
    }
}

// Function to show alert modal
function showAlertModal(message) {
    document.getElementById('alertMessage').textContent = message;
    const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
    alertModal.show();
}
</script>

<?php
// End layout
endLayout();
?>
