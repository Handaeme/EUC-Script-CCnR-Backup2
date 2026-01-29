<?php
namespace App\Models;

class TemplateModel {
    private $conn;

    public function __construct() {
        $configFile = __DIR__ . '/../../config/database.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            // Establish connection
            $this->conn = sqlsrv_connect($config['host'], isset($config['options']) ? $config['options'] : []);
            if (!$this->conn) {
                // Log or handle error silently? RequestModel doesn't die. 
                // But if conn fails, methods will fail.
                die("Database Connection Failed in TemplateModel");
            }
        }
    }

    public function getAll($startDate = null, $endDate = null) {
        $where = "";
        $params = [];
        if ($startDate && $endDate) {
            $where = "WHERE CAST(created_at AS DATE) >= ? AND CAST(created_at AS DATE) <= ?";
            $params = [$startDate, $endDate];
        }
        $sql = "SELECT * FROM script_templates $where ORDER BY created_at DESC";
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        $rows = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function add($title, $filename, $filepath, $user) {
        $sql = "INSERT INTO script_templates (title, filename, filepath, uploaded_by) VALUES (?, ?, ?, ?)";
        $params = [$title, $filename, $filepath, $user];
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            // Error handling
             return false;
        }
        return sqlsrv_rows_affected($stmt);
    }

    public function delete($id) {
        $sql = "DELETE FROM script_templates WHERE id = ?";
        return sqlsrv_query($this->conn, $sql, [$id]);
    }

    public function getById($id) {
        $sql = "SELECT * FROM script_templates WHERE id = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [$id]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return null;
    }
}
