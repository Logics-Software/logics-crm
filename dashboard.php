<?php
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Project.php';
require_once 'models/Komplain.php';
require_once 'models/Solving.php';
require_once 'includes/session.php';
require_once 'includes/layout_helper.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get user info
$user = new User($db);
$user->getUserById($_SESSION['user_id']);

// Determine user role and job
$user_role = $user->role;
$user_job = '';
if ($user->support == 1) $user_job = 'support';
if ($user->developer == 1) $user_job = 'developer';

// Start layout with buffer
startLayoutBuffer('Dashboard - Logics Software');
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($user_role === 'admin'): ?>
                        <!-- ADMIN DASHBOARD -->
                        <?php
                        // Get statistics for admin
                        $query = "SELECT COUNT(*) as total_klien FROM klien WHERE status = 'aktif'";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $total_klien = $stmt->fetch(PDO::FETCH_ASSOC)['total_klien'];

                        // Get klien statistics (aktif and non aktif)
                        $query = "SELECT 
                                    COUNT(*) as total_klien_all,
                                    SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as klien_aktif,
                                    SUM(CASE WHEN status = 'non aktif' THEN 1 ELSE 0 END) as klien_non_aktif
                                  FROM klien";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $klien_stats = $stmt->fetch(PDO::FETCH_ASSOC);

                        // Get klien perawatan statistics
                        $query = "SELECT COUNT(*) as total_klien_perawatan FROM klien WHERE pekerjaan = 'perawatan' AND status = 'aktif'";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $total_klien_perawatan = $stmt->fetch(PDO::FETCH_ASSOC)['total_klien_perawatan'];

                        // Get project statistics
                        $project = new Project($db);
                        $project_stats = $project->getProjectStats();

                        // Get total tagihan from klien with active status
                        $query = "SELECT SUM(jumlahtagihan) as total_tagihan FROM klien WHERE status = 'aktif'";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $total_tagihan = $stmt->fetch(PDO::FETCH_ASSOC)['total_tagihan'] ?? 0;

                        $query = "SELECT COUNT(*) as total_users FROM users WHERE status = 'aktif'";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
                        ?>
                        
                        <!-- Admin Statistics Cards -->
                        <div class="row mb-0">
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-primary me-3">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Total Klien</h6>
                                                <h3 class="mb-0"><?php echo number_format($total_klien); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-success me-3">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Klien Aktif</h6>
                                                <h3 class="mb-0 text-success"><?php echo number_format($klien_stats['klien_aktif']); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-danger me-3">
                                                <i class="fas fa-user-times"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Klien Non Aktif</h6>
                                                <h3 class="mb-0 text-danger"><?php echo number_format($klien_stats['klien_non_aktif']); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-success me-3">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Tagihan Perawatan</h6>
                                                <h4 class="mb-0">Rp <?php echo number_format($total_tagihan, 0, ',', '.'); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-warning me-3">
                                                <i class="fas fa-project-diagram"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Total Project</h6>
                                                <h3 class="mb-0"><?php echo number_format($project_stats['total_projects']); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-danger me-3">
                                                <i class="fas fa-dollar-sign"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Nilai Project</h6>
                                                <h4 class="mb-0">Rp <?php echo number_format($project_stats['total_nilai'], 0, ',', '.'); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-success me-3">
                                                <i class="fas fa-wallet"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Total Saldo Project</h6>
                                                <h4 class="mb-0">Rp <?php echo number_format($project_stats['total_saldo'], 0, ',', '.'); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-primary me-3">
                                                <i class="fas fa-tools"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Klien Perawatan</h6>
                                                <h3 class="mb-0 text-primary"><?php echo number_format($total_klien_perawatan); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Project Status Chart for Admin -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Status Project</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2 mb-3">
                                                <div class="card bg-primary text-dark">
                                                    <div class="card-body text-center">
                                                        <h4><?php echo $project_stats['kontrak']; ?></h4>
                                                        <small>Kontrak</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <div class="card bg-info text-dark">
                                                    <div class="card-body text-center">
                                                        <h4><?php echo $project_stats['develop']; ?></h4>
                                                        <small>Develop</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <div class="card bg-warning text-dark">
                                                    <div class="card-body text-center">
                                                        <h4><?php echo $project_stats['implementasi']; ?></h4>
                                                        <small>Implementasi</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <div class="card bg-success text-dark">
                                                    <div class="card-body text-center">
                                                        <h4><?php echo $project_stats['garansi']; ?></h4>
                                                        <small>Garansi</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <div class="card bg-secondary text-dark">
                                                    <div class="card-body text-center">
                                                        <h4><?php echo $project_stats['selesai']; ?></h4>
                                                        <small>Selesai</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <div class="card bg-dark text-warning">
                                                    <div class="card-body text-center">
                                                        <h4><?php echo $total_klien_perawatan; ?></h4>
                                                        <small>Perawatan</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($user_role === 'user'): ?>
                        <!-- USER DASHBOARD -->
                        <?php
                        // Get statistics for user based on job
                        if ($user_job === 'support') {
                            // Support user statistics
                            $komplain = new Komplain($db);
                            $solving = new Solving($db);
                            
                            // Get komplain statistics
                            $total_komplain = $komplain->getTotalKomplainByUser($_SESSION['user_id'], 'support');
                            $komplain_data = $komplain->getKomplainByUser($_SESSION['user_id'], 'support', 1, 5);
                            
                            // Get solving statistics
                            $total_solving = $solving->getTotalSolvingBySupportUser($_SESSION['user_id']);
                            $solving_data = $solving->getSolvingBySupportUser($_SESSION['user_id'], 1, 5);
                            
                            // Get posting solving count
                            $posting_count = $solving->getPostingSolvingCountBySupportUser($_SESSION['user_id']);
                        } else {
                            // Regular user statistics
                            $komplain = new Komplain($db);
                            $total_komplain = $komplain->getTotalKomplainByUser($_SESSION['user_id'], 'user');
                            $komplain_data = $komplain->getKomplainByUser($_SESSION['user_id'], 'user', 1, 5);
                        }
                        ?>
                        
                        <!-- User Statistics Cards -->
                        <div class="row mb-0">
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-primary me-3">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Total Komplain</h6>
                                                <h3 class="mb-0"><?php echo number_format($total_komplain); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($user_job === 'support'): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-warning me-3">
                                                <i class="fas fa-tools"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Solving Menunggu</h6>
                                                <h3 class="mb-0 text-warning"><?php echo number_format($posting_count); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-success me-3">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Total Solving</h6>
                                                <h3 class="mb-0 text-success"><?php echo number_format($total_solving); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Recent Komplain for User -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-secondary">
                                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Komplain Terbaru</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($komplain_data)): ?>
                                            <p class="text-muted">Tidak ada komplain</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <tbody>
                                                        <?php foreach ($komplain_data as $k): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($k['subyek']); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $k['status'] === 'komplain' ? 'danger' : ($k['status'] === 'proses' ? 'warning' : 'success'); ?>">
                                                                    <?php echo ucfirst($k['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($k['created_at'])); ?></td>
                                                            <td>
                                                                <a href="komplain.php" class="btn btn-sm btn-outline-primary">Lihat</a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($user_role === 'client'): ?>
                        <!-- CLIENT DASHBOARD -->
                        <?php
                        // Get statistics for client
                        $komplain = new Komplain($db);
                        $total_komplain = $komplain->getTotalKomplainByUser($_SESSION['user_id'], 'client');
                        $komplain_data = $komplain->getKomplainByUser($_SESSION['user_id'], 'client', 1, 5);
                        ?>
                        
                        <!-- Client Statistics Cards -->
                        <div class="row mb-0">
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-primary me-3">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Total Komplain Saya</h6>
                                                <h3 class="mb-0"><?php echo number_format($total_komplain); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="stat-icon bg-info me-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <h6 class="card-title mb-0">Status Akun</h6>
                                                <h3 class="mb-0 text-info">Client</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Komplain for Client -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-secondary">
                                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Komplain Saya</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($komplain_data)): ?>
                                            <p class="text-muted">Belum ada komplain</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <tbody>
                                                        <?php foreach ($komplain_data as $k): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($k['subyek']); ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $k['status'] === 'komplain' ? 'danger' : ($k['status'] === 'proses' ? 'warning' : 'success'); ?>">
                                                                    <?php echo ucfirst($k['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($k['created_at'])); ?></td>
                                                            <td>
                                                                <a href="komplain.php" class="btn btn-sm btn-outline-primary">Lihat</a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
// End layout
endLayout();
?>
