<?php
require_once 'config/database.php';
require_once 'models/Solving.php';
require_once 'models/Komplain.php';
require_once 'models/User.php';
require_once 'includes/session.php';
require_once 'includes/layout_helper.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$solving = new Solving($db);
$komplain = new Komplain($db);
$user = new User($db);

$message = '';
$message_type = '';

// Get current user info
$current_user = new User($db);
$current_user->getUserById($_SESSION['user_id']);

// Get parameters for pagination, search, and sorting
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
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
$allowed_sort_fields = ['subyek', 'created_at', 'updated_at', 'support_name', 'client_name', 'komplain_subyek'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'created_at';
}

if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

// Ensure page is at least 1
if($page < 1) $page = 1;

// Function to generate sort URL
function getSortUrl($field, $current_sort, $current_order, $search, $limit) {
    $new_order = ($current_sort == $field && $current_order == 'ASC') ? 'DESC' : 'ASC';
    $params = [
        'sort' => $field,
        'order' => $new_order,
        'search' => $search,
        'limit' => $limit
    ];
    return 'solving.php?' . http_build_query($params);
}

// Get solving data
$solving_data = $solving->getSolvingWithPagination($page, $limit, $search, $sort_by, $sort_order);
$total_solving = $solving->getTotalSolving($search);

$total_pages = ceil($total_solving / $limit);

// Handle form submissions
if($_POST) {
    $action = $_POST['action'];
    
    switch($action) {
        case 'create':
            $solving->subyek = $_POST['subyek'];
            $solving->solving = $_POST['solving'];
            $solving->idkomplain = $_POST['idkomplain'];
            $solving->idsupport = $_POST['idsupport'];
            $solving->iddeveloper = $_SESSION['user_id'];
            
            // Handle image uploads
            $images = [];
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/solving/images/';
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
            $solving->image = json_encode($images);
            
            // Handle file uploads
            $files = [];
            if (!empty($_FILES['uploadfiles']['name'][0])) {
                $upload_dir = 'uploads/solving/files/';
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
            $solving->uploadfiles = json_encode($files);
            
            if($solving->create()) {
                // Update status komplain menjadi "solved"
                try {
                    $update_komplain = $db->prepare("UPDATE komplain SET status = 'solved' WHERE id = ?");
                    $update_komplain->execute([$solving->idkomplain]);
                } catch (Exception $e) {
                    // Log error but don't stop the process
                    error_log("Failed to update komplain status: " . $e->getMessage());
                }
                
                // Redirect untuk mencegah resubmission
                header('Location: solving.php?success=create');
                exit();
            } else {
                $message = 'Gagal membuat solving!';
                $message_type = 'danger';
            }
            break;
            
        case 'update':
            $solving->id = $_POST['id'];
            $solving->subyek = $_POST['subyek'];
            $solving->solving = $_POST['solving'];
            $solving->idkomplain = $_POST['idkomplain'];
            $solving->idsupport = $_POST['idsupport'];
            $solving->iddeveloper = $_SESSION['user_id'];
            
            // Handle image uploads
            $images = [];
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/solving/images/';
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
            $solving->image = json_encode($images);
            
            // Handle file uploads
            $files = [];
            if (!empty($_FILES['uploadfiles']['name'][0])) {
                $upload_dir = 'uploads/solving/files/';
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
            $solving->uploadfiles = json_encode($files);
            
            if($solving->update()) {
                // Update status komplain menjadi "solved"
                try {
                    $update_komplain = $db->prepare("UPDATE komplain SET status = 'solved' WHERE id = ?");
                    $update_komplain->execute([$solving->idkomplain]);
                } catch (Exception $e) {
                    // Log error but don't stop the process
                    error_log("Failed to update komplain status: " . $e->getMessage());
                }
                
                // Redirect untuk mencegah resubmission
                header('Location: solving.php?success=update');
                exit();
            } else {
                $message = 'Gagal memperbarui solving!';
                $message_type = 'danger';
            }
            break;
            
        case 'delete':
            $solving->id = $_POST['id'];
            
            if($solving->delete()) {
                // Redirect untuk mencegah resubmission
                header('Location: solving.php?success=delete');
                exit();
            } else {
                $message = 'Gagal menghapus solving!';
                $message_type = 'danger';
            }
            break;
    }
}

// Handle success messages from redirect
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'create':
            $message = 'Solving berhasil dibuat!';
            $message_type = 'success';
            break;
        case 'update':
            $message = 'Solving berhasil diperbarui!';
            $message_type = 'success';
            break;
        case 'delete':
            $message = 'Solving berhasil dihapus!';
            $message_type = 'success';
            break;
    }
}

// Get komplain proses and support users for dropdowns
$komplain_proses = $solving->getKomplainProses();
$support_users = $solving->getSupportUsers();

// Start output buffering
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Data Solving</h1>
        <?php if ($current_user->role === 'admin' || $current_user->role === 'support' || $current_user->support == 1 || $current_user->developer == 1): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSolvingModal">
            <i class="fas fa-plus"></i> Tambah Solving
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
                <div class="col-md-6">
                    <label for="search" class="form-label">Cari Solving</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Subyek, solving, support, atau klien...">
                </div>
                <div class="col-md-2">
                    <label for="limit" class="form-label">Limit</label>
                    <select class="form-select" id="limit" name="limit" onchange="this.form.submit()">
                        <?php foreach($allowed_limits as $l): ?>
                            <option value="<?php echo $l; ?>" <?php echo $limit == $l ? 'selected' : ''; ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
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

    <!-- Solving Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th><a href="<?php echo getSortUrl('subyek', $sort_by, $sort_order, $search, $limit); ?>" class="text-white text-decoration-none">Subyek <?php echo $sort_by == 'subyek' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('komplain_subyek', $sort_by, $sort_order, $search, $limit); ?>" class="text-white text-decoration-none">Komplain <?php echo $sort_by == 'komplain_subyek' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('support_name', $sort_by, $sort_order, $search, $limit); ?>" class="text-white text-decoration-none">Support <?php echo $sort_by == 'support_name' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('client_name', $sort_by, $sort_order, $search, $limit); ?>" class="text-white text-decoration-none">Client <?php echo $sort_by == 'client_name' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('created_at', $sort_by, $sort_order, $search, $limit); ?>" class="text-white text-decoration-none">Tanggal <?php echo $sort_by == 'created_at' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($solving_data->rowCount() == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data solving</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $counter = ($page - 1) * $limit + 1;
                            while($s = $solving_data->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><?php echo htmlspecialchars($s['subyek']); ?></td>
                                    <td>
                                        <?php if(!empty($s['komplain_subyek'])): ?>
                                            <span><?php echo htmlspecialchars($s['komplain_subyek']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($s['support_name'])): ?>
                                            <span><?php echo htmlspecialchars($s['support_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($s['client_name'])): ?>
                                            <span><?php echo htmlspecialchars($s['client_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($s['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info view-btn" 
                                                data-solving='<?php echo json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($current_user->role !== 'support' && $current_user->support != 1): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-btn" 
                                                data-solving='<?php echo json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                                                data-id="<?php echo $s['id']; ?>" 
                                                data-subyek="<?php echo htmlspecialchars($s['subyek']); ?>">
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
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&limit=<?php echo $limit; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&limit=<?php echo $limit; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&limit=<?php echo $limit; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<!-- Add Solving Modal -->
<div class="modal fade" id="addSolvingModal" tabindex="-1" aria-labelledby="addSolvingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSolvingModalLabel">Tambah Solving</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="subyek" class="form-label">Subyek <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subyek" name="subyek" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="idkomplain" class="form-label">Komplain <span class="text-danger">*</span></label>
                            <select class="form-select" id="idkomplain" name="idkomplain" required>
                                <option value="">Pilih Komplain</option>
                                <?php foreach($komplain_proses as $kp): ?>
                                    <option value="<?php echo $kp['id']; ?>">
                                        <?php echo htmlspecialchars($kp['subyek'] . ' - ' . $kp['client_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="idsupport" class="form-label">Support User <span class="text-danger">*</span></label>
                            <select class="form-select" id="idsupport" name="idsupport" required>
                                <option value="">Pilih Support User</option>
                                <?php foreach($support_users as $su): ?>
                                    <option value="<?php echo $su['id']; ?>">
                                        <?php echo htmlspecialchars($su['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="solving" class="form-label">Solving <span class="text-danger">*</span></label>
                        <div id="solving"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="images" class="form-label">Images</label>
                            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                            <div class="form-text">Format: JPG, PNG, GIF, WebP. Max 5MB per file.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="uploadfiles" class="form-label">Upload Files</label>
                            <input type="file" class="form-control" id="uploadfiles" name="uploadfiles[]" multiple>
                            <div class="form-text">Format: PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP, RAR. Max 10MB per file.</div>
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

<!-- Edit Solving Modal -->
<div class="modal fade" id="editSolvingModal" tabindex="-1" aria-labelledby="editSolvingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSolvingModalLabel">Edit Solving</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label for="edit_subyek" class="form-label">Subyek <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_subyek" name="subyek" required>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="edit_komplain_display" class="form-label">Komplain</label>
                            <input type="text" class="form-control" id="edit_komplain_display" readonly>
                            <input type="hidden" id="edit_idkomplain" name="idkomplain">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_idsupport" class="form-label">Support User <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_idsupport" name="idsupport" required>
                                <option value="">Pilih Support User</option>
                                <?php foreach($support_users as $su): ?>
                                    <option value="<?php echo $su['id']; ?>">
                                        <?php echo htmlspecialchars($su['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_solving" class="form-label">Solving <span class="text-danger">*</span></label>
                        <div id="edit_solving"></div>
                    </div>

                    <div class="row">    
                        <div class="col-md-6 mb-3">
                            <label for="edit_images" class="form-label">Images</label>
                                <input type="file" class="form-control" id="edit_images" name="images[]" multiple accept="image/*">
                                <div class="form-text">Format: JPG, PNG, GIF, WebP. Max 5MB per file.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_uploadfiles" class="form-label">Upload Files</label>
                            <input type="file" class="form-control" id="edit_uploadfiles" name="uploadfiles[]" multiple>
                            <div class="form-text">Format: PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP, RAR. Max 10MB per file.</div>
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

<!-- View Solving Modal -->
<div class="modal fade" id="viewSolvingModal" tabindex="-1" aria-labelledby="viewSolvingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSolvingModalLabel">Detail Solving</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="view_solving_content"></div>
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
                <p>Apakah Anda yakin ingin menghapus solving "<span id="delete_subyek"></span>"?</p>
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

<!-- Komplain Detail Modal -->
<div class="modal fade" id="komplainDetailModal" tabindex="-1" aria-labelledby="komplainDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="komplainDetailModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Detail Komplain
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted">Subyek Komplain:</h6>
                        <p class="fw-bold" id="komplain_subyek_detail">-</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Status:</h6>
                        <span class="badge bg-info" id="komplain_status_detail">-</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted">Tanggal Komplain:</h6>
                        <p id="komplain_tanggal_detail">-</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Client:</h6>
                        <p id="komplain_client_detail">-</p>
                    </div>
                </div>
                <div class="mb-3">
                    <h6 class="text-muted">Isi Komplain:</h6>
                    <div class="border rounded p-3 bg-light" id="komplain_text_detail" style="min-height: 100px;">
                        -
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
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageZoomModalLabel">
                    <i class="fas fa-image me-2"></i>
                    Gambar Solving
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="imageContainer" style="position: relative; overflow: hidden; max-height: 80vh;">
                    <img id="zoomedImage" src="" alt="Gambar Solving" 
                         style="max-width: 100%; max-height: 80vh; cursor: grab; transition: transform 0.3s ease;"
                         draggable="false">
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomIn()">
                        <i class="fas fa-search-plus"></i> Zoom In
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="zoomOut()">
                        <i class="fas fa-search-minus"></i> Zoom Out
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetZoom()">
                        <i class="fas fa-expand-arrows-alt"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables for Quill editors
let solvingQuill, editSolvingQuill;

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
        placeholder: 'Ketik solving Anda di sini...'
    };
    
    // Auto-fill idsupport when komplain is selected
    const komplainSelect = document.getElementById('idkomplain');
    const supportSelect = document.getElementById('idsupport');
    
    if (komplainSelect && supportSelect) {
        komplainSelect.addEventListener('change', function() {
            const selectedKomplainId = this.value;
            if (selectedKomplainId) {
                // Get komplain data from PHP
                const komplainData = <?php echo json_encode($komplain_proses); ?>;
                const selectedKomplain = komplainData.find(k => k.id == selectedKomplainId);
                
                if (selectedKomplain && selectedKomplain.idsupport) {
                    // Set the support user based on komplain's idsupport
                    supportSelect.value = selectedKomplain.idsupport;
                }
            } else {
                // Reset support selection if no komplain selected
                supportSelect.value = '';
            }
        });
    }
    
    // Initialize create form Quill
    if (document.getElementById('solving')) {
        solvingQuill = new Quill('#solving', quillConfig);
    }
    
    // Initialize edit form Quill
    if (document.getElementById('edit_solving')) {
        editSolvingQuill = new Quill('#edit_solving', quillConfig);
    }
    
    // Handle form submission
    const addForm = document.querySelector('#addSolvingModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const solvingContent = solvingQuill.root.innerHTML;
            const solvingInput = document.createElement('input');
            solvingInput.type = 'hidden';
            solvingInput.name = 'solving';
            solvingInput.value = solvingContent;
            this.appendChild(solvingInput);
        });
    }
    
    const editForm = document.querySelector('#editSolvingModal form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const solvingContent = editSolvingQuill.root.innerHTML;
            const solvingInput = document.createElement('input');
            solvingInput.type = 'hidden';
            solvingInput.name = 'solving';
            solvingInput.value = solvingContent;
            this.appendChild(solvingInput);
        });
    }
    
    // View button click
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const solving = JSON.parse(this.dataset.solving);
            viewSolving(solving);
        });
    });
    
    // Edit button click
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const solving = JSON.parse(this.dataset.solving);
            editSolving(solving);
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
    document.getElementById('addSolvingModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('addSolvingModal').querySelector('form').reset();
        if (solvingQuill) {
            solvingQuill.setContents([]);
        }
    });
    
    document.getElementById('editSolvingModal').addEventListener('hidden.bs.modal', function() {
        if (editSolvingQuill) {
            editSolvingQuill.setContents([]);
        }
    });
});

function viewSolving(solving) {
    let content = `
        <div class="row">
            <div class="col-md-6">
                <h6>Subyek:</h6>
                <p>${solving.subyek}</p>
            </div>
            <div class="col-md-6">
                <h6>Komplain:</h6>
                <p onclick="showKomplainModal('${solving.komplain_subyek || 'N/A'}', '${solving.komplain_status || 'N/A'}', '${solving.komplain_created_at ? new Date(solving.komplain_created_at).toLocaleString('id-ID') : 'N/A'}', '${solving.client_name || 'N/A'}', '${solving.komplain_text || 'N/A'}')" 
                   style="cursor: pointer; text-decoration: underline; color: #007bff;">
                    ${solving.komplain_subyek || '-'}
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h6>Support:</h6>
                <p>${solving.support_name || '-'}</p>
            </div>
            <div class="col-md-6">
                <h6>Client:</h6>
                <p>${solving.client_name || '-'}</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h6>Developer:</h6>
                <p>${solving.developer_name || '-'}</p>
            </div>
            <div class="col-md-6">
                <h6>Tanggal Komplain:</h6>
                <p>${solving.komplain_created_at ? new Date(solving.komplain_created_at).toLocaleString('id-ID') : '-'}</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h6>Tanggal Dibuat/Update:</h6>
                <p>${new Date(solving.created_at).toLocaleString('id-ID')} || ${new Date(solving.updated_at).toLocaleString('id-ID')}</p>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <h6>Solving:</h6>
                <div class="border p-3 rounded">${solving.solving}</div>
            </div>
        </div>
    `;
    
    // Add images if available
    if (solving.image && solving.image !== 'null' && solving.image !== '') {
        try {
            const images = JSON.parse(solving.image);
            if (Array.isArray(images) && images.length > 0) {
                content += `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Gambar Solving:</h6>
                            <div class="row">
                `;
                images.forEach((image, index) => {
                    content += `
                        <div class="col-md-3 mb-3">
                            <div class="card">
                                <img src="uploads/solving/images/${image}" class="card-img-top" style="height: 150px; object-fit: cover;" 
                                     onclick="openImageModal('uploads/solving/images/${image}')" 
                                     style="cursor: pointer;">
                                <div class="card-body p-2">
                                    <small class="text-muted">Gambar ${index + 1}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                content += `
                            </div>
                        </div>
                    </div>
                `;
            }
        } catch (e) {
            console.log('Error parsing images:', e);
        }
    }
    
    // Add upload files if available
    if (solving.uploadfiles && solving.uploadfiles !== 'null' && solving.uploadfiles !== '') {
        try {
            const files = JSON.parse(solving.uploadfiles);
            if (Array.isArray(files) && files.length > 0) {
                content += `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>File Upload Solving:</h6>
                            <div class="list-group">
                `;
                files.forEach((file, index) => {
                    const fileExtension = file.split('.').pop().toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(fileExtension);
                    const isPdf = fileExtension === 'pdf';
                    const isDoc = ['doc', 'docx'].includes(fileExtension);
                    const isExcel = ['xls', 'xlsx'].includes(fileExtension);
                    
                    let iconClass = 'fas fa-file';
                    if (isImage) iconClass = 'fas fa-image';
                    else if (isPdf) iconClass = 'fas fa-file-pdf';
                    else if (isDoc) iconClass = 'fas fa-file-word';
                    else if (isExcel) iconClass = 'fas fa-file-excel';
                    
                    content += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="${iconClass} me-2"></i>
                                <span>${file}</span>
                            </div>
                            <a href="uploads/solving/files/${file}" class="btn btn-sm btn-outline-primary" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    `;
                });
                content += `
                            </div>
                        </div>
                    </div>
                `;
            }
        } catch (e) {
            console.log('Error parsing upload files:', e);
        }
    }
    
    document.getElementById('view_solving_content').innerHTML = content;
    new bootstrap.Modal(document.getElementById('viewSolvingModal')).show();
}

function editSolving(solving) {
    document.getElementById('edit_id').value = solving.id;
    document.getElementById('edit_subyek').value = solving.subyek;
    document.getElementById('edit_idkomplain').value = solving.idkomplain;
    document.getElementById('edit_idsupport').value = solving.idsupport;
    
    // Set komplain display (read-only)
    document.getElementById('edit_komplain_display').value = solving.komplain_subyek + ' - ' + solving.client_name;
    
    if (editSolvingQuill) {
        editSolvingQuill.root.innerHTML = solving.solving;
    }
    
    new bootstrap.Modal(document.getElementById('editSolvingModal')).show();
}

// Function to show komplain detail modal
function showKomplainModal(subyek, status, tanggal, client, text) {
    // Set modal content
    document.getElementById('komplain_subyek_detail').textContent = subyek;
    document.getElementById('komplain_status_detail').textContent = status;
    document.getElementById('komplain_tanggal_detail').textContent = tanggal;
    document.getElementById('komplain_client_detail').textContent = client;
    
    // Set komplain text (handle HTML content)
    const textElement = document.getElementById('komplain_text_detail');
    if (text && text !== 'N/A') {
        textElement.innerHTML = text;
    } else {
        textElement.innerHTML = '<em class="text-muted">Tidak ada isi komplain tersedia</em>';
    }
    
    // Show modal
    new bootstrap.Modal(document.getElementById('komplainDetailModal')).show();
}

// Image zoom and pan functionality
let currentScale = 1;
let isDragging = false;
let startX, startY, translateX = 0, translateY = 0;

function openImageModal(imageSrc) {
    document.getElementById('zoomedImage').src = imageSrc;
    resetZoom();
    new bootstrap.Modal(document.getElementById('imageZoomModal')).show();
}

function zoomIn() {
    currentScale = Math.min(currentScale * 1.2, 5);
    updateImageTransform();
}

function zoomOut() {
    currentScale = Math.max(currentScale / 1.2, 0.1);
    updateImageTransform();
}

function resetZoom() {
    currentScale = 1;
    translateX = 0;
    translateY = 0;
    updateImageTransform();
}

function updateImageTransform() {
    const img = document.getElementById('zoomedImage');
    img.style.transform = `scale(${currentScale}) translate(${translateX}px, ${translateY}px)`;
}

// Image drag functionality
document.addEventListener('DOMContentLoaded', function() {
    const img = document.getElementById('zoomedImage');
    if (img) {
        img.addEventListener('mousedown', function(e) {
            if (currentScale > 1) {
                isDragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                img.style.cursor = 'grabbing';
            }
        });

        document.addEventListener('mousemove', function(e) {
            if (isDragging && currentScale > 1) {
                translateX = e.clientX - startX;
                translateY = e.clientY - startY;
                updateImageTransform();
            }
        });

        document.addEventListener('mouseup', function() {
            if (isDragging) {
                isDragging = false;
                img.style.cursor = 'grab';
            }
        });

        // Reset drag when modal is hidden
        document.getElementById('imageZoomModal').addEventListener('hidden.bs.modal', function() {
            isDragging = false;
            resetZoom();
        });
    }
});
</script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include layout
include 'includes/layout.php';
?>
