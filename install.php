<?php
// File untuk setup database (hapus setelah instalasi selesai)
require_once 'config/database.php';

$message = '';
$message_type = '';

if($_POST && isset($_POST['install'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Read and execute SQL file
        $sql = file_get_contents('database.sql');
        $statements = explode(';', $sql);
        
        foreach($statements as $statement) {
            $statement = trim($statement);
            if(!empty($statement)) {
                $db->exec($statement);
            }
        }
        
        $message = 'Database berhasil diinstall! Silakan hapus file install.php untuk keamanan.';
        $message_type = 'success';
        
    } catch(Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Database - Software Developer</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #667eea;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .install-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card install-card">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3><i class="fas fa-database me-2"></i>Install Database</h3>
                        <p class="mb-0">Setup database untuk Aplikasi Tagihan</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Instruksi:</h6>
                            <ol>
                                <li>Pastikan database MySQL sudah berjalan</li>
                                <li>Buat database dengan nama <code>logics</code></li>
                                <li>Konfigurasi koneksi database di <code>config/database.php</code></li>
                                <li>Klik tombol Install untuk membuat tabel dan data default</li>
                                <li><strong>Hapus file ini setelah instalasi selesai!</strong></li>
                            </ol>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>Catatan:</strong> Field "Tanggal Tagihan" sekarang menggunakan format hari (1-31) setiap bulan.
                                </small>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-key me-2"></i>Login Default:</h6>
                            <p class="mb-0">
                                <strong>Username:</strong> admin<br>
                                <strong>Password:</strong> password<br>
                                <strong>Role:</strong> direktur
                            </p>
                        </div>
                        
                        <form method="POST">
                            <div class="d-grid">
                                <button type="submit" name="install" class="btn btn-primary btn-lg">
                                    <i class="fas fa-download me-2"></i>Install Database
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-in-alt me-2"></i>Ke Halaman Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
