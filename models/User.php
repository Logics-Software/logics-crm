<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password;
    public $nama;
    public $alamat;
    public $email;
    public $foto_profile;
    public $role;
    public $status;
    public $developer;
    public $support;
    public $tagihan;
    public $idklien;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Login user
    public function login($username, $password) {
        $query = "SELECT id, username, password, nama, alamat, email, foto_profile, role, status, developer, support, tagihan, idklien 
                  FROM " . $this->table_name . " 
                  WHERE username = :username AND status = 'aktif'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->nama = $row['nama'];
                $this->alamat = $row['alamat'];
                $this->email = $row['email'];
                $this->foto_profile = $row['foto_profile'];
                $this->role = $row['role'];
                $this->status = $row['status'];
                $this->developer = (bool)$row['developer'];
                $this->support = (bool)$row['support'];
                $this->tagihan = (bool)$row['tagihan'];
                return true;
            }
        }
        return false;
    }

    // Get all users
    public function getAllUsers() {
        $query = "SELECT u.id, u.username, u.nama, u.alamat, u.email, u.foto_profile, u.role, u.status, u.developer, u.support, u.tagihan, u.idklien, u.created_at, k.namaklien 
                  FROM " . $this->table_name . " u 
                  LEFT JOIN klien k ON u.idklien = k.id 
                  ORDER BY u.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Get users with pagination and sorting
    public function getUsersWithPagination($page = 1, $limit = 10, $search = '', $sort_by = 'id', $sort_order = 'asc') {
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause for search
        $where_clause = '';
        $params = [];
        
        if (!empty($search)) {
            $where_clause = "WHERE username LIKE :search OR nama LIKE :search OR email LIKE :search";
            $search_param = '%' . $search . '%';
            $params[':search'] = $search_param;
        }
        
        // Validate sort parameters
        $allowed_sort_fields = ['username', 'nama', 'role', 'status', 'id', 'created_at'];
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'id';
        }
        
        if (!in_array($sort_order, ['asc', 'desc'])) {
            $sort_order = 'asc';
        }
        
        $query = "SELECT u.id, u.username, u.nama, u.alamat, u.email, u.foto_profile, u.role, u.status, u.developer, u.support, u.tagihan, u.idklien, u.created_at, k.namaklien 
                  FROM " . $this->table_name . " u 
                  LEFT JOIN klien k ON u.idklien = k.id 
                  " . $where_clause . "
                  ORDER BY " . $sort_by . " " . $sort_order . "
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind search parameter if exists
        if (!empty($search)) {
            $stmt->bindParam(':search', $search_param);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Get total users count for pagination
    public function getTotalUsers($search = '') {
        $where_clause = '';
        $params = [];
        
        if (!empty($search)) {
            $where_clause = "WHERE username LIKE :search OR nama LIKE :search OR email LIKE :search";
            $search_param = '%' . $search . '%';
            $params[':search'] = $search_param;
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $where_clause;
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $stmt->bindParam(':search', $search_param);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT u.id, u.username, u.nama, u.alamat, u.email, u.foto_profile, u.role, u.status, u.developer, u.support, u.tagihan, u.idklien, k.namaklien 
                  FROM " . $this->table_name . " u 
                  LEFT JOIN klien k ON u.idklien = k.id 
                  WHERE u.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->nama = $row['nama'];
            $this->alamat = $row['alamat'];
            $this->email = $row['email'];
            $this->foto_profile = $row['foto_profile'];
            $this->role = $row['role'];
            $this->status = $row['status'];
            $this->developer = (bool)$row['developer'];
            $this->support = (bool)$row['support'];
            $this->tagihan = (bool)$row['tagihan'];
            $this->idklien = $row['idklien'];
            return true;
        }
        return false;
    }

    // Create user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, password, nama, alamat, email, foto_profile, role, status, developer, support, tagihan, idklien) 
                  VALUES (:username, :password, :nama, :alamat, :email, :foto_profile, :role, :status, :developer, :support, :tagihan, :idklien)";
        
        $stmt = $this->conn->prepare($query);
        
        // Password already hashed in controller
        
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':nama', $this->nama);
        $stmt->bindParam(':alamat', $this->alamat);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':foto_profile', $this->foto_profile);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':developer', $this->developer, PDO::PARAM_BOOL);
        $stmt->bindParam(':support', $this->support, PDO::PARAM_BOOL);
        $stmt->bindParam(':tagihan', $this->tagihan, PDO::PARAM_BOOL);
        $stmt->bindParam(':idklien', $this->idklien);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update user
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username = :username, nama = :nama, alamat = :alamat, 
                      email = :email, foto_profile = :foto_profile, role = :role, status = :status,
                      developer = :developer, support = :support, tagihan = :tagihan, idklien = :idklien";
        
        if(!empty($this->password)) {
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':nama', $this->nama);
        $stmt->bindParam(':alamat', $this->alamat);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':foto_profile', $this->foto_profile);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':developer', $this->developer, PDO::PARAM_BOOL);
        $stmt->bindParam(':support', $this->support, PDO::PARAM_BOOL);
        $stmt->bindParam(':tagihan', $this->tagihan, PDO::PARAM_BOOL);
        $stmt->bindParam(':idklien', $this->idklien);
        $stmt->bindParam(':id', $this->id);
        
        if(!empty($this->password)) {
            $stmt->bindParam(':password', $this->password);
        }
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete user
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update profile (nama, alamat, email, foto_profile)
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nama = :nama, alamat = :alamat, email = :email, foto_profile = :foto_profile
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->nama = htmlspecialchars(strip_tags($this->nama));
        $this->alamat = htmlspecialchars(strip_tags($this->alamat));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(':nama', $this->nama);
        $stmt->bindParam(':alamat', $this->alamat);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':foto_profile', $this->foto_profile);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update password only
    public function updatePassword($hashed_password) {
        $query = "UPDATE " . $this->table_name . " 
                  SET password = :password
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Password is already hashed in the calling function
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Check if username exists (for validation)
    public function usernameExists($username, $exclude_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        
        if($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        
        if($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }
        
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
}
?>
