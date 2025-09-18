<?php
require_once 'config/database.php';

class Komplain {
    private $conn;
    private $table_name = "komplain";

    public $id;
    public $subyek;
    public $kompain;
    public $idsupport;
    public $idklien;
    public $image;
    public $uploadfile;
    public $status;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all komplain with pagination
    public function getKomplainWithPagination($page = 1, $limit = 10, $search = '', $status_filter = '', $sort_by = 'created_at', $sort_order = 'DESC') {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT k.*, 
                         u.nama as nama_support, 
                         kl.namaklien as nama_klien,
                         kp.notes as process_notes,
                         kp.process_date,
                         up.nama as process_user_name
                  FROM " . $this->table_name . " k 
                  LEFT JOIN users u ON k.idsupport = u.id 
                  LEFT JOIN klien kl ON k.idklien = kl.id
                  LEFT JOIN komplain_process kp ON k.id = kp.komplain_id AND kp.status_to = 'proses'
                  LEFT JOIN users up ON kp.user_id = up.id";
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(k.subyek LIKE :search OR k.kompain LIKE :search OR u.nama LIKE :search OR kl.namaklien LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "k.status = :status";
            $params[':status'] = $status_filter;
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        // Validate sort parameters
        $allowed_sort_fields = ['subyek', 'status', 'created_at', 'updated_at', 'nama_support', 'nama_klien'];
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'created_at';
        }
        
        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'DESC';
        }
        
        $query .= " ORDER BY k." . $sort_by . " " . $sort_order . " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Get total count for pagination
    public function getTotalKomplain($search = '', $status_filter = '') {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . " k 
                  LEFT JOIN users u ON k.idsupport = u.id 
                  LEFT JOIN klien kl ON k.idklien = kl.id";
        
        $where_conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $where_conditions[] = "(k.subyek LIKE :search OR k.kompain LIKE :search OR u.nama LIKE :search OR kl.namaklien LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if (!empty($status_filter)) {
            $where_conditions[] = "k.status = :status";
            $params[':status'] = $status_filter;
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    // Get komplain by ID
    public function getKomplainById($id) {
        $query = "SELECT k.*, 
                         u.nama as nama_support, 
                         kl.namaklien as nama_klien
                  FROM " . $this->table_name . " k 
                  LEFT JOIN users u ON k.idsupport = u.id 
                  LEFT JOIN klien kl ON k.idklien = kl.id
                  WHERE k.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->subyek = $row['subyek'];
            $this->kompain = $row['kompain'];
            $this->idsupport = $row['idsupport'];
            $this->idklien = $row['idklien'];
            $this->image = $row['image'];
            $this->uploadfile = $row['uploadfile'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Create komplain
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET subyek = :subyek, 
                      kompain = :kompain, 
                      idsupport = :idsupport, 
                      idklien = :idklien, 
                      image = :image, 
                      uploadfile = :uploadfile, 
                      status = :status";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':subyek', $this->subyek);
        $stmt->bindParam(':kompain', $this->kompain);
        $stmt->bindParam(':idsupport', $this->idsupport);
        $stmt->bindParam(':idklien', $this->idklien);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':uploadfile', $this->uploadfile);
        $stmt->bindParam(':status', $this->status);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Update komplain
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET subyek = :subyek, 
                      kompain = :kompain, 
                      idsupport = :idsupport, 
                      idklien = :idklien, 
                      image = :image, 
                      uploadfile = :uploadfile, 
                      status = :status 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':subyek', $this->subyek);
        $stmt->bindParam(':kompain', $this->kompain);
        $stmt->bindParam(':idsupport', $this->idsupport);
        $stmt->bindParam(':idklien', $this->idklien);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':uploadfile', $this->uploadfile);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete komplain
    public function delete() {
        // Delete associated files
        $this->deleteAssociatedFiles();
        
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete associated files
    private function deleteAssociatedFiles() {
        // Delete images
        if (!empty($this->image)) {
            $images = json_decode($this->image, true);
            if (is_array($images)) {
                foreach ($images as $image) {
                    if (file_exists('uploads/komplain/images/' . $image)) {
                        unlink('uploads/komplain/images/' . $image);
                    }
                }
            }
        }
        
        // Delete files
        if (!empty($this->uploadfile)) {
            $files = json_decode($this->uploadfile, true);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (file_exists('uploads/komplain/files/' . $file)) {
                        unlink('uploads/komplain/files/' . $file);
                    }
                }
            }
        }
    }

    // Get komplain by user role and job
    public function getKomplainByUser($user_id, $user_role, $page = 1, $limit = 10, $user_support = false, $user_idklien = null) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT k.*, 
                         u.nama as nama_support, 
                         kl.namaklien as nama_klien,
                         kp.notes as process_notes,
                         kp.process_date,
                         up.nama as process_user_name
                  FROM " . $this->table_name . " k 
                  LEFT JOIN users u ON k.idsupport = u.id 
                  LEFT JOIN klien kl ON k.idklien = kl.id
                  LEFT JOIN komplain_process kp ON k.id = kp.komplain_id AND kp.status_to = 'proses'
                  LEFT JOIN users up ON kp.user_id = up.id";
        
        if ($user_role === 'support' || $user_support) {
            $query .= " WHERE k.idsupport = :user_id";
        } elseif ($user_role === 'client') {
            $query .= " WHERE k.idsupport = :user_id";
        }
        
        $query .= " ORDER BY k.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        if ($user_role === 'support' || $user_support) {
            $stmt->bindParam(':user_id', $user_id);
        } elseif ($user_role === 'client') {
            $stmt->bindParam(':user_id', $user_id);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Get komplain by support user (based on iduser in klien table)
    public function getKomplainBySupportUser($user_id, $page = 1, $limit = 10, $search = '', $status_filter = '', $sort_by = 'created_at', $sort_order = 'DESC') {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT k.*, 
                         u.nama as nama_support, 
                         kl.namaklien as nama_klien,
                         kp.notes as process_notes,
                         kp.process_date,
                         up.nama as process_user_name
                  FROM " . $this->table_name . " k 
                  LEFT JOIN users u ON k.idsupport = u.id 
                  LEFT JOIN klien kl ON k.idklien = kl.id
                  LEFT JOIN komplain_process kp ON k.id = kp.komplain_id AND kp.status_to = 'proses'
                  LEFT JOIN users up ON kp.user_id = up.id
                  WHERE kl.iduser = :user_id";
        
        // Add search filter
        if (!empty($search)) {
            $query .= " AND (k.subyek LIKE :search OR kl.namaklien LIKE :search OR u.nama LIKE :search)";
        }
        
        // Add status filter
        if (!empty($status_filter)) {
            $query .= " AND k.status = :status_filter";
        }
        
        // Add sorting
        $query .= " ORDER BY k." . $sort_by . " " . $sort_order . " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $stmt->bindParam(':search', $search_param);
        }
        
        if (!empty($status_filter)) {
            $stmt->bindParam(':status_filter', $status_filter);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Get total komplain by support user
    public function getTotalKomplainBySupportUser($user_id, $search = '', $status_filter = '') {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . " k 
                  LEFT JOIN klien kl ON k.idklien = kl.id
                  WHERE kl.iduser = :user_id";
        
        // Add search filter
        if (!empty($search)) {
            $query .= " AND (k.subyek LIKE :search OR kl.namaklien LIKE :search)";
        }
        
        // Add status filter
        if (!empty($status_filter)) {
            $query .= " AND k.status = :status_filter";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $stmt->bindParam(':search', $search_param);
        }
        
        if (!empty($status_filter)) {
            $stmt->bindParam(':status_filter', $status_filter);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Get total komplain by user with search and status filter
    public function getTotalKomplainByUser($user_id, $user_role, $user_support = false, $user_idklien = null, $search = '', $status_filter = '') {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . " k 
                  LEFT JOIN users u ON k.idsupport = u.id 
                  LEFT JOIN klien kl ON k.idklien = kl.id
                  LEFT JOIN komplain_process kp ON k.id = kp.komplain_id AND kp.status_to = 'proses'
                  LEFT JOIN users up ON kp.user_id = up.id";
        
        if ($user_role === 'support' || $user_support) {
            $query .= " WHERE k.idsupport = :user_id";
        } elseif ($user_role === 'client') {
            $query .= " WHERE k.idsupport = :user_id";
        } else {
            $query .= " WHERE k.idsupport = :user_id";
        }
        
        // Add search filter
        if (!empty($search)) {
            $query .= " AND (k.subyek LIKE :search OR kl.namaklien LIKE :search OR u.nama LIKE :search)";
        }
        
        // Add status filter
        if (!empty($status_filter)) {
            $query .= " AND k.status = :status_filter";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if ($user_role === 'support' || $user_support) {
            $stmt->bindParam(':user_id', $user_id);
        } elseif ($user_role === 'client') {
            $stmt->bindParam(':user_id', $user_id);
        } else {
            $stmt->bindParam(':user_id', $user_id);
        }
        
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $stmt->bindParam(':search', $search_param);
        }
        
        if (!empty($status_filter)) {
            $stmt->bindParam(':status_filter', $status_filter);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>
