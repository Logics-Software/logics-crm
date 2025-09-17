<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'includes/auth.php';
require_once 'includes/layout_helper.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$db = $db->getConnection();

$user = new User($db);
$user->getUserById($_SESSION['user_id']);

$message = '';
$message_type = '';

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_profile') {
            // Update profile
            $user->nama = $_POST['nama'];
            $user->alamat = $_POST['alamat'];
            $user->email = $_POST['email'];
            
            // Handle photo upload
            if(isset($_FILES['foto_profile']) && $_FILES['foto_profile']['error'] == 0) {
                $upload_result = uploadPhoto($_FILES['foto_profile']);
                if($upload_result['success']) {
                    // Delete old photo if exists
                    if(!empty($user->foto_profile) && file_exists('uploads/profiles/' . $user->foto_profile)) {
                        unlink('uploads/profiles/' . $user->foto_profile);
                    }
                    $user->foto_profile = $upload_result['filename'];
                } else {
                    $message = $upload_result['message'];
                    $message_type = 'danger';
                }
            }
            
            if($user->updateProfile()) {
                $message = 'Profile berhasil diperbarui!';
                $message_type = 'success';
            } else {
                $message = 'Gagal memperbarui profile!';
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'change_password') {
            // Change password
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Get current password hash from database for validation
            $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();
            $current_user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify current password
            if (!password_verify($current_password, $current_user_data['password'])) {
                $message = 'Password lama tidak benar!';
                $message_type = 'danger';
            } elseif ($new_password !== $confirm_password) {
                $message = 'Password baru dan konfirmasi password tidak sama!';
                $message_type = 'danger';
            } elseif (strlen($new_password) < 6) {
                $message = 'Password baru minimal 6 karakter!';
                $message_type = 'danger';
            } else {
                // Hash new password before updating
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                if($user->updatePassword($hashed_new_password)) {
                    $message = 'Password berhasil diubah!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal mengubah password!';
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Start layout with buffer
startLayoutBuffer('Profile - Logics Software');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Profile</h1>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <!-- Profile Tabs -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                                <i class="fas fa-user-edit me-2"></i>Edit Profile
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                                <i class="fas fa-key me-2"></i>Ubah Password
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Edit Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($user->nama); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3"><?php echo htmlspecialchars($user->alamat); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="foto_profile" class="form-label">Foto Profile</label>
                                    <input type="file" class="form-control" id="foto_profile" name="foto_profile" accept="image/*">
                                    <div class="form-text">Format: JPG, PNG, GIF, WebP. Maksimal 2MB.</div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                            </form>
                        </div>
                        
                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Password Lama <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Password Baru <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    <div class="form-text">Minimal 6 karakter.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Ubah Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Profile Info Card -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if(!empty($user->foto_profile)): ?>
                            <img src="uploads/profiles/<?php echo htmlspecialchars($user->foto_profile); ?>" 
                                 alt="Foto Profile" class="rounded-circle img-cover avatar-large">
                        <?php else: ?>
                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center avatar-large">
                                <i class="fas fa-user text-white icon-large"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h5 class="card-title"><?php echo htmlspecialchars($user->nama); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user->email); ?></p>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h6 class="mb-0"><?php echo ucfirst($user->role); ?></h6>
                                <small class="text-muted">Role</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="mb-0"><?php echo ucfirst($user->status); ?></h6>
                            <small class="text-muted">Status</small>
                        </div>
                    </div>
                    
                    <?php if(!empty($user->alamat)): ?>
                        <hr>
                        <div class="text-start">
                            <h6>Alamat:</h6>
                            <p class="text-muted small"><?php echo nl2br(htmlspecialchars($user->alamat)); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Password tidak sama');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
});
</script>

<?php
// End layout
endLayout();
?>
