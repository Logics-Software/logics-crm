<?php
require_once 'config/database.php';
require_once 'models/Komplain.php';
require_once 'models/User.php';
require_once 'models/Klien.php';
require_once 'models/KomplainProcess.php';
require_once 'includes/session.php';
require_once 'includes/layout_helper.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$komplain = new Komplain($db);
$user = new User($db);
$klien = new Klien($db);
$komplainProcess = new KomplainProcess($db);

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
if ($current_user->role === 'admin' && $current_user->developer == 1) {
    // 1. Administrator + Developer: semua data komplain
    $komplain_data = $komplain->getKomplainWithPagination($page, $limit, $search, $status_filter, $sort_by, $sort_order);
    $total_komplain = $komplain->getTotalKomplain($search, $status_filter);
} elseif ($current_user->role === 'client') {
    // 2. Client: hanya data komplain yang dibuat oleh user login
    $komplain_data = $komplain->getKomplainByUser($_SESSION['user_id'], $current_user->role, $page, $limit, $current_user->support, $current_user->idklien);
    $total_komplain = $komplain->getTotalKomplainByUser($_SESSION['user_id'], $current_user->role, $current_user->support, $current_user->idklien, $search, $status_filter);
} elseif ($current_user->role === 'user' && $current_user->support == 1) {
    // 3. User + Support: data komplain yang dibuat oleh support + data komplain dari client yang iduser di klien == iduser login
    $komplain_data = $komplain->getKomplainBySupportUser($_SESSION['user_id'], $page, $limit, $search, $status_filter, $sort_by, $sort_order);
    $total_komplain = $komplain->getTotalKomplainBySupportUser($_SESSION['user_id'], $search, $status_filter);
} else {
    // Default: data komplain berdasarkan user
    $komplain_data = $komplain->getKomplainByUser($_SESSION['user_id'], $current_user->role, $page, $limit, $current_user->support, $current_user->idklien);
    $total_komplain = $komplain->getTotalKomplainByUser($_SESSION['user_id'], $current_user->role, $current_user->support, $current_user->idklien, $search, $status_filter);
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
            // Untuk role client, idklien otomatis dari user yang login
            if ($current_user->role === 'client') {
                $komplain->idklien = $current_user->idklien;
            } else {
                $komplain->idklien = $_POST['idklien'];
            }
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
            // Status tidak bisa diupdate - tetap dari database
            $existing_komplain = new Komplain($db);
            $existing_komplain->getKomplainById($komplain->id);
            $komplain->status = $existing_komplain->status;
            $komplain->idsupport = $existing_komplain->idsupport; // Tetap menggunakan idsupport yang sudah ada
            // Untuk role client, idklien otomatis dari user yang login
            if ($current_user->role === 'client') {
                $komplain->idklien = $current_user->idklien;
            } else {
                $komplain->idklien = $_POST['idklien'];
            }
            
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
            
            // Validasi: hanya admin atau pemilik komplain yang bisa menghapus
            $existing_komplain = new Komplain($db);
            $existing_komplain->getKomplainById($komplain->id);
            
            $can_delete = false;
            if ($current_user->role === 'admin') {
                $can_delete = true;
            } elseif (($current_user->role === 'support' || $current_user->support == 1) && $existing_komplain->idsupport == $_SESSION['user_id']) {
                $can_delete = true;
            } elseif ($current_user->role === 'client' && $existing_komplain->idklien == $_SESSION['user_id']) {
                $can_delete = true;
            }
            
            if ($can_delete) {
                if($komplain->delete()) {
                    // Redirect untuk mencegah resubmission
                    header('Location: komplain.php?success=delete');
                    exit();
                } else {
                    $message = 'Gagal menghapus komplain!';
                    $message_type = 'danger';
                }
            } else {
                $message = 'Anda tidak memiliki izin untuk menghapus komplain ini!';
                $message_type = 'danger';
            }
            break;
            
        case 'process':
            $komplain_id = $_POST['komplain_id'];
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
            
            // Validasi: hanya developer yang bisa memproses komplain
            if (!$komplainProcess->canProcessKomplain($_SESSION['user_id'])) {
                $message = 'Anda tidak memiliki izin untuk memproses komplain!';
                $message_type = 'danger';
                break;
            }
            
            try {
                if ($komplainProcess->processKomplain($komplain_id, $_SESSION['user_id'], $notes)) {
                    // Redirect untuk mencegah resubmission
                    header('Location: komplain.php?success=process');
                    exit();
                } else {
                    $message = 'Gagal memproses komplain!';
                    $message_type = 'danger';
                }
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
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
        case 'process':
            $message = 'Komplain berhasil diproses!';
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
    
    // Get client data for the logged-in client user
    if ($current_user->idklien) {
        $stmt = $db->prepare("SELECT id, namaklien FROM klien WHERE id = :idklien AND status = 'aktif'");
        $stmt->bindParam(':idklien', $current_user->idklien);
        $stmt->execute();
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // If no idklien, initialize empty array
        $clients = [];
    }
}

// Start layout
startLayoutBuffer('Komplain');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Data Komplain</h1>
        <?php if ($current_user->role === 'admin' || $current_user->role === 'support' || $current_user->support == 1 || $current_user->developer == 1 || $current_user->role === 'client'): ?>
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
                        <option value="solved" <?php echo $status_filter === 'solved' ? 'selected' : ''; ?>>Solved</option>
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
                            <th>Klien</th>
                            <th>Support</th>
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
                            <?php 
                            $counter = ($page - 1) * $limit + 1;
                            while($k = $komplain_data->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo htmlspecialchars($k['subyek']); ?></td>
                                    <td>
                                        <?php if(!empty($k['nama_klien'])): ?>
                                            <span><?php echo htmlspecialchars($k['nama_klien']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($k['nama_support'])): ?>
                                            <span><?php echo htmlspecialchars($k['nama_support']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($k['status'] === 'proses' && !empty($k['process_notes'])): ?>
                                        <span class="badge bg-info status-badge" 
                                              data-bs-toggle="tooltip" 
                                              data-bs-placement="top" 
                                              data-bs-html="true"
                                              data-bs-title="<div class='process-tooltip'>
                                                              <div class='fw-bold mb-2'><?php echo htmlspecialchars($k['process_user_name'] ?? 'N/A'); ?></div>
                                                              <div class='mb-3'><?php echo date('d/m/Y H:i', strtotime($k['process_date'])); ?></div>
                                                              <div><i class='fa-solid fa-comment'></i><?php echo htmlspecialchars($k['process_notes']); ?></div>
                                                           </div>">
                                            <?php echo ucfirst($k['status']); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-<?php 
                                            echo $k['status'] === 'komplain' ? 'warning' : 
                                                ($k['status'] === 'proses' ? 'info' : 
                                                    ($k['status'] === 'solved' ? 'primary' : 'success')); 
                                        ?>">
                                            <?php echo ucfirst($k['status']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($k['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info view-btn" 
                                                data-komplain='<?php echo json_encode($k, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if (($current_user->role === 'admin' || (($current_user->role === 'support' || $current_user->support == 1) && $k['idsupport'] == $_SESSION['user_id']) || ($current_user->role === 'client' && $k['idsupport'] == $_SESSION['user_id'])) && !in_array($k['status'], ['proses', 'solved', 'selesai']) && ($current_user->role !== 'admin' || $current_user->developer != 1)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-btn" 
                                                data-komplain='<?php echo json_encode($k, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (($current_user->role === 'admin' || $current_user->developer == 1) && $k['status'] === 'komplain'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success process-btn" 
                                                data-id="<?php echo $k['id']; ?>" 
                                                data-subyek="<?php echo htmlspecialchars($k['subyek']); ?>">
                                            <i class="fas fa-cog"></i> Proses
                                        </button>
                                        <?php endif; ?>
                                        <?php if (($current_user->role === 'admin' || (($current_user->role === 'support' || $current_user->support == 1) && $k['idsupport'] == $_SESSION['user_id']) || ($current_user->role === 'client' && $k['idsupport'] == $_SESSION['user_id'])) && !in_array($k['status'], ['proses', 'solved', 'selesai']) && ($current_user->role !== 'admin' || $current_user->developer != 1)): ?>
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
                        <div class="col-md-6 mb-3">
                            <label for="subyek" class="form-label">Subyek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subyek" name="subyek" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="idklien" class="form-label">Klien <span class="text-danger">*</span></label>
                            <select class="form-select" id="idklien" name="idklien" required <?php echo ($current_user->role === 'client') ? 'disabled' : ''; ?>>
                                <option value="">Pilih Klien</option>
                                <?php foreach($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>" <?php echo ($current_user->role === 'client' && $current_user->idklien == $client['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['namaklien']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if($current_user->role === 'client'): ?>
                                <input type="hidden" name="idklien" value="<?php echo $current_user->idklien; ?>">
                            <?php endif; ?>
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
                        <div class="col-md-6 mb-3">
                            <label for="edit_subyek" class="form-label">Subyek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_subyek" name="subyek" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_idklien" class="form-label">Klien <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_idklien" name="idklien" <?php echo ($current_user->role === 'client') ? 'disabled' : 'required'; ?>>
                                <option value="">Pilih Klien</option>
                                <?php foreach($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>" <?php echo ($current_user->role === 'client' && $current_user->idklien == $client['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['namaklien']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if($current_user->role === 'client'): ?>
                                <input type="hidden" name="idklien" value="<?php echo $current_user->idklien; ?>">
                            <?php endif; ?>
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

<!-- Image Zoom Modal -->
<div class="modal fade" id="imageZoomModal" tabindex="-1" aria-labelledby="imageZoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageZoomModalLabel">Gambar Komplain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="image-zoom-container" style="position: relative; overflow: hidden; max-height: 80vh;">
                    <img id="zoomedImage" src="" alt="Gambar Komplain" class="img-fluid" style="max-width: 100%; max-height: 100%; cursor: grab; transition: transform 0.3s ease; user-select: none;">
                </div>
                <div class="mt-3 zoom-controls">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="zoomInBtn">
                        <i class="fas fa-search-plus"></i> Zoom In
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="zoomOutBtn">
                        <i class="fas fa-search-minus"></i> Zoom Out
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="resetZoomBtn">
                        <i class="fas fa-expand-arrows-alt"></i> Reset
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" id="downloadImageBtn">
                        <i class="fas fa-download"></i> Download
                    </button>
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

<!-- Process Confirmation Modal -->
<div class="modal fade" id="processModal" tabindex="-1" aria-labelledby="processModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processModalLabel">Konfirmasi Proses Komplain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin memproses komplain "<span id="process_subyek"></span>"?</p>
                <div class="mb-3">
                    <label for="process_notes" class="form-label">Catatan (Opsional)</label>
                    <textarea class="form-control" id="process_notes" name="notes" rows="3" placeholder="Tambahkan catatan untuk proses komplain..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="process">
                    <input type="hidden" name="komplain_id" id="process_komplain_id">
                    <input type="hidden" name="notes" id="process_notes_hidden">
                    <button type="submit" class="btn btn-success">Proses Komplain</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Image Zoom Modal Styles */
#imageZoomModal .modal-dialog {
    max-width: 95vw;
    max-height: 95vh;
    height: 90vh;
    min-height: 600px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

#imageZoomModal .modal-body {
    padding: 1rem;
    background-color: #f8f9fa;
}

.image-zoom-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 1rem;
    margin-bottom: 1rem;
    height: 70vh;
    min-height: 500px;
    display: flex;
    align-items: center;
    justify-content: center;
}

#zoomedImage {
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    user-select: none;
    -webkit-user-drag: none;
    -khtml-user-drag: none;
    -moz-user-drag: none;
    -o-user-drag: none;
}

#zoomedImage.dragging {
    cursor: grabbing !important;
    transition: none !important;
}

.zoom-controls {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Process Tooltip Styles */
.process-tooltip {
    max-width: 300px;
    font-size: 0.875rem;
    line-height: 1.4;
    border: 1px solid #757677;
    /* border: none; */
    border-radius: 8px;
    padding: 12px;
    color:rgb(252, 252, 252);
    box-shadow: none;
}

.process-tooltip .fw-bold {
    color:rgb(189, 255, 6);
    font-weight: 600;
}

.process-tooltip i {
    width: 16px;
    text-align: center;
    margin-right: 8px;
    color:rgb(211, 94, 94);
}

.status-badge {
    cursor: help;
    transition: all 0.3s ease;
}

.status-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.zoom-controls .btn {
    min-width: 120px;
}

/* Thumbnail hover effect */
.img-thumbnail {
    transition: all 0.3s ease;
    border: 2px solid #dee2e6;
}

.img-thumbnail:hover {
    border-color: #007bff;
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #imageZoomModal .modal-dialog {
        max-width: 100vw;
        margin: 0;
        height: 100vh;
        min-height: 100vh;
    }
    
    .image-zoom-container {
        height: 60vh;
        min-height: 400px;
    }
    
    .zoom-controls .btn {
        min-width: 80px;
        font-size: 0.875rem;
    }
}
</style>

<script>
// Global variables for Quill editors
let kompainQuill, editKompainQuill;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quill editors
    
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
    
    // Initialize tooltips for process status
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            html: true,
            placement: 'top',
            trigger: 'hover focus'
        });
    });
    
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
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            try {
                const komplain = JSON.parse(this.dataset.komplain);
                editKomplain(komplain);
            } catch (error) {
                alert('Error loading komplain data: ' + error.message);
            }
        });
    });
    
    // View button click
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const komplain = JSON.parse(this.dataset.komplain);
            viewKomplain(komplain);
        });
    });
    
    // Handle modal focus management for accessibility
    const modals = ['viewKomplainModal', 'addKomplainModal', 'editKomplainModal', 'imageZoomModal', 'deleteModal', 'processModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function() {
                // Remove focus from any focused element within the modal
                const focusedElement = modal.querySelector(':focus');
                if (focusedElement) {
                    focusedElement.blur();
                }
            });
        }
    });
    
    // Delete button click
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_id').value = this.dataset.id;
            document.getElementById('delete_subyek').textContent = this.dataset.subyek;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });
    
    // Process button click
    document.querySelectorAll('.process-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('process_komplain_id').value = this.dataset.id;
            document.getElementById('process_subyek').textContent = this.dataset.subyek;
            // Reset notes field
            document.getElementById('process_notes').value = '';
            new bootstrap.Modal(document.getElementById('processModal')).show();
        });
    });
    
    // Handle process form submission
    document.getElementById('processModal').addEventListener('submit', function(e) {
        e.preventDefault();
        const notes = document.getElementById('process_notes').value;
        document.getElementById('process_notes_hidden').value = notes;
        this.querySelector('form').submit();
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
    try {
        // Check if all required elements exist
        const editIdField = document.getElementById('edit_id');
        const editSubyekField = document.getElementById('edit_subyek');
        const editIdklienField = document.getElementById('edit_idklien');
        const modalElement = document.getElementById('editKomplainModal');
        
        if (!editIdField || !editSubyekField || !editIdklienField || !modalElement) {
            alert('Required form elements not found. Please refresh the page.');
            return;
        }
        
        // Populate form fields
        editIdField.value = komplain.id;
        editSubyekField.value = komplain.subyek;
        editIdklienField.value = komplain.idklien || '';
        
        // Handle disabled idklien field for client role
        if (editIdklienField.disabled) {
            // For client role, set the hidden input value
            const hiddenIdklien = document.querySelector('input[name="idklien"][type="hidden"]');
            if (hiddenIdklien) {
                hiddenIdklien.value = komplain.idklien || '';
            }
        }
        
        // Set Quill content
        if (editKompainQuill) {
            editKompainQuill.root.innerHTML = komplain.kompain;
        } else {
            // Try to initialize Quill if not already done
            const editKompainElement = document.getElementById('edit_kompain');
            if (editKompainElement) {
                try {
                    editKompainQuill = new Quill('#edit_kompain', {
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
                    });
                    editKompainQuill.root.innerHTML = komplain.kompain;
                } catch (quillError) {
                    // Silent error handling
                }
            }
        }
        
        // Check if Bootstrap is loaded
        if (typeof bootstrap === 'undefined') {
            alert('Bootstrap is not loaded. Please refresh the page.');
            return;
        }
        
        // Show modal
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
    } catch (error) {
        alert('Error opening edit modal: ' + error.message);
    }
}

function viewKomplain(komplain) {
    document.getElementById('view_subyek').textContent = komplain.subyek;
    document.getElementById('view_status').innerHTML = '<span class="badge bg-' + 
        (komplain.status === 'komplain' ? 'warning' : 
         komplain.status === 'proses' ? 'info' : 
         komplain.status === 'solved' ? 'primary' : 'success') + '">' + 
        komplain.status.charAt(0).toUpperCase() + komplain.status.slice(1) + '</span>';
    document.getElementById('view_support').textContent = komplain.nama_support || '-';
    document.getElementById('view_klien').textContent = komplain.nama_klien || '-';
    document.getElementById('view_created_at').textContent = new Date(komplain.created_at).toLocaleString('id-ID');
    document.getElementById('view_updated_at').textContent = new Date(komplain.updated_at).toLocaleString('id-ID');
    document.getElementById('view_kompain').innerHTML = komplain.kompain;
    
    // Display images with zoom functionality
    const imagesContainer = document.getElementById('view_images');
    imagesContainer.innerHTML = '';
    if (komplain.image) {
        const images = JSON.parse(komplain.image);
        if (images && images.length > 0) {
            images.forEach((image, index) => {
                const img = document.createElement('img');
                img.src = 'uploads/komplain/images/' + image;
                img.className = 'img-thumbnail me-2 mb-2';
                img.style.maxWidth = '150px';
                img.style.maxHeight = '150px';
                img.style.cursor = 'pointer';
                img.title = 'Klik untuk zoom';
                
                // Add click event for zoom
                img.addEventListener('click', function() {
                    showImageZoom('uploads/komplain/images/' + image, image);
                });
                
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

// Image zoom functionality
let currentZoom = 1;
let currentImageSrc = '';
let currentImageName = '';
let isDragging = false;
let startX = 0;
let startY = 0;
let translateX = 0;
let translateY = 0;

function showImageZoom(imageSrc, imageName) {
    currentImageSrc = imageSrc;
    currentImageName = imageName;
    currentZoom = 1;
    translateX = 0;
    translateY = 0;
    isDragging = false;
    
    const zoomedImage = document.getElementById('zoomedImage');
    zoomedImage.src = imageSrc;
    zoomedImage.style.transform = 'scale(1) translate(0px, 0px)';
    zoomedImage.classList.remove('dragging');
    
    const modal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
    modal.show();
}

// Zoom controls
document.addEventListener('DOMContentLoaded', function() {
    const zoomedImage = document.getElementById('zoomedImage');
    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    const resetZoomBtn = document.getElementById('resetZoomBtn');
    const downloadImageBtn = document.getElementById('downloadImageBtn');
    
    // Zoom In
    zoomInBtn.addEventListener('click', function() {
        currentZoom += 0.25;
        if (currentZoom > 3) currentZoom = 3;
        zoomedImage.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
    });
    
    // Zoom Out
    zoomOutBtn.addEventListener('click', function() {
        currentZoom -= 0.25;
        if (currentZoom < 0.25) currentZoom = 0.25;
        zoomedImage.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
    });
    
    // Reset Zoom
    resetZoomBtn.addEventListener('click', function() {
        currentZoom = 1;
        translateX = 0;
        translateY = 0;
        zoomedImage.style.transform = 'scale(1) translate(0px, 0px)';
    });
    
    // Download Image
    downloadImageBtn.addEventListener('click', function() {
        if (currentImageSrc) {
            const link = document.createElement('a');
            link.href = currentImageSrc;
            link.download = currentImageName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });
    
    // Mouse wheel zoom
    zoomedImage.addEventListener('wheel', function(e) {
        e.preventDefault();
        if (e.deltaY < 0) {
            // Zoom in
            currentZoom += 0.1;
            if (currentZoom > 3) currentZoom = 3;
        } else {
            // Zoom out
            currentZoom -= 0.1;
            if (currentZoom < 0.25) currentZoom = 0.25;
        }
        zoomedImage.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
    });
    
    // Double click to reset zoom
    zoomedImage.addEventListener('dblclick', function() {
        currentZoom = 1;
        translateX = 0;
        translateY = 0;
        zoomedImage.style.transform = 'scale(1) translate(0px, 0px)';
    });
    
    // Drag/Pan functionality
    zoomedImage.addEventListener('mousedown', function(e) {
        if (currentZoom > 1) {
            isDragging = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
            zoomedImage.classList.add('dragging');
            e.preventDefault();
        }
    });
    
    document.addEventListener('mousemove', function(e) {
        if (isDragging && currentZoom > 1) {
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            zoomedImage.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
        }
    });
    
    document.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            zoomedImage.classList.remove('dragging');
        }
    });
    
    // Touch support for mobile
    zoomedImage.addEventListener('touchstart', function(e) {
        if (currentZoom > 1 && e.touches.length === 1) {
            isDragging = true;
            startX = e.touches[0].clientX - translateX;
            startY = e.touches[0].clientY - translateY;
            zoomedImage.classList.add('dragging');
            e.preventDefault();
        }
    });
    
    document.addEventListener('touchmove', function(e) {
        if (isDragging && currentZoom > 1 && e.touches.length === 1) {
            translateX = e.touches[0].clientX - startX;
            translateY = e.touches[0].clientY - startY;
            zoomedImage.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
            e.preventDefault();
        }
    });
    
    document.addEventListener('touchend', function() {
        if (isDragging) {
            isDragging = false;
            zoomedImage.classList.remove('dragging');
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (document.getElementById('imageZoomModal').classList.contains('show')) {
            switch(e.key) {
                case '+':
                case '=':
                    e.preventDefault();
                    zoomInBtn.click();
                    break;
                case '-':
                    e.preventDefault();
                    zoomOutBtn.click();
                    break;
                case '0':
                    e.preventDefault();
                    resetZoomBtn.click();
                    break;
                case 'ArrowLeft':
                    if (currentZoom > 1) {
                        e.preventDefault();
                        translateX += 20;
                        zoomedImage.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                    }
                    break;
                case 'ArrowRight':
                    if (currentZoom > 1) {
                        e.preventDefault();
                        translateX -= 20;
                        zoomedImage.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                    }
                    break;
                case 'ArrowUp':
                    if (currentZoom > 1) {
                        e.preventDefault();
                        translateY += 20;
                        zoomedImage.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                    }
                    break;
                case 'ArrowDown':
                    if (currentZoom > 1) {
                        e.preventDefault();
                        translateY -= 20;
                        zoomedImage.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                    }
                    break;
                case 'Escape':
                    bootstrap.Modal.getInstance(document.getElementById('imageZoomModal')).hide();
                    break;
            }
        }
    });
});
</script>

<?php
// End layout
endLayout();
?>
