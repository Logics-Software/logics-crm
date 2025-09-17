<?php
require_once 'config/database.php';

class Klien {
    private $conn;
    private $table_name = "klien";

    public $id;
    public $namaklien;
    public $alamatklien;
    public $kotaklien;
    public $sistem;
    public $tanggaltagihan;
    public $keterangantagihan;
    public $jumlahtagihan;
    public $pekerjaan;
    public $iduser;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all klien
    public function getAllKlien() {
        $query = "SELECT k.*, u.nama as nama_user 
                  FROM " . $this->table_name . " k 
                  LEFT JOIN users u ON k.iduser = u.id 
                  ORDER BY k.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Get klien by ID
    public function getKlienById($id) {
        $query = "SELECT k.*, u.nama as nama_user 
                  FROM " . $this->table_name . " k 
                  LEFT JOIN users u ON k.iduser = u.id 
                  WHERE k.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->namaklien = $row['namaklien'];
            $this->alamatklien = $row['alamatklien'];
            $this->kotaklien = $row['kotaklien'];
            $this->sistem = $row['sistem'];
            $this->tanggaltagihan = $row['tanggaltagihan'];
            $this->keterangantagihan = $row['keterangantagihan'];
            $this->jumlahtagihan = $row['jumlahtagihan'];
            $this->pekerjaan = $row['pekerjaan'];
            $this->iduser = $row['iduser'];
            $this->status = $row['status'];
            return true;
        }
        return false;
    }

    // Create klien
    public function create() {
        // Auto-set status to 'nonaktif' if pekerjaan is 'selesai'
        if($this->pekerjaan == 'selesai') {
            $this->status = 'nonaktif';
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (namaklien, alamatklien, kotaklien, sistem, tanggaltagihan, 
                   keterangantagihan, jumlahtagihan, pekerjaan, iduser, status) 
                  VALUES (:namaklien, :alamatklien, :kotaklien, :sistem, :tanggaltagihan, 
                          :keterangantagihan, :jumlahtagihan, :pekerjaan, :iduser, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':namaklien', $this->namaklien);
        $stmt->bindParam(':alamatklien', $this->alamatklien);
        $stmt->bindParam(':kotaklien', $this->kotaklien);
        $stmt->bindParam(':sistem', $this->sistem);
        $stmt->bindParam(':tanggaltagihan', $this->tanggaltagihan);
        $stmt->bindParam(':keterangantagihan', $this->keterangantagihan);
        $stmt->bindParam(':jumlahtagihan', $this->jumlahtagihan);
        $stmt->bindParam(':pekerjaan', $this->pekerjaan);
        $stmt->bindParam(':iduser', $this->iduser);
        $stmt->bindParam(':status', $this->status);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update klien
    public function update() {
        // Auto-set status to 'nonaktif' if pekerjaan is 'selesai'
        if($this->pekerjaan == 'selesai') {
            $this->status = 'nonaktif';
        }
        
        $query = "UPDATE " . $this->table_name . " 
                  SET namaklien = :namaklien, alamatklien = :alamatklien, 
                      kotaklien = :kotaklien, sistem = :sistem, 
                      tanggaltagihan = :tanggaltagihan, 
                      keterangantagihan = :keterangantagihan, 
                      jumlahtagihan = :jumlahtagihan, pekerjaan = :pekerjaan, 
                      iduser = :iduser, status = :status 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':namaklien', $this->namaklien);
        $stmt->bindParam(':alamatklien', $this->alamatklien);
        $stmt->bindParam(':kotaklien', $this->kotaklien);
        $stmt->bindParam(':sistem', $this->sistem);
        $stmt->bindParam(':tanggaltagihan', $this->tanggaltagihan);
        $stmt->bindParam(':keterangantagihan', $this->keterangantagihan);
        $stmt->bindParam(':jumlahtagihan', $this->jumlahtagihan);
        $stmt->bindParam(':pekerjaan', $this->pekerjaan);
        $stmt->bindParam(':iduser', $this->iduser);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete klien
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get users for dropdown
    public function getUsers() {
        $query = "SELECT id, nama FROM users WHERE status = 'aktif' ORDER BY nama";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Get klien with pagination, search, and sorting
    public function getKlienWithPagination($page = 1, $limit = 10, $search = '', $status_filter = '', $pekerjaan_filter = '', $sort_by = 'namaklien', $sort_order = 'ASC') {
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause for search and status filter
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(k.namaklien LIKE :search OR k.sistem LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "k.status = :status";
            $params[':status'] = $status_filter;
        }
        
        if (!empty($pekerjaan_filter)) {
            $where_conditions[] = "k.pekerjaan = :pekerjaan";
            $params[':pekerjaan'] = $pekerjaan_filter;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = "WHERE " . implode(' AND ', $where_conditions);
        }
        
        // Validate sort column
        $allowed_sort_columns = ['namaklien', 'alamatklien', 'tanggaltagihan', 'jumlahtagihan', 'status', 'created_at'];
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'namaklien';
        }
        
        // Validate sort order
        $sort_order = strtoupper($sort_order);
        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'ASC';
        }
        
        // Build query
        $query = "SELECT k.*, u.nama as nama_user 
                  FROM " . $this->table_name . " k 
                  LEFT JOIN users u ON k.iduser = u.id 
                  " . $where_clause . "
                  ORDER BY k." . $sort_by . " " . $sort_order . "
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        if (!empty($search)) {
            $stmt->bindParam(':search', $params[':search']);
        }
        if (!empty($status_filter)) {
            $stmt->bindParam(':status', $params[':status']);
        }
        if (!empty($pekerjaan_filter)) {
            $stmt->bindParam(':pekerjaan', $params[':pekerjaan']);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Get total count for pagination
    public function getTotalKlien($search = '', $status_filter = '', $pekerjaan_filter = '') {
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(namaklien LIKE :search OR sistem LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "status = :status";
            $params[':status'] = $status_filter;
        }
        
        if (!empty($pekerjaan_filter)) {
            $where_conditions[] = "pekerjaan = :pekerjaan";
            $params[':pekerjaan'] = $pekerjaan_filter;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = "WHERE " . implode(' AND ', $where_conditions);
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $where_clause;
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $stmt->bindParam(':search', $params[':search']);
        }
        if (!empty($status_filter)) {
            $stmt->bindParam(':status', $params[':status']);
        }
        if (!empty($pekerjaan_filter)) {
            $stmt->bindParam(':pekerjaan', $params[':pekerjaan']);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'];
    }
}
?>
