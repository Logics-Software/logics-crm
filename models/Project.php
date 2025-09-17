<?php

class Project {
    private $conn;
    private $table_name = "project";

    public $id;
    public $namaproyek;
    public $namaperusahaan;
    public $deskripsiproyek;
    public $tanggalkontrak;
    public $lamapengerjaan;
    public $tanggalselesai;
    public $developer;
    public $nilaiproyek;
    public $jumlahtermin;
    public $termin1;
    public $termin2;
    public $termin3;
    public $termin4;
    public $saldoproyek;
    public $status;
    public $iduser;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all projects
    public function getAllProjects() {
        $query = "SELECT id, namaproyek, namaperusahaan, deskripsiproyek, tanggalkontrak, 
                         lamapengerjaan, tanggalselesai, developer, nilaiproyek, jumlahtermin, 
                         termin1, termin2, termin3, termin4, saldoproyek, status, iduser, created_at 
                  FROM " . $this->table_name . " 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Get project by ID
    public function getProjectById($id) {
        $query = "SELECT id, namaproyek, namaperusahaan, deskripsiproyek, tanggalkontrak, 
                         lamapengerjaan, tanggalselesai, developer, nilaiproyek, jumlahtermin, 
                         termin1, termin2, termin3, termin4, saldoproyek, status, iduser 
                  FROM " . $this->table_name . " 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->namaproyek = $row['namaproyek'];
            $this->namaperusahaan = $row['namaperusahaan'];
            $this->deskripsiproyek = $row['deskripsiproyek'];
            $this->tanggalkontrak = $row['tanggalkontrak'];
            $this->lamapengerjaan = $row['lamapengerjaan'];
            $this->tanggalselesai = $row['tanggalselesai'];
            $this->developer = $row['developer'];
            $this->nilaiproyek = $row['nilaiproyek'];
            $this->jumlahtermin = $row['jumlahtermin'];
            $this->termin1 = $row['termin1'];
            $this->termin2 = $row['termin2'];
            $this->termin3 = $row['termin3'];
            $this->termin4 = $row['termin4'];
            $this->saldoproyek = $row['saldoproyek'];
            $this->status = $row['status'];
            $this->iduser = $row['iduser'];
            return true;
        }
        return false;
    }

    // Create project
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (namaproyek, namaperusahaan, deskripsiproyek, tanggalkontrak, lamapengerjaan, 
                   tanggalselesai, developer, nilaiproyek, jumlahtermin, termin1, termin2, 
                   termin3, termin4, saldoproyek, status, iduser) 
                  VALUES (:namaproyek, :namaperusahaan, :deskripsiproyek, :tanggalkontrak, :lamapengerjaan, 
                          :tanggalselesai, :developer, :nilaiproyek, :jumlahtermin, :termin1, :termin2, 
                          :termin3, :termin4, :saldoproyek, :status, :iduser)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':namaproyek', $this->namaproyek);
        $stmt->bindParam(':namaperusahaan', $this->namaperusahaan);
        $stmt->bindParam(':deskripsiproyek', $this->deskripsiproyek);
        $stmt->bindParam(':tanggalkontrak', $this->tanggalkontrak);
        $stmt->bindParam(':lamapengerjaan', $this->lamapengerjaan);
        $stmt->bindParam(':tanggalselesai', $this->tanggalselesai);
        $stmt->bindParam(':developer', $this->developer);
        $stmt->bindParam(':nilaiproyek', $this->nilaiproyek);
        $stmt->bindParam(':jumlahtermin', $this->jumlahtermin);
        $stmt->bindParam(':termin1', $this->termin1);
        $stmt->bindParam(':termin2', $this->termin2);
        $stmt->bindParam(':termin3', $this->termin3);
        $stmt->bindParam(':termin4', $this->termin4);
        $stmt->bindParam(':saldoproyek', $this->saldoproyek);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':iduser', $this->iduser);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update project
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET namaproyek = :namaproyek, namaperusahaan = :namaperusahaan, 
                      deskripsiproyek = :deskripsiproyek, tanggalkontrak = :tanggalkontrak, 
                      lamapengerjaan = :lamapengerjaan, tanggalselesai = :tanggalselesai, 
                      developer = :developer, nilaiproyek = :nilaiproyek, 
                      jumlahtermin = :jumlahtermin, termin1 = :termin1, termin2 = :termin2, 
                      termin3 = :termin3, termin4 = :termin4, saldoproyek = :saldoproyek, 
                      status = :status, iduser = :iduser 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':namaproyek', $this->namaproyek);
        $stmt->bindParam(':namaperusahaan', $this->namaperusahaan);
        $stmt->bindParam(':deskripsiproyek', $this->deskripsiproyek);
        $stmt->bindParam(':tanggalkontrak', $this->tanggalkontrak);
        $stmt->bindParam(':lamapengerjaan', $this->lamapengerjaan);
        $stmt->bindParam(':tanggalselesai', $this->tanggalselesai);
        $stmt->bindParam(':developer', $this->developer);
        $stmt->bindParam(':nilaiproyek', $this->nilaiproyek);
        $stmt->bindParam(':jumlahtermin', $this->jumlahtermin);
        $stmt->bindParam(':termin1', $this->termin1);
        $stmt->bindParam(':termin2', $this->termin2);
        $stmt->bindParam(':termin3', $this->termin3);
        $stmt->bindParam(':termin4', $this->termin4);
        $stmt->bindParam(':saldoproyek', $this->saldoproyek);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':iduser', $this->iduser);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete project
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get projects with pagination and search
    public function getProjectsWithPagination($page = 1, $limit = 10, $search = '', $status_filter = '', $sort_by = 'namaproyek', $sort_order = 'ASC') {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = [];
        $params = [];
        
        if(!empty($search)) {
            $where_conditions[] = "(namaproyek LIKE :search OR namaperusahaan LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if(!empty($status_filter)) {
            $where_conditions[] = "status = :status";
            $params[':status'] = $status_filter;
        }
        
        $where_clause = '';
        if(!empty($where_conditions)) {
            $where_clause = "WHERE " . implode(' AND ', $where_conditions);
        }
        
        // Validate sort parameters
        $allowed_sort_fields = ['namaproyek', 'namaperusahaan', 'nilaiproyek', 'status', 'tanggalkontrak', 'id', 'created_at'];
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'namaproyek';
        }
        
        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'ASC';
        }
        
        $query = "SELECT id, namaproyek, namaperusahaan, deskripsiproyek, tanggalkontrak, 
                         lamapengerjaan, tanggalselesai, developer, nilaiproyek, jumlahtermin, 
                         termin1, termin2, termin3, termin4, saldoproyek, status, iduser, created_at 
                  FROM " . $this->table_name . " 
                  " . $where_clause . " 
                  ORDER BY " . $sort_by . " " . $sort_order . " 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        if(!empty($search)) {
            $stmt->bindParam(':search', $params[':search']);
        }
        if(!empty($status_filter)) {
            $stmt->bindParam(':status', $params[':status']);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }

    // Get total projects count for pagination
    public function getTotalProjects($search = '', $status_filter = '') {
        $where_conditions = [];
        $params = [];
        
        if(!empty($search)) {
            $where_conditions[] = "(namaproyek LIKE :search OR namaperusahaan LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        if(!empty($status_filter)) {
            $where_conditions[] = "status = :status";
            $params[':status'] = $status_filter;
        }
        
        $where_clause = '';
        if(!empty($where_conditions)) {
            $where_clause = "WHERE " . implode(' AND ', $where_conditions);
        }
        
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " " . $where_clause;
        
        $stmt = $this->conn->prepare($query);
        
        if(!empty($search)) {
            $stmt->bindParam(':search', $params[':search']);
        }
        if(!empty($status_filter)) {
            $stmt->bindParam(':status', $params[':status']);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }

    // Get project statistics
    public function getProjectStats() {
        $query = "SELECT 
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 'kontrak' THEN 1 ELSE 0 END) as kontrak,
                    SUM(CASE WHEN status = 'develop' THEN 1 ELSE 0 END) as develop,
                    SUM(CASE WHEN status = 'implementasi' THEN 1 ELSE 0 END) as implementasi,
                    SUM(CASE WHEN status = 'garansi' THEN 1 ELSE 0 END) as garansi,
                    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                    SUM(CASE WHEN status = 'perawatan' THEN 1 ELSE 0 END) as perawatan,
                    SUM(nilaiproyek) as total_nilai,
                    SUM(saldoproyek) as total_saldo
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
