<?php
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'includes/session.php';
require_once 'includes/layout_helper.php';

requireAdmin(); // Hanya admin yang bisa akses

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Get pagination and sorting parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Validate sort parameters
$allowed_sort_fields = ['username', 'nama', 'role', 'status', 'id'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'id';
}

if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'asc';
}

// Function to generate sort URL
function getSortUrl($field, $current_sort, $current_order, $search, $limit) {
    $new_order = ($current_sort == $field && $current_order == 'asc') ? 'desc' : 'asc';
    $params = [
        'sort' => $field,
        'order' => $new_order,
        'search' => $search,
        'limit' => $limit
    ];
    return '?' . http_build_query($params);
}

// Function to handle photo upload
function uploadPhoto($file) {
    $upload_dir = 'uploads/profiles/';
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    // Check file type
    if(!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.'];
    }
    
    // Check file size
    if($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 2MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    
    // Create upload directory if not exists
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    $target_path = $upload_dir . $filename;
    if(move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file.'];
    }
}

$message = '';
$message_type = '';

// Handle form submissions
if($_POST) {
    // Check if this is an AJAX request
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'create':
                $user->username = $_POST['username'];
                $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $user->nama = $_POST['nama'];
                $user->alamat = $_POST['alamat'];
                $user->email = $_POST['email'];
                $user->role = $_POST['role'];
                $user->status = $_POST['status'];
                $user->developer = isset($_POST['developer']) ? 1 : 0;
                $user->support = isset($_POST['support']) ? 1 : 0;
                $user->tagihan = isset($_POST['tagihan']) ? 1 : 0;
                $user->foto_profile = '';
                
                // Check if username already exists
                if($user->usernameExists($user->username)) {
                    $message = 'Username sudah digunakan! Silakan gunakan username lain.';
                    $message_type = 'danger';
                    
                    if($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => $message,
                            'message_type' => $message_type
                        ]);
                        exit;
                    }
                    break;
                }
                
                // Handle photo upload
                if(isset($_FILES['foto_profile']) && $_FILES['foto_profile']['error'] == 0) {
                    $upload_result = uploadPhoto($_FILES['foto_profile']);
                    if($upload_result['success']) {
                        $user->foto_profile = $upload_result['filename'];
                    } else {
                        $message = $upload_result['message'];
                        $message_type = 'danger';
                        break;
                    }
                }
                
                if($user->create()) {
                    $message = 'User berhasil ditambahkan!';
                    $message_type = 'success';
                    
                    if($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => $message,
                            'message_type' => $message_type
                        ]);
                        exit;
                    }
                } else {
                    $message = 'Gagal menambahkan user!';
                    $message_type = 'danger';
                    
                    if($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => $message,
                            'message_type' => $message_type
                        ]);
                        exit;
                    }
                }
                break;
                
            case 'update':
                $user->id = $_POST['id'];
                $user->username = $_POST['username'];
                $user->nama = $_POST['nama'];
                $user->alamat = $_POST['alamat'];
                $user->email = $_POST['email'];
                $user->role = $_POST['role'];
                $user->status = $_POST['status'];
                $user->developer = isset($_POST['developer']) ? 1 : 0;
                $user->support = isset($_POST['support']) ? 1 : 0;
                $user->tagihan = isset($_POST['tagihan']) ? 1 : 0;
                
                // Check if trying to deactivate admin user
                $existing_user = new User($db);
                $existing_user->getUserById($user->id);
                
                if($existing_user->role === 'admin' && $user->status === 'non aktif') {
                    $message = 'User dengan role Admin tidak dapat dinonaktifkan!';
                    $message_type = 'danger';
                    
                    if($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => $message,
                            'message_type' => $message_type
                        ]);
                        exit;
                    }
                    break;
                }
                
                // Check if username already exists (excluding current user)
                if($user->usernameExists($user->username, $user->id)) {
                    $message = 'Username sudah digunakan! Silakan gunakan username lain.';
                    $message_type = 'danger';
                    
                    if($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => $message,
                            'message_type' => $message_type
                        ]);
                        exit;
                    }
                    break;
                }
                
                // Handle password update
                if(!empty($_POST['password'])) {
                    $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                } else {
                    // Keep existing password
                    $existing_user = new User($db);
                    $existing_user->getUserById($user->id);
                    $user->password = $existing_user->password;
                }
                
                // Handle photo upload
                if(isset($_FILES['foto_profile']) && $_FILES['foto_profile']['error'] == 0) {
                    $upload_result = uploadPhoto($_FILES['foto_profile']);
                    if($upload_result['success']) {
                        // Delete old photo if exists
                        $existing_user = new User($db);
                        $existing_user->getUserById($user->id);
                        if(!empty($existing_user->foto_profile) && file_exists('uploads/profiles/' . $existing_user->foto_profile)) {
                            unlink('uploads/profiles/' . $existing_user->foto_profile);
                        }
                        $user->foto_profile = $upload_result['filename'];
                    } else {
                        $message = $upload_result['message'];
                        $message_type = 'danger';
                        break;
                    }
                } else {
                    // Keep existing photo
                    $existing_user = new User($db);
                    $existing_user->getUserById($user->id);
                    $user->foto_profile = $existing_user->foto_profile;
                }
                
                if($user->update()) {
                    $message = 'User berhasil diperbarui!';
                    $message_type = 'success';
                    
                    if($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => $message,
                            'message_type' => $message_type
                        ]);
                        exit;
                    }
                } else {
                    $message = 'Gagal memperbarui user!';
                    $message_type = 'danger';
                    
                    if($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'message' => $message,
                            'message_type' => $message_type
                        ]);
                        exit;
                    }
                }
                break;
                
            case 'delete':
                $user->id = $_POST['id'];
                
                // Check if user is admin - prevent deletion
                $existing_user = new User($db);
                $existing_user->getUserById($user->id);
                
                if($existing_user->role === 'admin') {
                    $message = 'User dengan role Admin tidak dapat dihapus!';
                    $message_type = 'danger';
                } else {
                    // Delete photo if exists
                    if(!empty($existing_user->foto_profile) && file_exists('uploads/profiles/' . $existing_user->foto_profile)) {
                        unlink('uploads/profiles/' . $existing_user->foto_profile);
                    }
                    
                    if($user->delete()) {
                        $message = 'User berhasil dihapus!';
                        $message_type = 'success';
                    } else {
                        $message = 'Gagal menghapus user!';
                        $message_type = 'danger';
                    }
                }
                break;
        }
    }
}

// Get users data with pagination
$users = $user->getUsersWithPagination($page, $limit, $search, $sort_by, $sort_order);
$total_users = $user->getTotalUsers($search);
$total_pages = ceil($total_users / $limit);

// Start layout with buffer
startLayoutBuffer('Manajemen User - Logics Software');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Manajemen User</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus"></i> Tambah User
        </button>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search and Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Cari User</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Username, nama, atau email...">
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
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Cari
                        </button>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-refresh"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th><a href="<?php echo getSortUrl('username', $sort_by, $sort_order, $search, $limit); ?>" class="text-white text-decoration-none">Username <?php echo $sort_by == 'username' ? ($sort_order == 'asc' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('nama', $sort_by, $sort_order, $search, $limit); ?>" class="text-white text-decoration-none">Nama <?php echo $sort_by == 'nama' ? ($sort_order == 'asc' ? '↑' : '↓') : ''; ?></a></th>
                            <th>Email</th>
                            <th><a href="<?php echo getSortUrl('role', $sort_by, $sort_order, $search, $limit); ?>" class="text-white text-decoration-none">Role <?php echo $sort_by == 'role' ? ($sort_order == 'asc' ? '↑' : '↓') : ''; ?></a></th>
                            <th>Pekerjaan</th>
                            <th><a href="<?php echo getSortUrl('status', $sort_by, $sort_order, $search, $limit); ?>" class="text-white text-decoration-none">Status <?php echo $sort_by == 'status' ? ($sort_order == 'asc' ? '↑' : '↓') : ''; ?></a></th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada data user</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($users as $index => $u): ?>
                                <tr>
                                    <td><?php echo (($page - 1) * $limit) + $index + 1; ?></td>
                                    <td>
                                        <?php if(!empty($u['foto_profile'])): ?>
                                            <img src="uploads/profiles/<?php echo htmlspecialchars($u['foto_profile'] ?? ''); ?>" 
                                                 alt="Foto Profile" class="rounded-circle img-cover avatar-small">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center avatar-small">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['username'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($u['nama'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $u['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst($u['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <?php if($u['developer']): ?>
                                                <span class="badge bg-info" title="Developer">Dev</span>
                                            <?php endif; ?>
                                            <?php if($u['support']): ?>
                                                <span class="badge bg-warning" title="Support">Sup</span>
                                            <?php endif; ?>
                                            <?php if($u['tagihan']): ?>
                                                <span class="badge bg-success" title="Tagihan">Tag</span>
                                            <?php endif; ?>
                                            <?php if(!$u['developer'] && !$u['support'] && !$u['tagihan']): ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $u['status'] === 'aktif' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($u['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-btn" 
                                                data-user='<?php echo json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if($u['role'] !== 'admin'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                                                data-id="<?php echo $u['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($u['nama'] ?? ''); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&limit=<?php echo $limit; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&limit=<?php echo $limit; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&limit=<?php echo $limit; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Tambah User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUserForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama" name="nama" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="1"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="foto_profile" class="form-label">Foto Profile</label>
                        <input type="file" class="form-control" id="foto_profile" name="foto_profile" accept="image/*">
                        <small class="text-muted d-block mb-2">Format: JPG, PNG, GIF, WebP. Maksimal 2MB.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                                <option value="client">Client</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pekerjaan</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="developer" name="developer" value="1">
                                        <label class="form-check-label" for="developer">
                                            Developer
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="support" name="support" value="1">
                                        <label class="form-check-label" for="support">
                                            Support
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tagihan" name="tagihan" value="1">
                                        <label class="form-check-label" for="tagihan">
                                            Tagihan
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Pilih Status</option>
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

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nama" name="nama" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_alamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="edit_alamat" name="alamat" rows="1"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_foto_profile" class="form-label">Foto Profile</label>
                        <div class="d-flex align-items-start gap-3">
                            <div id="current_photo" class="flex-shrink-0"></div>
                            <div class="flex-grow-1">
                                <input type="file" class="form-control" id="edit_foto_profile" name="foto_profile" accept="image/*">
                                <small class="text-muted d-block mb-2">Format: JPG, PNG, GIF, WebP. Maksimal 2MB.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label for="edit_role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                                <option value="client">Client</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pekerjaan</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit_developer" name="developer" value="1">
                                        <label class="form-check-label" for="edit_developer">
                                            Developer
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit_support" name="support" value="1">
                                        <label class="form-check-label" for="edit_support">
                                            Support
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit_tagihan" name="tagihan" value="1">
                                        <label class="form-check-label" for="edit_tagihan">
                                            Tagihan
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="">Pilih Status</option>
                                <option value="aktif">Aktif</option>
                                <option value="non aktif" id="edit_nonaktif_option">Non Aktif</option>
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
                <p>Apakah Anda yakin ingin menghapus user <strong id="deleteUserName"></strong>?</p>
                <p class="text-danger">Tindakan ini tidak dapat dibatalkan!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal for Username Duplication -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="errorModalLabel">Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-warning modal-icon-large"></i>
                    <p id="errorMessage" class="mb-0"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
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
document.addEventListener('DOMContentLoaded', function() {
    // Handle role change for create form
    const roleSelect = document.getElementById('role');
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            toggleJobCheckboxes('create');
        });
    }
    
    // Handle role change for edit form
    const editRoleSelect = document.getElementById('edit_role');
    if (editRoleSelect) {
        editRoleSelect.addEventListener('change', function() {
            toggleJobCheckboxes('edit');
        });
    }
    
    
    // Handle delete button clicks
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

    // Handle edit button clicks
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userData = JSON.parse(this.getAttribute('data-user'));
            editUser(userData);
        });
    });
    
    // Reset form when modals are shown
    const addUserModal = document.getElementById('addUserModal');
    if (addUserModal) {
        addUserModal.addEventListener('show.bs.modal', function() {
            // Reset all checkboxes to enabled state
            document.getElementById('developer').disabled = false;
            document.getElementById('support').disabled = false;
            document.getElementById('tagihan').disabled = false;
            
            // Remove any existing info text
            const existingInfo = document.getElementById('role-info');
            if (existingInfo) {
                existingInfo.remove();
            }
        });
    }
    
    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        editUserModal.addEventListener('show.bs.modal', function() {
            // Remove any existing info text
            const existingInfo = document.getElementById('edit_role-info');
            if (existingInfo) {
                existingInfo.remove();
            }
        });
    }
});

// Function to toggle job checkboxes based on role (Global function)
function toggleJobCheckboxes(formType) {
    const prefix = formType === 'edit' ? 'edit_' : '';
    const roleSelect = document.getElementById(prefix + 'role');
    const developerCheckbox = document.getElementById(prefix + 'developer');
    const supportCheckbox = document.getElementById(prefix + 'support');
    const tagihanCheckbox = document.getElementById(prefix + 'tagihan');
    
    if (roleSelect && developerCheckbox && supportCheckbox && tagihanCheckbox) {
        const selectedRole = roleSelect.value;
        
        // Remove existing info text
        const existingInfo = document.getElementById(prefix + 'role-info');
        if (existingInfo) {
            existingInfo.remove();
        }
        
        if (selectedRole === 'client') {
            // Disable and uncheck all job checkboxes for client role
            developerCheckbox.disabled = true;
            developerCheckbox.checked = false;
            supportCheckbox.disabled = true;
            supportCheckbox.checked = false;
            tagihanCheckbox.disabled = true;
            tagihanCheckbox.checked = false;
            
            // Add info text for client role
            // const jobContainer = developerCheckbox.closest('.row').parentElement;
            // const infoDiv = document.createElement('div');
            // infoDiv.id = prefix + 'role-info';
            // infoDiv.className = 'role-client-info';
            // infoDiv.innerHTML = '<i class="fas fa-info-circle me-1"></i>Role Client tidak memiliki akses pekerjaan internal. Checkbox dinonaktifkan.';
            // jobContainer.appendChild(infoDiv);
        } else {
            // Enable all job checkboxes for admin and user roles
            developerCheckbox.disabled = false;
            supportCheckbox.disabled = false;
            tagihanCheckbox.disabled = false;
        }
    }
}

function editUser(user) {
    try {
        document.getElementById('edit_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_nama').value = user.nama;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_alamat').value = user.alamat;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_status').value = user.status;
        
        // Hide "Non Aktif" option for admin users
        const nonaktifOption = document.getElementById('edit_nonaktif_option');
        if (user.role === 'admin') {
            nonaktifOption.style.display = 'none';
        } else {
            nonaktifOption.style.display = 'block';
        }
        
        // Set checkbox values
        document.getElementById('edit_developer').checked = user.developer == 1;
        document.getElementById('edit_support').checked = user.support == 1;
        document.getElementById('edit_tagihan').checked = user.tagihan == 1;
        
        // Toggle job checkboxes based on role
        toggleJobCheckboxes('edit');
        
        // Show current photo
        const currentPhotoDiv = document.getElementById('current_photo');
        if(user.foto_profile) {
            currentPhotoDiv.innerHTML = `
                <div class="text-center">
                    <img src="uploads/profiles/${user.foto_profile}" alt="Current Photo" class="rounded-circle img-cover avatar-medium-large">
                </div>
            `;
        } else {
            currentPhotoDiv.innerHTML = `
                <div class="text-center">
                    <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center avatar-medium-large">
                        <i class="fas fa-user text-white"></i>
                    </div>
                </div>
            `;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        modal.show();
    } catch (error) {
        showAlertModal('Error: ' + error.message);
    }
}

// Handle form submissions with AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Add User Form
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, 'create');
        });
    }

    // Edit User Form
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, 'update');
        });
    }
});

function submitForm(form, action) {
    const formData = new FormData(form);
    formData.append('action', action);

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Memproses...';
    submitBtn.disabled = true;

    fetch('users.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success - show alert and reload page
            showAlertModal(data.message);
            location.reload();
        } else {
            // Error - show modal and keep form modal open
            showErrorModal(data.message);
            
            // Reset button state
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        showAlertModal('Terjadi kesalahan saat memproses data!');
        
        // Reset button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Function to show error modal
function showErrorModal(message) {
    document.getElementById('errorMessage').textContent = message;
    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
    errorModal.show();
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
