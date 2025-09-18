<?php
require_once 'config/database.php';

class Solving {
    private $conn;
    private $table_name = "solving";

    public $id;
    public $subyek;
    public $solving;
    public $idkomplain;
    public $idsupport;
    public $image;
    public $uploadfiles;
    public $iddeveloper;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all solving with pagination
    public function getSolvingWithPagination($page = 1, $limit = 10, $search = '', $sort_by = 'created_at', $sort_order = 'DESC') {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT s.*, 
                         k.subyek as komplain_subyek,
                         k.status as komplain_status,
                         k.created_at as komplain_created_at,
                         k.kompain as komplain_text,
                         u.nama as support_name,
                         kl.namaklien as client_name,
                         ud.nama as developer_name
                  FROM " . $this->table_name . " s 
                  LEFT JOIN komplain k ON s.idkomplain = k.id 
                  LEFT JOIN users u ON s.idsupport = u.id 
                  LEFT JOIN klien kl ON k.idklien = kl.id
                  LEFT JOIN users ud ON s.iddeveloper = ud.id";
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(s.subyek LIKE :search OR s.solving LIKE :search OR k.subyek LIKE :search OR u.nama LIKE :search OR kl.namaklien LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        // Validate sort parameters
        $allowed_sort_fields = ['subyek', 'created_at', 'updated_at', 'support_name', 'client_name', 'komplain_subyek'];
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'created_at';
        }
        
        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'DESC';
        }
        
        $query .= " ORDER BY s." . $sort_by . " " . $sort_order;
        $query .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Get total solving count
    public function getTotalSolving($search = '') {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " s 
                  LEFT JOIN komplain k ON s.idkomplain = k.id 
                  LEFT JOIN users u ON s.idsupport = u.id 
                  LEFT JOIN klien kl ON k.idklien = kl.id";
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(s.subyek LIKE :search OR s.solving LIKE :search OR k.subyek LIKE :search OR u.nama LIKE :search OR kl.namaklien LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Get solving by ID
    public function getSolvingById($id) {
        $query = "SELECT s.*, 
                         k.subyek as komplain_subyek,
                         k.status as komplain_status,
                         u.nama as support_name,
                         kl.namaklien as client_name
                  FROM " . $this->table_name . " s 
                  LEFT JOIN komplain k ON s.idkomplain = k.id 
                  LEFT JOIN users u ON s.idsupport = u.id 
                  LEFT JOIN klien kl ON k.idklien = kl.id
                  WHERE s.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->subyek = $row['subyek'];
            $this->solving = $row['solving'];
            $this->idkomplain = $row['idkomplain'];
            $this->idsupport = $row['idsupport'];
            $this->image = $row['image'];
            $this->uploadfiles = $row['uploadfiles'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Create solving
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET subyek=:subyek, solving=:solving, idkomplain=:idkomplain, 
                      idsupport=:idsupport, image=:image, uploadfiles=:uploadfiles,
                      iddeveloper=:iddeveloper";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':subyek', $this->subyek);
        $stmt->bindValue(':solving', $this->solving);
        $stmt->bindValue(':idkomplain', $this->idkomplain);
        $stmt->bindValue(':idsupport', $this->idsupport);
        $stmt->bindValue(':image', $this->image);
        $stmt->bindValue(':uploadfiles', $this->uploadfiles);
        $stmt->bindValue(':iddeveloper', $this->iddeveloper);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update solving
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET subyek=:subyek, solving=:solving, idkomplain=:idkomplain, 
                      idsupport=:idsupport, image=:image, uploadfiles=:uploadfiles,
                      iddeveloper=:iddeveloper
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindValue(':subyek', $this->subyek);
        $stmt->bindValue(':solving', $this->solving);
        $stmt->bindValue(':idkomplain', $this->idkomplain);
        $stmt->bindValue(':idsupport', $this->idsupport);
        $stmt->bindValue(':image', $this->image);
        $stmt->bindValue(':uploadfiles', $this->uploadfiles);
        $stmt->bindValue(':iddeveloper', $this->iddeveloper);
        $stmt->bindValue(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete solving
    public function delete() {
        // Delete associated files
        $this->deleteFiles();
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $this->id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete associated files
    private function deleteFiles() {
        // Delete images
        if (!empty($this->image)) {
            $images = json_decode($this->image, true);
            if (is_array($images)) {
                foreach ($images as $image) {
                    if (file_exists('uploads/solving/images/' . $image)) {
                        unlink('uploads/solving/images/' . $image);
                    }
                }
            }
        }
        
        // Delete files
        if (!empty($this->uploadfiles)) {
            $files = json_decode($this->uploadfiles, true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (file_exists('uploads/solving/files/' . $file)) {
                        unlink('uploads/solving/files/' . $file);
                    }
                }
            }
        }
    }

    // Get komplain with status 'proses' for dropdown
    public function getKomplainProses() {
        $query = "SELECT k.id, k.subyek, k.idsupport, kl.namaklien as client_name
                  FROM komplain k 
                  LEFT JOIN klien kl ON k.idklien = kl.id 
                  WHERE k.status = 'proses'
                  ORDER BY k.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get support users for dropdown
    public function getSupportUsers() {
        $query = "SELECT id, nama FROM users 
                  WHERE role = 'support' OR support = 1 
                  ORDER BY nama ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
