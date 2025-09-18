<?php
class KomplainProcess {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Proses komplain (ubah status dari 'komplain' ke 'proses')
    public function processKomplain($komplain_id, $user_id, $notes = '') {
        try {
            $this->db->beginTransaction();
            
            // Update status komplain
            $stmt = $this->db->prepare("
                UPDATE komplain 
                SET status = 'proses', updated_at = NOW() 
                WHERE id = ? AND status = 'komplain'
            ");
            $stmt->execute([$komplain_id]);
            
            if ($stmt->rowCount() == 0) {
                throw new Exception("Komplain tidak ditemukan atau sudah diproses");
            }
            
            // Simpan tracking proses
            $stmt = $this->db->prepare("
                INSERT INTO komplain_process (komplain_id, user_id, status_from, status_to, process_date, notes) 
                VALUES (?, ?, 'komplain', 'proses', NOW(), ?)
            ");
            $stmt->execute([$komplain_id, $user_id, $notes]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    // Get tracking history untuk komplain
    public function getProcessHistory($komplain_id) {
        $stmt = $this->db->prepare("
            SELECT kp.*, u.nama as user_name
            FROM komplain_process kp
            LEFT JOIN users u ON kp.user_id = u.id
            WHERE kp.komplain_id = ?
            ORDER BY kp.process_date DESC
        ");
        $stmt->execute([$komplain_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get komplain yang bisa diproses (status 'komplain')
    public function getKomplainToProcess() {
        $stmt = $this->db->prepare("
            SELECT k.*, kl.namaklien as client_name, u.nama as support_name
            FROM komplain k
            LEFT JOIN klien kl ON k.idklien = kl.id
            LEFT JOIN users u ON k.idsupport = u.id
            WHERE k.status = 'komplain'
            ORDER BY k.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Check apakah user bisa memproses komplain
    public function canProcessKomplain($user_id) {
        $stmt = $this->db->prepare("
            SELECT developer FROM users WHERE id = ? AND developer = 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
}
?>
