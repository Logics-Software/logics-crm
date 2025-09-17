<?php
require_once 'config/database.php';
require_once 'models/Komplain.php';
require_once 'models/User.php';
require_once 'models/Klien.php';
require_once 'includes/session.php';
require_once 'includes/layout_helper.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$komplain = new Komplain($db);
$user = new User($db);
$klien = new Klien($db);

$message = '';
$message_type = '';

// Get current user info
$current_user = new User($db);
$current_user->getUserById($_SESSION['user_id']);

// Get parameters for pagination, search, and sorting
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Validate limit
$allowed_limits = [5, 10, 25, 50, 100];
if (!in_array($limit, $allowed_limits)) {
    $limit = 10;
}

// Validate sort parameters
$allowed_sort_fields = ['subyek', 'status', 'created_at', 'updated_at', 'nama_support', 'nama_klien'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'created_at';
}

if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

// Ensure page is at least 1
if($page < 1) $page = 1;

// Get komplain data based on user role and job
if ($current_user->role === 'admin') {
    $komplain_data = $komplain->getKomplainWithPagination($page, $limit, $search, $status_filter, $sort_by, $sort_order);
    $total_komplain = $komplain->getTotalKomplain($search, $status_filter);
} else {
    $komplain_data = $komplain->getKomplainByUser($_SESSION['user_id'], $current_user->role, $page, $limit, $current_user->support);
    $total_komplain = $komplain->getTotalKomplain($search, $status_filter);
}

$total_pages = ceil($total_komplain / $limit);

// Function to generate sort URL
function getSortUrl($field, $current_sort, $current_order, $search, $status_filter, $limit) {
    $new_order = ($current_sort == $field && $current_order == 'ASC') ? 'DESC' : 'ASC';
    $params = [
        'sort' => $field,
        'order' => $new_order,
        'search' => $search,
        'status' => $status_filter,
        'limit' => $limit
    ];
    return 'komplain.php?' . http_build_query($params);
}

// Handle form submissions
if($_POST) {
    $action = $_POST['action'];
    
    switch($action) {
        case 'create':
            $komplain->subyek = $_POST['subyek'];
            $komplain->kompain = $_POST['kompain'];
            // Support user otomatis dari session login
            $komplain->idsupport = $_SESSION['user_id'];
            $komplain->idklien = $_POST['idklien'];
            // Status otomatis "komplain"
            $komplain->status = 'komplain';
            
            // Handle image uploads
            $images = [];
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/komplain/images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array(strtolower($file_extension), $allowed_extensions)) {
                            // Check file size (5MB max)
                            if ($_FILES['images']['size'][$i] <= 5 * 1024 * 1024) {
                                $filename = uniqid() . '_' . time() . '.' . $file_extension;
                                $upload_path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_path)) {
                                    $images[] = $filename;
                                }
                            }
                        }
                    }
                }
            }
            $komplain->image = json_encode($images);
            
            // Handle file uploads
            $files = [];
            if (!empty($_FILES['uploadfiles']['name'][0])) {
                $upload_dir = 'uploads/komplain/files/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                for ($i = 0; $i < count($_FILES['uploadfiles']['name']); $i++) {
                    if ($_FILES['uploadfiles']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_extension = pathinfo($_FILES['uploadfiles']['name'][$i], PATHINFO_EXTENSION);
                        $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
                        
                        if (in_array(strtolower($file_extension), $allowed_extensions)) {
                            // Check file size (10MB max)
                            if ($_FILES['uploadfiles']['size'][$i] <= 10 * 1024 * 1024) {
                                $filename = uniqid() . '_' . time() . '.' . $file_extension;
                                $upload_path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($_FILES['uploadfiles']['tmp_name'][$i], $upload_path)) {
                                    $files[] = $filename;
                                }
                            }
                        }
                    }
                }
            }
            $komplain->uploadfile = json_encode($files);
            
            if($komplain->create()) {
                // Redirect untuk mencegah resubmission
                header('Location: komplain.php?success=create');
                exit();
            } else {
                $message = 'Gagal membuat komplain!';
                $message_type = 'danger';
            }
            break;
            
        case 'update':
            $komplain->id = $_POST['id'];
            $komplain->subyek = $_POST['subyek'];
            $komplain->kompain = $_POST['kompain'];
            $komplain->idsupport = $_POST['idsupport'];
            $komplain->idklien = $_POST['idklien'];
            // Status tidak bisa diupdate - tetap dari database
            $existing_komplain = new Komplain($db);
            $existing_komplain->getKomplainById($komplain->id);
            $komplain->status = $existing_komplain->status;
            
            // Handle image uploads
            $images = [];
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/komplain/images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array(strtolower($file_extension), $allowed_extensions)) {
                            // Check file size (5MB max)
                            if ($_FILES['images']['size'][$i] <= 5 * 1024 * 1024) {
                                $filename = uniqid() . '_' . time() . '.' . $file_extension;
                                $upload_path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_path)) {
                                    $images[] = $filename;
                                }
                            }
                        }
                    }
                }
            }
            
            // Keep existing images if no new ones uploaded
            if (empty($images)) {
                $existing_komplain = new Komplain($db);
                $existing_komplain->getKomplainById($komplain->id);
                $images = json_decode($existing_komplain->image, true) ?: [];
            }
            $komplain->image = json_encode($images);
            
            // Handle file uploads
            $files = [];
            if (!empty($_FILES['uploadfiles']['name'][0])) {
                $upload_dir = 'uploads/komplain/files/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                for ($i = 0; $i < count($_FILES['uploadfiles']['name']); $i++) {
                    if ($_FILES['uploadfiles']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_extension = pathinfo($_FILES['uploadfiles']['name'][$i], PATHINFO_EXTENSION);
                        $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
                        
                        if (in_array(strtolower($file_extension), $allowed_extensions)) {
                            // Check file size (10MB max)
                            if ($_FILES['uploadfiles']['size'][$i] <= 10 * 1024 * 1024) {
                                $filename = uniqid() . '_' . time() . '.' . $file_extension;
                                $upload_path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($_FILES['uploadfiles']['tmp_name'][$i], $upload_path)) {
                                    $files[] = $filename;
                                }
                            }
                        }
                    }
                }
            }
            
            // Keep existing files if no new ones uploaded
            if (empty($files)) {
                $existing_komplain = new Komplain($db);
                $existing_komplain->getKomplainById($komplain->id);
                $files = json_decode($existing_komplain->uploadfile, true) ?: [];
            }
            $komplain->uploadfile = json_encode($files);
            
            if($komplain->update()) {
                // Redirect untuk mencegah resubmission
                header('Location: komplain.php?success=update');
                exit();
            } else {
                $message = 'Gagal memperbarui komplain!';
                $message_type = 'danger';
            }
            break;
            
        case 'delete':
            $komplain->id = $_POST['id'];
            if($komplain->delete()) {
                // Redirect untuk mencegah resubmission
                header('Location: komplain.php?success=delete');
                exit();
            } else {
                $message = 'Gagal menghapus komplain!';
                $message_type = 'danger';
            }
            break;
    }
}

// Handle success messages from redirect
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'create':
            $message = 'Komplain berhasil dibuat!';
            $message_type = 'success';
            break;
        case 'update':
            $message = 'Komplain berhasil diperbarui!';
            $message_type = 'success';
            break;
        case 'delete':
            $message = 'Komplain berhasil dihapus!';
            $message_type = 'success';
            break;
    }
}

// Get support users and clients for dropdowns
$support_users = [];
$clients = [];

if ($current_user->role === 'admin') {
    // Get support users (role support OR job support)
    $stmt = $db->prepare("SELECT id, nama FROM users WHERE (role = 'support' OR support = 1) AND status = 'aktif' ORDER BY nama ASC");
    $stmt->execute();
    $support_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get clients
    $stmt = $db->prepare("SELECT id, namaklien FROM klien WHERE status = 'aktif' ORDER BY namaklien ASC");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($current_user->role === 'support' || $current_user->support == 1) {
    // Get clients for support user
    $stmt = $db->prepare("SELECT id, namaklien FROM klien WHERE status = 'aktif' ORDER BY namaklien ASC");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($current_user->role === 'client') {
    // Get support users for client (role support OR job support)
    $stmt = $db->prepare("SELECT id, nama FROM users WHERE (role = 'support' OR support = 1) AND status = 'aktif' ORDER BY nama ASC");
    $stmt->execute();
    $support_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Start layout
startLayoutBuffer('Komplain');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Data Komplain</h1>
        <?php if ($current_user->role === 'admin' || $current_user->role === 'support' || $current_user->support == 1 || $current_user->role === 'client'): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addKomplainModal">
            <i class="fas fa-plus"></i> Tambah Komplain
        </button>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Cari Komplain</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Subyek, komplain, support, atau klien...">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="komplain" <?php echo $status_filter === 'komplain' ? 'selected' : ''; ?>>Komplain</option>
                        <option value="proses" <?php echo $status_filter === 'proses' ? 'selected' : ''; ?>>Proses</option>
                        <option value="feedback" <?php echo $status_filter === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                        <option value="selesai" <?php echo $status_filter === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="limit" class="form-label">Limit</label>
                    <select class="form-select" id="limit" name="limit" onchange="this.form.submit()">
                        <option value="5" <?php echo $limit == 5 ? 'selected' : ''; ?>>5</option>
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <a href="komplain.php" class="btn btn-secondary">
                            <i class="fas fa-refresh"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Komplain Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th><a href="<?php echo getSortUrl('subyek', $sort_by, $sort_order, $search, $status_filter, $limit); ?>" class="text-white text-decoration-none">Subyek <?php echo $sort_by == 'subyek' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th>Support</th>
                            <th>Klien</th>
                            <th><a href="<?php echo getSortUrl('status', $sort_by, $sort_order, $search, $status_filter, $limit); ?>" class="text-white text-decoration-none">Status <?php echo $sort_by == 'status' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('created_at', $sort_by, $sort_order, $search, $status_filter, $limit); ?>" class="text-white text-decoration-none">Tanggal <?php echo $sort_by == 'created_at' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($komplain_data->rowCount() == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data komplain</td>
                            </tr>
                        <?php else: ?>
                            <?php while($k = $komplain_data->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo ($page - 1) * $limit + $komplain_data->rowCount(); ?></td>
                                    <td><?php echo htmlspecialchars($k['subyek']); ?></td>
                                    <td>
                                        <?php if(!empty($k['nama_support'])): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($k['nama_support']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($k['nama_klien'])): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($k['nama_klien']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $k['status'] === 'komplain' ? 'warning' : 
                                                ($k['status'] === 'proses' ? 'info' : 
                                                    ($k['status'] === 'feedback' ? 'primary' : 'success')); 
                                        ?>">
                                            <?php echo ucfirst($k['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($k['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info view-btn" 
                                                data-komplain='<?php echo json_encode($k, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($current_user->role === 'admin' || (($current_user->role === 'support' || $current_user->support == 1) && $k['idsupport'] == $_SESSION['user_id']) || ($current_user->role === 'client' && $k['idklien'] == $_SESSION['user_id'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-btn" 
                                                data-komplain='<?php echo json_encode($k, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($current_user->role === 'admin'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                                                data-id="<?php echo $k['id']; ?>" 
                                                data-subyek="<?php echo htmlspecialchars($k['subyek']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&limit=<?php echo $limit; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&limit=<?php echo $limit; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&limit=<?php echo $limit; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Komplain Modal -->
<div class="modal fade" id="addKomplainModal" tabindex="-1" aria-labelledby="addKomplainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addKomplainModalLabel">Tambah Komplain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="subyek" class="form-label">Subyek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subyek" name="subyek" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="idklien" class="form-label">Klien <span class="text-danger">*</span></label>
                            <select class="form-select" id="idklien" name="idklien" required>
                                <option value="">Pilih Klien</option>
                                <?php foreach($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['namaklien']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="kompain" class="form-label">Komplain <span class="text-danger">*</span></label>
                        <div id="kompain" class="ql-editor"></div>
                        <textarea name="kompain" style="display: none;"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="images" class="form-label">Upload Gambar</label>
                            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF, WebP (Max 5MB per file)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="uploadfiles" class="form-label">Upload File</label>
                            <input type="file" class="form-control" id="uploadfiles" name="uploadfiles[]" multiple>
                            <small class="text-muted">Format: PDF, DOC, XLS, TXT, ZIP, RAR (Max 10MB per file)</small>
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

<!-- Edit Komplain Modal -->
<div class="modal fade" id="editKomplainModal" tabindex="-1" aria-labelledby="editKomplainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editKomplainModalLabel">Edit Komplain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_subyek" class="form-label">Subyek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_subyek" name="subyek" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_idsupport" class="form-label">Support User</label>
                            <select class="form-select" id="edit_idsupport" name="idsupport">
                                <option value="">Pilih Support User</option>
                                <?php foreach($support_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_idklien" class="form-label">Klien</label>
                            <select class="form-select" id="edit_idklien" name="idklien">
                                <option value="">Pilih Klien</option>
                                <?php foreach($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['namaklien']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_kompain" class="form-label">Komplain <span class="text-danger">*</span></label>
                        <div id="edit_kompain" class="ql-editor"></div>
                        <textarea name="kompain" style="display: none;"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_images" class="form-label">Upload Gambar Baru</label>
                            <input type="file" class="form-control" id="edit_images" name="images[]" multiple accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF, WebP (Max 5MB per file)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_uploadfiles" class="form-label">Upload File Baru</label>
                            <input type="file" class="form-control" id="edit_uploadfiles" name="uploadfiles[]" multiple>
                            <small class="text-muted">Format: PDF, DOC, XLS, TXT, ZIP, RAR (Max 10MB per file)</small>
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

<!-- View Komplain Modal -->
<div class="modal fade" id="viewKomplainModal" tabindex="-1" aria-labelledby="viewKomplainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewKomplainModalLabel">Detail Komplain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Subyek:</h6>
                        <p id="view_subyek"></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Status:</h6>
                        <p id="view_status"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Support User:</h6>
                        <p id="view_support"></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Klien:</h6>
                        <p id="view_klien"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Tanggal Dibuat:</h6>
                        <p id="view_created_at"></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Tanggal Diupdate:</h6>
                        <p id="view_updated_at"></p>
                    </div>
                </div>
                <div class="mb-3">
                    <h6>Komplain:</h6>
                    <div id="view_kompain" class="border p-3 rounded"></div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Gambar:</h6>
                        <div id="view_images"></div>
                    </div>
                    <div class="col-md-6">
                        <h6>File:</h6>
                        <div id="view_files"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
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
                <p>Apakah Anda yakin ingin menghapus komplain "<span id="delete_subyek"></span>"?</p>
                <p class="text-danger">Tindakan ini tidak dapat dibatalkan!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editors
    let kompainQuill, editKompainQuill;
    
    const quillConfig = {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['link', 'image', 'video'],
                ['clean']
            ]
        },
        placeholder: 'Ketik komplain Anda di sini...'
    };
    
    // Initialize create form Quill
    if (document.getElementById('kompain')) {
        kompainQuill = new Quill('#kompain', quillConfig);
    }
    
    // Initialize edit form Quill
    if (document.getElementById('edit_kompain')) {
        editKompainQuill = new Quill('#edit_kompain', quillConfig);
    }
    
    // Handle form submission
    const addForm = document.querySelector('#addKomplainModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const kompainTextarea = addForm.querySelector('textarea[name="kompain"]');
            kompainTextarea.value = kompainQuill.root.innerHTML;
        });
    }
    
    const editForm = document.querySelector('#editKomplainModal form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const kompainTextarea = editForm.querySelector('textarea[name="kompain"]');
            kompainTextarea.value = editKompainQuill.root.innerHTML;
        });
    }
    
    // Edit button click
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const komplain = JSON.parse(this.dataset.komplain);
            editKomplain(komplain);
        });
    });
    
    // View button click
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const komplain = JSON.parse(this.dataset.komplain);
            viewKomplain(komplain);
        });
    });
    
    // Delete button click
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_id').value = this.dataset.id;
            document.getElementById('delete_subyek').textContent = this.dataset.subyek;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });
    
    // Reset form when modal is hidden
    document.getElementById('addKomplainModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('addKomplainModal').querySelector('form').reset();
        if (kompainQuill) {
            kompainQuill.setContents([]);
        }
    });
    
    document.getElementById('editKomplainModal').addEventListener('hidden.bs.modal', function() {
        if (editKompainQuill) {
            editKompainQuill.setContents([]);
        }
    });
});

function editKomplain(komplain) {
    document.getElementById('edit_id').value = komplain.id;
    document.getElementById('edit_subyek').value = komplain.subyek;
    // Status tidak bisa diupdate - tidak perlu set value
    document.getElementById('edit_idsupport').value = komplain.idsupport || '';
    document.getElementById('edit_idklien').value = komplain.idklien || '';
    
    // Set Quill content
    if (editKompainQuill) {
        editKompainQuill.root.innerHTML = komplain.kompain;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('editKomplainModal'));
    modal.show();
}

function viewKomplain(komplain) {
    document.getElementById('view_subyek').textContent = komplain.subyek;
    document.getElementById('view_status').innerHTML = '<span class="badge bg-' + 
        (komplain.status === 'komplain' ? 'warning' : 
         komplain.status === 'proses' ? 'info' : 
         komplain.status === 'feedback' ? 'primary' : 'success') + '">' + 
        komplain.status.charAt(0).toUpperCase() + komplain.status.slice(1) + '</span>';
    document.getElementById('view_support').textContent = komplain.nama_support || '-';
    document.getElementById('view_klien').textContent = komplain.nama_klien || '-';
    document.getElementById('view_created_at').textContent = new Date(komplain.created_at).toLocaleString('id-ID');
    document.getElementById('view_updated_at').textContent = new Date(komplain.updated_at).toLocaleString('id-ID');
    document.getElementById('view_kompain').innerHTML = komplain.kompain;
    
    // Display images
    const imagesContainer = document.getElementById('view_images');
    imagesContainer.innerHTML = '';
    if (komplain.image) {
        const images = JSON.parse(komplain.image);
        if (images && images.length > 0) {
            images.forEach(image => {
                const img = document.createElement('img');
                img.src = 'uploads/komplain/images/' + image;
                img.className = 'img-thumbnail me-2 mb-2';
                img.style.maxWidth = '150px';
                img.style.maxHeight = '150px';
                imagesContainer.appendChild(img);
            });
        } else {
            imagesContainer.innerHTML = '<p class="text-muted">Tidak ada gambar</p>';
        }
    } else {
        imagesContainer.innerHTML = '<p class="text-muted">Tidak ada gambar</p>';
    }
    
    // Display files
    const filesContainer = document.getElementById('view_files');
    filesContainer.innerHTML = '';
    if (komplain.uploadfile) {
        const files = JSON.parse(komplain.uploadfile);
        if (files && files.length > 0) {
            files.forEach(file => {
                const link = document.createElement('a');
                link.href = 'uploads/komplain/files/' + file;
                link.className = 'btn btn-outline-primary btn-sm me-2 mb-2';
                link.textContent = file;
                link.target = '_blank';
                filesContainer.appendChild(link);
            });
        } else {
            filesContainer.innerHTML = '<p class="text-muted">Tidak ada file</p>';
        }
    } else {
        filesContainer.innerHTML = '<p class="text-muted">Tidak ada file</p>';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('viewKomplainModal'));
    modal.show();
}
</script>

<?php
// End layout
endLayout();
?>
