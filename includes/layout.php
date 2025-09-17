<?php
// Get database connection from global
$db = $GLOBALS['db'];

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
                        <?php if(!empty($current_user->foto_profile)): ?>
                            <img src="uploads/profiles/<?php echo htmlspecialchars($current_user->foto_profile ?? ''); ?>" 
                                 alt="Foto Profile" class="user-avatar">
                        <?php else: ?>
                            <div class="user-avatar bg-white d-flex align-items-center justify-content-center">
                                <i class="fas fa-user text-primary"></i>
                            </div>
                        <?php endif; ?>
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
                <li>
                    <a href="klien.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'klien.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        Data Klien
                    </a>
                </li>
                <li>
                    <a href="project.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'project.php' ? 'active' : ''; ?>">
                        <i class="fas fa-project-diagram"></i>
                        Data Project
                    </a>
                </li>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'support' || $_SESSION['support'] == 1 || $_SESSION['role'] == 'client'): ?>
                <li>
                    <a href="komplain.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'komplain.php' ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle"></i>
                        Komplain
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
        <?php echo $content; ?>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav d-md-none">
        <div class="bottom-nav-container">
            <a href="dashboard.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="klien.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'klien.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Klien</span>
            </a>
            <a href="project.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'project.php' ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram"></i>
                <span>Project</span>
            </a>
            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'support' || $_SESSION['support'] == 1 || $_SESSION['role'] == 'client'): ?>
            <a href="komplain.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'komplain.php' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Komplain</span>
            </a>
            <?php endif; ?>
            <?php if($_SESSION['role'] == 'admin'): ?>
            <a href="users.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i>
                <span>Users</span>
            </a>
            <?php endif; ?>
            <a href="profile.php" class="bottom-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Mobile Bottom Navigation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add touch feedback for mobile bottom navigation
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
