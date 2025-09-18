<?php
// Include required files
require_once 'config/database.php';
require_once 'models/User.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get current user info for photo
$current_user = new User($db);
$current_user->getUserById($_SESSION['user_id']);

// Rich Text Editor configuration loaded via CDN
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Logics Software'; ?></title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <!-- Quill.js Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        // Check if Quill is loaded
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Quill === 'undefined') {
                console.error('Quill.js failed to load');
            } else {
            }
        });
    </script>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="container-fluid">
            <div class="header-content">
                <div class="logo-section">
                    <img src="assets/images/logics-logo.svg" alt="Logics">
                    <h4>Logics Software</h4>
                </div>
                <div class="user-section">
                    <div class="user-info">
                        <div class="avatar-container position-relative">
                            <?php if(!empty($current_user->foto_profile)): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($current_user->foto_profile ?? ''); ?>" 
                                     alt="Foto Profile" class="user-avatar">
                            <?php else: ?>
                                <div class="user-avatar bg-white d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                            <?php endif; ?>
                            
                            <?php 
                            // Show notification badge for developer users
                            if ($current_user->developer == 1) {
                                require_once 'config/database.php';
                                $database = new Database();
                                $db = $database->getConnection();
                                
                                // Get komplain count
                                $stmt = $db->prepare("SELECT COUNT(*) as total FROM komplain WHERE status = 'komplain'");
                                $stmt->execute();
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $komplain_count = $result['total'];
                                
                                // Get komplain data for popup (5 terakhir)
                                $stmt = $db->prepare("
                                    SELECT k.id, k.subyek, k.created_at, 
                                           u.nama as support_name, 
                                           kl.namaklien as client_name
                                    FROM komplain k 
                                    LEFT JOIN users u ON k.idsupport = u.id 
                                    LEFT JOIN klien kl ON k.idklien = kl.id 
                                    WHERE k.status = 'komplain' 
                                    ORDER BY k.created_at DESC 
                                    LIMIT 5
                                ");
                                $stmt->execute();
                                $komplain_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if ($komplain_count > 0): 
                                    // Build popup content
                                    $popup_content = '<div class="komplain-popup">';
                                    $popup_content .= '<div class="popup-header">';
                                    $popup_content .= '</div>';
                                    $popup_content .= '<div class="popup-body">';
                                    
                                    foreach($komplain_data as $komplain) {
                                        $popup_content .= '<div class="komplain-item mb-2" onclick="window.location.href=\'komplain.php\'">';
                                        $popup_content .= '<div class="komplain-subject fw-bold">' . htmlspecialchars($komplain['subyek']) . '</div>';
                                        $popup_content .= '<div class="komplain-meta text-muted small">';
                                        $popup_content .= '<i class="fas fa-user"></i> ' . htmlspecialchars($komplain['client_name'] ?? 'N/A');
                                        $popup_content .= '<span class="ms-2"><i class="fas fa-clock"></i> ' . date('d/m/Y H:i', strtotime($komplain['created_at'])) . '</span>';
                                        $popup_content .= '</div>';
                                        $popup_content .= '</div>';
                                    }
                                    
                                    if($komplain_count > 5) {
                                        $popup_content .= '<div class="text-center mt-2">';
                                        $popup_content .= '<small class="text-muted">dan ' . ($komplain_count - 5) . ' komplain lainnya...</small>';
                                        $popup_content .= '</div>';
                                    }
                                    
                                    $popup_content .= '</div>';
                                    $popup_content .= '<div class="popup-footer">';
                                    $popup_content .= '<a href="komplain.php" class="btn btn-sm btn-primary w-100">Lihat Semua Komplain</a>';
                                    $popup_content .= '</div>';
                                    $popup_content .= '</div>';
                                ?>
                                    <a href="komplain.php" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger komplain-badge text-decoration-none" style="margin-left: -10px !important; margin-top: 5px !important;"
                                       id="komplainBadge" 
                                       data-bs-toggle="popover" 
                                       data-bs-placement="bottom" 
                                       data-bs-html="true"
                                       data-bs-content="<?php echo htmlspecialchars($popup_content, ENT_QUOTES); ?>"
                                       title="<?php echo $komplain_count; ?> komplain menunggu">
                                        <?php echo $komplain_count > 99 ? '99+' : $komplain_count; ?>
                                    </a>
                                <?php endif;
                            }
                            
                            // Show notification badge for support users
                            if (($current_user->role === 'support' || $current_user->support == 1) && $current_user->developer != 1) {
                                require_once 'models/Solving.php';
                                $solving = new Solving($db);
                                $posting_count = $solving->getPostingSolvingCountBySupportUser($_SESSION['user_id']);
                                
                                if ($posting_count > 0): 
                                    // Get solving data for popup
                                    $stmt = $db->prepare("
                                        SELECT s.*, k.subyek as komplain_subyek, k.status as komplain_status, 
                                               u.nama as support_name, kl.namaklien as client_name
                                        FROM solving s 
                                        LEFT JOIN komplain k ON s.idkomplain = k.id 
                                        LEFT JOIN users u ON s.idsupport = u.id 
                                        LEFT JOIN klien kl ON k.idklien = kl.id
                                        WHERE s.idsupport = :user_id AND s.status = 'posting'
                                        ORDER BY s.created_at DESC 
                                        LIMIT 5
                                    ");
                                    $stmt->bindValue(':user_id', $_SESSION['user_id']);
                                    $stmt->execute();
                                    $solving_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // Build popup content
                                    $solving_popup_content = '<div class="solving-popup">';
                                    $solving_popup_content .= '<div class="popup-header">';
                                    $solving_popup_content .= '<h6><i class="fas fa-tools text-warning"></i> Solving Menunggu DiUpdate</h6>';
                                    $solving_popup_content .= '</div>';
                                    $solving_popup_content .= '<div class="popup-body">';
                                    
                                    foreach($solving_data as $solving_item) {
                                        $solving_popup_content .= '<div class="solving-item mb-2" onclick="window.location.href=\'solving.php\'">';
                                        $solving_popup_content .= '<div class="solving-subject fw-bold">' . htmlspecialchars($solving_item['subyek']) . '</div>';
                                        $solving_popup_content .= '<div class="solving-meta text-muted small">';
                                        $solving_popup_content .= '<i class="fas fa-user"></i> ' . htmlspecialchars($solving_item['client_name'] ?? 'N/A');
                                        $solving_popup_content .= '<span class="ms-2"><i class="fas fa-clock"></i> ' . date('d/m/Y H:i', strtotime($solving_item['created_at'])) . '</span>';
                                        $solving_popup_content .= '</div>';
                                        $solving_popup_content .= '</div>';
                                    }
                                    
                                    if($posting_count > 5) {
                                        $solving_popup_content .= '<div class="text-center mt-2">';
                                        $solving_popup_content .= '<small class="text-muted">dan ' . ($posting_count - 5) . ' solving lainnya...</small>';
                                        $solving_popup_content .= '</div>';
                                    }
                                    
                                    $solving_popup_content .= '</div>';
                                    $solving_popup_content .= '<div class="popup-footer">';
                                    $solving_popup_content .= '<a href="solving.php" class="btn btn-sm btn-warning w-100">Lihat Semua Solving</a>';
                                    $solving_popup_content .= '</div>';
                                    $solving_popup_content .= '</div>';
                                ?>
                                    <a href="solving.php" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark solving-badge text-decoration-none" style="margin-left: -10px !important; margin-top: 5px !important;"
                                       id="solvingBadge" 
                                       data-bs-toggle="popover" 
                                       data-bs-placement="bottom" 
                                       data-bs-html="true"
                                       data-bs-content="<?php echo htmlspecialchars($solving_popup_content, ENT_QUOTES); ?>"
                                       title="<?php echo $posting_count; ?> Solving menunggu diupdate">
                                        <?php echo $posting_count > 99 ? '99+' : $posting_count; ?>
                                    </a>
                                <?php endif;
                            }
                            ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($current_user->nama ?? 'User'); ?></span>
                    </div>
                    <div class="dropdown">
                        <a class="btn btn-outline-light btn-sm dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-edit me-2"></i>Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <nav class="main-sidebar">
        <div class="sidebar-content">
            <ul class="sidebar-nav">
                <li>
                    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <?php if($_SESSION['role'] !== 'user' && $_SESSION['role'] !== 'client'): ?>
                <li>
                    <a href="klien.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'klien.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        Data Klien
                    </a>
                </li>
                <?php endif; ?>
                <?php if($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'client'): ?>
                <li>
                    <a href="daftar_klien.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'daftar_klien.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        Daftar Klien
                    </a>
                </li>
                <?php endif; ?>
                <?php if($_SESSION['role'] !== 'user' && $_SESSION['role'] !== 'client'): ?>
                <li>
                    <a href="project.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'project.php' ? 'active' : ''; ?>">
                        <i class="fas fa-project-diagram"></i>
                        Data Project
                    </a>
                </li>
                <?php endif; ?>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'support' || $_SESSION['support'] == 1 || $_SESSION['role'] == 'client'): ?>
                <li>
                    <a href="komplain.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'komplain.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-headphones"></i>
                        Komplain
                    </a>
                </li>
                <?php endif; ?>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'support' || $_SESSION['support'] == 1): ?>
                <li>
                    <a href="solving.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'solving.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tools"></i>
                        Solving
                    </a>
                </li>
                <?php endif; ?>
                <?php if($_SESSION['role'] == 'admin'): ?>
                <li>
                    <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-cog"></i>
                        Manajemen User
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <?php if(isset($message) && !empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message ?? ''); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Page content will be inserted here -->
        <?php echo isset($content) ? $content : ''; ?>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav d-md-none">
        <div class="bottom-nav-container">
            <a href="dashboard.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <?php if($_SESSION['role'] !== 'user' && $_SESSION['role'] !== 'client'): ?>
            <a href="klien.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'klien.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Klien</span>
            </a>
            <?php if($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'client'): ?>
            <a href="daftar_klien.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'daftar_klien.php' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                <span>Daftar</span>
            </a>
            <?php endif; ?>
            <a href="project.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'project.php' ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram"></i>
                <span>Project</span>
            </a>
            <?php endif; ?>
            <?php if($_SESSION['role'] == 'user' && $_SESSION['support'] == 1): ?>
            <a href="daftar_klien.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'daftar_klien.php' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                <span>Daftar</span>
            </a>
            <?php endif; ?>
            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'support' || $_SESSION['support'] == 1 || $_SESSION['role'] == 'client'): ?>
            <a href="komplain.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'komplain.php' ? 'active' : ''; ?>">
                <i class="fas fa-headphones"></i>
                <span>Komplain</span>
            </a>
            <?php endif; ?>
            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'support' || $_SESSION['support'] == 1): ?>
            <a href="solving.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'solving.php' ? 'active' : ''; ?>">
                <i class="fas fa-tools"></i>
                <span>Solving</span>
            </a>
            <?php endif; ?>
            <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="users.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i>
                <span>Users</span>
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Auto-hide Success Messages Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide success messages after 5 seconds
            const successAlerts = document.querySelectorAll('.alert-success');
            successAlerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000); // 5 seconds
            });
        });
    </script>
    
    <!-- Komplain Badge Popover Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize popover for komplain badge
            const komplainBadge = document.getElementById('komplainBadge');
            if (komplainBadge) {
                const popover = new bootstrap.Popover(komplainBadge, {
                    trigger: 'manual',
                    placement: 'bottom',
                    html: true,
                    container: 'body'
                });
                
                let hoverTimeout;
                
                // Show popover on hover
                komplainBadge.addEventListener('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                    popover.show();
                });
                
                // Hide popover with delay when leaving badge
                komplainBadge.addEventListener('mouseleave', function() {
                    hoverTimeout = setTimeout(function() {
                        popover.hide();
                    }, 200);
                });
                
                // Keep popover open when hovering over it
                document.addEventListener('mouseover', function(e) {
                    if (e.target && e.target.closest && e.target.closest('.popover')) {
                        clearTimeout(hoverTimeout);
                    }
                });
                
                // Hide popover when leaving popover area
                document.addEventListener('mouseleave', function(e) {
                    if (e.target && e.target.closest && e.target.closest('.popover')) {
                        hoverTimeout = setTimeout(function() {
                            popover.hide();
                        }, 50);
                    }
                });
                
                // Use MutationObserver to detect when popover is added to DOM
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && node.classList && node.classList.contains('popover')) {
                                // Add event listeners to the popover element
                                node.addEventListener('mouseleave', function() {
                                    hoverTimeout = setTimeout(function() {
                                        popover.hide();
                                    }, 50);
                                });
                                
                                node.addEventListener('mouseenter', function() {
                                    clearTimeout(hoverTimeout);
                                });
                            }
                        });
                    });
                });
                
                // Start observing
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
                
                // Handle click on komplain items
                document.addEventListener('click', function(e) {
                    if (e.target && e.target.closest && e.target.closest('.komplain-item')) {
                        window.location.href = 'komplain.php';
                    }
                });
            }
            
            // Initialize popover for solving badge
            const solvingBadge = document.getElementById('solvingBadge');
            if (solvingBadge) {
                const solvingPopover = new bootstrap.Popover(solvingBadge, {
                    trigger: 'manual',
                    placement: 'bottom',
                    html: true,
                    container: 'body'
                });
                
                let solvingHoverTimeout;
                
                // Show popover on hover
                solvingBadge.addEventListener('mouseenter', function() {
                    clearTimeout(solvingHoverTimeout);
                    solvingPopover.show();
                });
                
                // Hide popover with delay when leaving badge
                solvingBadge.addEventListener('mouseleave', function() {
                    solvingHoverTimeout = setTimeout(function() {
                        solvingPopover.hide();
                    }, 200);
                });
                
                // Keep popover open when hovering over it
                document.addEventListener('mouseover', function(e) {
                    if (e.target && e.target.closest && e.target.closest('.popover')) {
                        clearTimeout(solvingHoverTimeout);
                    }
                });
                
                // Hide popover when leaving popover area
                document.addEventListener('mouseleave', function(e) {
                    if (e.target && e.target.closest && e.target.closest('.popover')) {
                        solvingHoverTimeout = setTimeout(function() {
                            solvingPopover.hide();
                        }, 50);
                    }
                });
                
                // Use MutationObserver to detect when popover is added to DOM
                const solvingObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && node.classList && node.classList.contains('popover')) {
                                // Add event listeners to the popover element
                                node.addEventListener('mouseleave', function() {
                                    solvingHoverTimeout = setTimeout(function() {
                                        solvingPopover.hide();
                                    }, 50);
                                });
                                
                                node.addEventListener('mouseenter', function() {
                                    clearTimeout(solvingHoverTimeout);
                                });
                            }
                        });
                    });
                });
                
                // Start observing
                solvingObserver.observe(document.body, {
                    childList: true,
                    subtree: true
                });
                
                // Handle click on solving items
                document.addEventListener('click', function(e) {
                    if (e.target && e.target.closest && e.target.closest('.solving-item')) {
                        window.location.href = 'solving.php';
                    }
                });
            }
        });
    </script>
    
    <!-- Mobile Bottom Navigation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add touch solved for mobile bottom navigation
            const bottomNavItems = document.querySelectorAll('.bottom-nav-item');
            
            bottomNavItems.forEach(item => {
                item.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.95)';
                });
                
                item.addEventListener('touchend', function() {
                    this.style.transform = '';
                });
                
                item.addEventListener('touchcancel', function() {
                    this.style.transform = '';
                });
            });
            
            // Smooth scroll to top when navigating
            bottomNavItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Only scroll to top if not already at top
                    if (window.scrollY > 100) {
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Add active state management
            const currentPage = window.location.pathname.split('/').pop();
            bottomNavItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && href.includes(currentPage)) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
