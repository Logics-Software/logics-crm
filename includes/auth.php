<?php
// Authentication helper functions

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is support (by role or job)
function isSupport() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'support') || 
           (isset($_SESSION['support']) && $_SESSION['support'] == 1);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is user
function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

// Check if user is client
function isClient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'client';
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Redirect to login if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Redirect to login if not user or admin
function requireUserOrAdmin() {
    requireLogin();
    if (!isUser() && !isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Redirect to login if not support
function requireSupport() {
    requireLogin();
    if (!isSupport()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Redirect to login if not client
function requireClient() {
    requireLogin();
    if (!isClient()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Redirect to login if not support or client
function requireSupportOrClient() {
    requireLogin();
    if (!isSupport() && !isClient()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Get current user ID
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Get current user role
function getCurrentUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Get current user support
function getCurrentUserSupport() {
    return isset($_SESSION['support']) ? $_SESSION['support'] : null;
}

// Get current user developer
function getCurrentUserDeveloper() {
    return isset($_SESSION['developer']) ? $_SESSION['developer'] : null;
}

// Get current user developer
function getCurrentUserTagihan() {
    return isset($_SESSION['tagihan']) ? $_SESSION['tagihan'] : null;
}

// Get current user name
function getCurrentUserName() {
    return isset($_SESSION['nama']) ? $_SESSION['nama'] : null;
}

// Get current user username
function getCurrentUserUsername() {
    return isset($_SESSION['username']) ? $_SESSION['username'] : null;
}

// Get current user email
function getCurrentUserEmail() {
    return isset($_SESSION['email']) ? $_SESSION['email'] : null;
}

// Check if user has permission for specific action
function hasPermission($action) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $role = getCurrentUserRole();
    
    switch ($action) {
        case 'manage_users':
            return $role === 'admin';
        case 'view_dashboard':
            return in_array($role, ['admin', 'user', 'client', 'support']);
        case 'manage_clients':
            return in_array($role, ['admin', 'user', 'support']);
        case 'manage_projects':
            return in_array($role, ['admin', 'user', 'support']);
        case 'manage_komplain':
            return in_array($role, ['admin', 'support', 'client']) || 
                   (isset($_SESSION['support']) && $_SESSION['support'] == 1);
        case 'edit_profile':
            return in_array($role, ['admin', 'user', 'client', 'support']);
        default:
            return false;
    }
}

// Logout user
function logout() {
    session_start();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate password strength
function validatePassword($password) {
    return strlen($password) >= 6;
}

// Generate random password
function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

// Format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Format date
function formatDate($date, $format = 'd F Y') {
    return date($format, strtotime($date));
}

// Format datetime
function formatDateTime($datetime, $format = 'd F Y H:i') {
    return date($format, strtotime($datetime));
}

// Get time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    if ($time < 31536000) return floor($time/2592000) . ' bulan yang lalu';
    return floor($time/31536000) . ' tahun yang lalu';
}

// Check if file is image
function isImage($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return in_array($file['type'], $allowed_types);
}

// Upload file
function uploadFile($file, $directory = 'uploads', $max_size = 2097152) { // 2MB default
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File terlalu besar. Maksimal ' . ($max_size / 1024 / 1024) . 'MB'];
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $filepath = $directory . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file'];
    }
}

// Delete file
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return true;
}

// Get file size in human readable format
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Generate pagination
function generatePagination($current_page, $total_pages, $base_url) {
    $pagination = '';
    
    if ($total_pages > 1) {
        $pagination .= '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
        
        // Previous button
        if ($current_page > 1) {
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page - 1) . '">Previous</a></li>';
        }
        
        // Page numbers
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $current_page) {
                $pagination .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a></li>';
            }
        }
        
        // Next button
        if ($current_page < $total_pages) {
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page + 1) . '">Next</a></li>';
        }
        
        $pagination .= '</ul></nav>';
    }
    
    return $pagination;
}

// Flash message
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Display flash message
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        echo '<div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show" role="alert">';
        echo $flash['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}
?>
