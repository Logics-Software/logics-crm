<?php
require_once 'config/database.php';
require_once 'models/Project.php';
require_once 'models/User.php';
require_once 'includes/session.php';
require_once 'includes/layout_helper.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$project = new Project($db);

$message = '';
$message_type = '';

// Get parameters for pagination, search, and sorting
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'namaproyek';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Validate limit
$allowed_limits = [5, 10, 25, 50, 100];
if (!in_array($limit, $allowed_limits)) {
    $limit = 10;
}

// Validate sort parameters
$allowed_sort_fields = ['namaproyek', 'namaperusahaan', 'nilaiproyek', 'status', 'tanggalkontrak', 'id', 'created_at'];
if (!in_array($sort_by, $allowed_sort_fields)) {
    $sort_by = 'namaproyek';
}

if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

if($page < 1) $page = 1;

// Handle form submissions
if($_POST) {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'create':
                $project->namaproyek = $_POST['namaproyek'];
                $project->namaperusahaan = $_POST['namaperusahaan'];
                $project->deskripsiproyek = $_POST['deskripsiproyek'];
                $project->tanggalkontrak = $_POST['tanggalkontrak'];
                $project->lamapengerjaan = $_POST['lamapengerjaan'];
                $project->tanggalselesai = $_POST['tanggalselesai'];
                $project->developer = $_POST['developer'];
                $project->nilaiproyek = (int)str_replace('.', '', $_POST['nilaiproyek']);
                $project->jumlahtermin = (int)$_POST['jumlahtermin'];
                $project->termin1 = (int)str_replace('.', '', $_POST['termin1']);
                $project->termin2 = (int)str_replace('.', '', $_POST['termin2']);
                $project->termin3 = (int)str_replace('.', '', $_POST['termin3']);
                $project->termin4 = (int)str_replace('.', '', $_POST['termin4']);
                $project->saldoproyek = (int)str_replace('.', '', $_POST['saldoproyek']);
                $project->status = $_POST['status'];
                
                if($project->create()) {
                    $message = 'Data project berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menambahkan data project!';
                    $message_type = 'danger';
                }
                break;
                
            case 'update':
                $project->id = $_POST['id'];
                $project->namaproyek = $_POST['namaproyek'];
                $project->namaperusahaan = $_POST['namaperusahaan'];
                $project->deskripsiproyek = $_POST['deskripsiproyek'];
                $project->tanggalkontrak = $_POST['tanggalkontrak'];
                $project->lamapengerjaan = $_POST['lamapengerjaan'];
                $project->tanggalselesai = $_POST['tanggalselesai'];
                $project->developer = $_POST['developer'];
                $project->nilaiproyek = (int)str_replace('.', '', $_POST['nilaiproyek']);
                $project->jumlahtermin = (int)$_POST['jumlahtermin'];
                $project->termin1 = (int)str_replace('.', '', $_POST['termin1']);
                $project->termin2 = (int)str_replace('.', '', $_POST['termin2']);
                $project->termin3 = (int)str_replace('.', '', $_POST['termin3']);
                $project->termin4 = (int)str_replace('.', '', $_POST['termin4']);
                $project->saldoproyek = (int)str_replace('.', '', $_POST['saldoproyek']);
                $project->status = $_POST['status'];
                
                if($project->update()) {
                    $message = 'Data project berhasil diperbarui!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal memperbarui data project!';
                    $message_type = 'danger';
                }
                break;
                
            case 'delete':
                $project->id = $_POST['id'];
                if($project->delete()) {
                    $message = 'Data project berhasil dihapus!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menghapus data project!';
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get projects data with pagination
$projects_list = $project->getProjectsWithPagination($page, $limit, $search, $status_filter, $sort_by, $sort_order);
$total_projects = $project->getTotalProjects($search, $status_filter);
$total_pages = ceil($total_projects / $limit);

// Helper function for sort URLs
function getSortUrl($column, $search, $status_filter, $current_order, $limit) {
    $new_order = ($current_order === 'ASC') ? 'DESC' : 'ASC';
    return "?sort=$column&order=$new_order&search=" . urlencode($search) . "&status=" . urlencode($status_filter) . "&limit=$limit";
}

// Start layout with buffer
startLayoutBuffer('Data Project - Logics Software');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Data Project</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProjectModal">
            <i class="fas fa-plus"></i> Tambah Project
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
                    <label for="search" class="form-label">Cari Project</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Nama project atau perusahaan..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="kontrak" <?php echo $status_filter == 'kontrak' ? 'selected' : ''; ?>>Kontrak</option>
                        <option value="develop" <?php echo $status_filter == 'develop' ? 'selected' : ''; ?>>Develop</option>
                        <option value="implementasi" <?php echo $status_filter == 'implementasi' ? 'selected' : ''; ?>>Implementasi</option>
                        <option value="garansi" <?php echo $status_filter == 'garansi' ? 'selected' : ''; ?>>Garansi</option>
                        <option value="selesai" <?php echo $status_filter == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                        <option value="perawatan" <?php echo $status_filter == 'perawatan' ? 'selected' : ''; ?>>Perawatan</option>
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
                        <a href="project.php" class="btn btn-secondary">
                            <i class="fas fa-refresh"></i> Reset
                    </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th><a href="<?php echo getSortUrl('namaproyek', $search, $status_filter, $sort_order, $limit); ?>" class="text-white text-decoration-none">Nama Project <?php echo $sort_by == 'namaproyek' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('namaperusahaan', $search, $status_filter, $sort_order, $limit); ?>" class="text-white text-decoration-none">Perusahaan <?php echo $sort_by == 'namaperusahaan' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('nilaiproyek', $search, $status_filter, $sort_order, $limit); ?>" class="text-white text-decoration-none">Nilai Project <?php echo $sort_by == 'nilaiproyek' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('status', $search, $status_filter, $sort_order, $limit); ?>" class="text-white text-decoration-none">Status <?php echo $sort_by == 'status' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th><a href="<?php echo getSortUrl('tanggalkontrak', $search, $status_filter, $sort_order, $limit); ?>" class="text-white text-decoration-none">Tgl.Kontrak <?php echo $sort_by == 'tanggalkontrak' ? ($sort_order == 'ASC' ? '↑' : '↓') : ''; ?></a></th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($projects_list)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    Belum ada data project
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($projects_list as $index => $p): ?>
                                <tr>
                                    <td><?php echo ($page - 1) * $limit + $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($p['namaproyek']); ?></td>
                                    <td><?php echo htmlspecialchars($p['namaperusahaan']); ?></td>
                                    <td>Rp <?php echo number_format($p['nilaiproyek'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $p['status'] == 'kontrak' ? 'secondary' : 
                                                ($p['status'] == 'develop' ? 'primary' : 
                                                ($p['status'] == 'implementasi' ? 'info' : 
                                                ($p['status'] == 'garansi' ? 'warning' : 
                                                ($p['status'] == 'selesai' ? 'success' : 'dark')))); 
                                        ?>">
                                            <?php echo ucfirst($p['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($p['tanggalkontrak'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-btn" 
                                                data-project='<?php echo json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-btn" 
                                                data-id="<?php echo $p['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($p['namaproyek']); ?>">
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&limit=<?php echo $limit; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&limit=<?php echo $limit; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&order=<?php echo urlencode($sort_order); ?>&limit=<?php echo $limit; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProjectModalLabel">Tambah Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="namaproyek" class="form-label">Nama Project <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="namaproyek" name="namaproyek" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="namaperusahaan" class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="namaperusahaan" name="namaperusahaan" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deskripsiproyek" class="form-label">Deskripsi Project</label>
                        <div id="deskripsiproyek" style="min-height: 200px;"></div>
                        <textarea name="deskripsiproyek" style="display: none;"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="tanggalkontrak" class="form-label">Tanggal Kontrak <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggalkontrak" name="tanggalkontrak" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="lamapengerjaan" class="form-label">Lama Pengerjaan (Hari)</label>
                            <input type="number" class="form-control" id="lamapengerjaan" name="lamapengerjaan" min="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="tanggalselesai" class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" id="tanggalselesai" name="tanggalselesai">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="developer" class="form-label">Developer</label>
                        <div id="developer" style="min-height: 200px;"></div>
                        <textarea name="developer" style="display: none;"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nilaiproyek" class="form-label">Nilai Project <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nilaiproyek" name="nilaiproyek" required placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="jumlahtermin" class="form-label">Jumlah Termin</label>
                            <input type="number" class="form-control" id="jumlahtermin" name="jumlahtermin" min="1" max="4" value="1">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="termin1" class="form-label">Termin 1</label>
                            <input type="text" class="form-control" id="termin1" name="termin1" placeholder="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="termin2" class="form-label">Termin 2</label>
                            <input type="text" class="form-control" id="termin2" name="termin2" placeholder="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="termin3" class="form-label">Termin 3</label>
                            <input type="text" class="form-control" id="termin3" name="termin3" placeholder="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="termin4" class="form-label">Termin 4</label>
                            <input type="text" class="form-control" id="termin4" name="termin4" placeholder="0">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="saldoproyek" class="form-label">Saldo Project</label>
                            <input type="text" class="form-control" id="saldoproyek" name="saldoproyek" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Pilih Status</option>
                                <option value="kontrak">Kontrak</option>
                                <option value="develop">Develop</option>
                                <option value="implementasi">Implementasi</option>
                                <option value="garansi">Garansi</option>
                                <option value="selesai">Selesai</option>
                                <option value="perawatan">Perawatan</option>
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

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_namaproyek" class="form-label">Nama Project <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_namaproyek" name="namaproyek" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_namaperusahaan" class="form-label">Nama Perusahaan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_namaperusahaan" name="namaperusahaan" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_deskripsiproyek" class="form-label">Deskripsi Project</label>
                        <div id="edit_deskripsiproyek" style="min-height: 200px;"></div>
                        <textarea name="deskripsiproyek" style="display: none;"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_tanggalkontrak" class="form-label">Tanggal Kontrak <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_tanggalkontrak" name="tanggalkontrak" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_lamapengerjaan" class="form-label">Lama Pengerjaan (Hari)</label>
                            <input type="number" class="form-control" id="edit_lamapengerjaan" name="lamapengerjaan" min="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_tanggalselesai" class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" id="edit_tanggalselesai" name="tanggalselesai">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_developer" class="form-label">Developer</label>
                        <div id="edit_developer" style="min-height: 200px;"></div>
                        <textarea name="developer" style="display: none;"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_nilaiproyek" class="form-label">Nilai Project <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_nilaiproyek" name="nilaiproyek" required placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_jumlahtermin" class="form-label">Jumlah Termin</label>
                            <input type="number" class="form-control" id="edit_jumlahtermin" name="jumlahtermin" min="1" max="4" value="1">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="edit_termin1" class="form-label">Termin 1</label>
                            <input type="text" class="form-control" id="edit_termin1" name="termin1" placeholder="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_termin2" class="form-label">Termin 2</label>
                            <input type="text" class="form-control" id="edit_termin2" name="termin2" placeholder="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_termin3" class="form-label">Termin 3</label>
                            <input type="text" class="form-control" id="edit_termin3" name="termin3" placeholder="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_termin4" class="form-label">Termin 4</label>
                            <input type="text" class="form-control" id="edit_termin4" name="termin4" placeholder="0">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_saldoproyek" class="form-label">Saldo Project</label>
                            <input type="text" class="form-control" id="edit_saldoproyek" name="saldoproyek" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="">Pilih Status</option>
                                <option value="kontrak">Kontrak</option>
                                <option value="develop">Develop</option>
                                <option value="implementasi">Implementasi</option>
                                <option value="garansi">Garansi</option>
                                <option value="selesai">Selesai</option>
                                <option value="perawatan">Perawatan</option>
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
                <p>Apakah Anda yakin ingin menghapus project <strong id="deleteProjectName"></strong>?</p>
                <p class="text-danger">Tindakan ini tidak dapat dibatalkan!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteProjectId">
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

<style>
/* Hide sidebar on mobile */
@media (max-width: 767.98px) {
    .sidebar {
        display: none !important;
    }
}

/* Custom styles for better UX */
.table th {
    border-top: none;
    font-weight: 600;
}

/* Termin fields styling */
.termin-field-hidden {
    display: none !important;
}

.termin-field-visible {
    display: block !important;
}

/* Smooth transition for termin fields */
.col-md-3 {
    transition: all 0.3s ease;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
}

.pagination {
    margin-top: 1rem;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.alert {
    border: none;
    border-radius: 0.5rem;
}

.modal-content {
    border: none;
    border-radius: 0.5rem;
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
}
</style>

<script>
// Format number input with thousand separators
document.addEventListener('DOMContentLoaded', function() {
    // Format number inputs
    const numberInputs = document.querySelectorAll('#nilaiproyek, #edit_nilaiproyek, #termin1, #edit_termin1, #termin2, #edit_termin2, #termin3, #edit_termin3, #termin4, #edit_termin4, #saldoproyek, #edit_saldoproyek');
    numberInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/[^\d]/g, '');
            if (value) {
                this.value = parseInt(value).toLocaleString('id-ID');
            } else {
                this.value = '';
            }
        });
    });

    // Clean number inputs and save Quill content before form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            // Save Quill content to hidden textareas before form submission
            if (deskripsiQuill) {
                const hiddenTextarea = this.querySelector('textarea[name="deskripsiproyek"]');
                if (hiddenTextarea) {
                    hiddenTextarea.value = deskripsiQuill.root.innerHTML;
                }
            }
            if (developerQuill) {
                const hiddenTextarea = this.querySelector('textarea[name="developer"]');
                if (hiddenTextarea) {
                    hiddenTextarea.value = developerQuill.root.innerHTML;
                }
            }
            if (editDeskripsiQuill) {
                const hiddenTextarea = this.querySelector('textarea[name="deskripsiproyek"]');
                if (hiddenTextarea) {
                    hiddenTextarea.value = editDeskripsiQuill.root.innerHTML;
                }
            }
            if (editDeveloperQuill) {
                const hiddenTextarea = this.querySelector('textarea[name="developer"]');
                if (hiddenTextarea) {
                    hiddenTextarea.value = editDeveloperQuill.root.innerHTML;
                }
            }
            
            // Clean number inputs
            const numberInputs = this.querySelectorAll('#nilaiproyek, #edit_nilaiproyek, #termin1, #edit_termin1, #termin2, #edit_termin2, #termin3, #edit_termin3, #termin4, #edit_termin4, #saldoproyek, #edit_saldoproyek');
            numberInputs.forEach(input => {
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
            document.getElementById('deleteProjectId').value = id;
            document.getElementById('deleteProjectName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

    // Handle edit button clicks
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const projectData = JSON.parse(this.getAttribute('data-project'));
            editProject(projectData);
        });
    });
});

// Function to toggle termin fields based on jumlah termin (Global function)
function toggleTerminFields(formType) {
    const prefix = formType === 'edit' ? 'edit_' : '';
    const jumlahTerminInput = document.getElementById(prefix + 'jumlahtermin');
    const termin1Div = document.getElementById(prefix + 'termin1').closest('.col-md-3');
    const termin2Div = document.getElementById(prefix + 'termin2').closest('.col-md-3');
    const termin3Div = document.getElementById(prefix + 'termin3').closest('.col-md-3');
    const termin4Div = document.getElementById(prefix + 'termin4').closest('.col-md-3');
    
    if (jumlahTerminInput && termin1Div && termin2Div && termin3Div && termin4Div) {
        const jumlahTermin = parseInt(jumlahTerminInput.value) || 1;
        
        // Hide all termin fields first
        termin1Div.style.display = 'none';
        termin2Div.style.display = 'none';
        termin3Div.style.display = 'none';
        termin4Div.style.display = 'none';
        
        // Show termin fields based on jumlah termin
        if (jumlahTermin >= 1) {
            termin1Div.style.display = 'block';
        }
        if (jumlahTermin >= 2) {
            termin2Div.style.display = 'block';
        }
        if (jumlahTermin >= 3) {
            termin3Div.style.display = 'block';
        }
        if (jumlahTermin >= 4) {
            termin4Div.style.display = 'block';
        }
        
        // Clear values for hidden termin fields
        if (jumlahTermin < 2) {
            document.getElementById(prefix + 'termin2').value = '';
        }
        if (jumlahTermin < 3) {
            document.getElementById(prefix + 'termin3').value = '';
        }
        if (jumlahTermin < 4) {
            document.getElementById(prefix + 'termin4').value = '';
        }
    }
}

function editProject(project) {
    try {
        document.getElementById('edit_id').value = project.id;
        document.getElementById('edit_namaproyek').value = project.namaproyek;
        document.getElementById('edit_namaperusahaan').value = project.namaperusahaan;
        document.getElementById('edit_deskripsiproyek').value = project.deskripsiproyek;
        document.getElementById('edit_tanggalkontrak').value = project.tanggalkontrak;
        document.getElementById('edit_lamapengerjaan').value = project.lamapengerjaan;
        document.getElementById('edit_tanggalselesai').value = project.tanggalselesai;
        document.getElementById('edit_developer').value = project.developer;
        document.getElementById('edit_nilaiproyek').value = project.nilaiproyek ? parseInt(project.nilaiproyek).toLocaleString('id-ID') : '';
        document.getElementById('edit_jumlahtermin').value = project.jumlahtermin;
        document.getElementById('edit_termin1').value = project.termin1 ? parseInt(project.termin1).toLocaleString('id-ID') : '';
        document.getElementById('edit_termin2').value = project.termin2 ? parseInt(project.termin2).toLocaleString('id-ID') : '';
        document.getElementById('edit_termin3').value = project.termin3 ? parseInt(project.termin3).toLocaleString('id-ID') : '';
        document.getElementById('edit_termin4').value = project.termin4 ? parseInt(project.termin4).toLocaleString('id-ID') : '';
        document.getElementById('edit_saldoproyek').value = project.saldoproyek ? parseInt(project.saldoproyek).toLocaleString('id-ID') : '';
        document.getElementById('edit_status').value = project.status;
        
        // Toggle termin fields based on jumlah termin
        toggleTerminFields('edit');
        
        const modal = new bootstrap.Modal(document.getElementById('editProjectModal'));
        modal.show();
        
        // Update Quill content after modal is shown
        setTimeout(() => {
            if (editDeskripsiQuill) {
                editDeskripsiQuill.root.innerHTML = project.deskripsiproyek || '';
            }
            if (editDeveloperQuill) {
                editDeveloperQuill.root.innerHTML = project.developer || '';
            }
        }, 1000);
    } catch (error) {
        showAlertModal('Error: ' + error.message);
    }
}

// Function to show alert modal
function showAlertModal(message) {
    document.getElementById('alertMessage').textContent = message;
    const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
    alertModal.show();
}

// Initialize Quill.js Rich Text Editor
let deskripsiQuill, developerQuill, editDeskripsiQuill, editDeveloperQuill;

const quillConfig = {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'align': [] }],
            ['link'],
            ['clean']
        ]
    },
    placeholder: 'Ketik deskripsi project...',
    bounds: document.body
};

// Initialize Quill editors when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Handle jumlah termin change for create form
    const jumlahTerminInput = document.getElementById('jumlahtermin');
    if (jumlahTerminInput) {
        jumlahTerminInput.addEventListener('change', function() {
            toggleTerminFields('create');
        });
    }
    
    // Handle jumlah termin change for edit form
    const editJumlahTerminInput = document.getElementById('edit_jumlahtermin');
    if (editJumlahTerminInput) {
        editJumlahTerminInput.addEventListener('change', function() {
            toggleTerminFields('edit');
        });
    }
    
    // Check if Quill is available
    if (typeof Quill === 'undefined') {
        console.error('Quill.js is not loaded');
        return;
    }
    
    console.log('Initializing Quill editors...');
    
    // Initialize Quill editors
    try {
        if (document.getElementById('deskripsiproyek')) {
            deskripsiQuill = new Quill('#deskripsiproyek', quillConfig);
            console.log('Deskripsi Quill initialized');
        }
        if (document.getElementById('developer')) {
            developerQuill = new Quill('#developer', quillConfig);
            console.log('Developer Quill initialized');
        }
        if (document.getElementById('edit_deskripsiproyek')) {
            editDeskripsiQuill = new Quill('#edit_deskripsiproyek', quillConfig);
            console.log('Edit Deskripsi Quill initialized');
        }
        if (document.getElementById('edit_developer')) {
            editDeveloperQuill = new Quill('#edit_developer', quillConfig);
            console.log('Edit Developer Quill initialized');
        }
    } catch (error) {
        console.error('Error initializing Quill editors:', error);
    }
    
    // Reset Quill content when modals are hidden
    const addModal = document.getElementById('addProjectModal');
    const editModal = document.getElementById('editProjectModal');
    
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function () {
            if (deskripsiQuill) {
                deskripsiQuill.setContents([]);
            }
            if (developerQuill) {
                developerQuill.setContents([]);
            }
        });
        
        // Reset termin fields when add modal is shown
        addModal.addEventListener('show.bs.modal', function() {
            // Reset jumlah termin to 1 and show only termin 1
            document.getElementById('jumlahtermin').value = 1;
            toggleTerminFields('create');
        });
    }
    
    if (editModal) {
        editModal.addEventListener('hidden.bs.modal', function () {
            if (editDeskripsiQuill) {
                editDeskripsiQuill.setContents([]);
            }
            if (editDeveloperQuill) {
                editDeveloperQuill.setContents([]);
            }
        });
    }
});
</script>

<?php
// End layout
endLayout();
?>
