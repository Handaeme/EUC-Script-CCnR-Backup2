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
            $where = "WHERE CAST(t.created_at AS DATE) >= ? AND CAST(t.created_at AS DATE) <= ?";
            $params = [$startDate, $endDate];
        }
        
        // JOIN to get Group Name
        $sql = "SELECT t.*, u.group_name 
                FROM script_templates t 
                LEFT JOIN script_users u ON t.uploaded_by = u.username 
                $where 
                ORDER BY t.created_at DESC";
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        $rows = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function add($title, $filename, $filepath, $user, $description = null) {
        // Try INSERT with Description
        $sql = "INSERT INTO script_templates (title, filename, filepath, uploaded_by, description) VALUES (?, ?, ?, ?, ?)";
        $params = [$title, $filename, $filepath, $user, $description];
        
        // Suppress error logging for the first attempt to confirm success manually
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            // Check errors to see if it is "Invalid column name" or generic failure
            $errors = sqlsrv_errors();
            $isColumnMissing = false;
            
            if ($errors) {
                foreach ($errors as $error) {
                    // SQL State 42S22 = Invalid column name
                    if ($error['SQLSTATE'] == '42S22' || strpos($error['message'], 'Invalid column name') !== false) {
                         $isColumnMissing = true;
                         break;
                    }
                }
            }
            
            if ($isColumnMissing || $stmt === false) {
                // FALLBACK: Try Legacy Insert (No Description)
                $sqlFallback = "INSERT INTO script_templates (title, filename, filepath, uploaded_by) VALUES (?, ?, ?, ?)";
                $paramsFallback = [$title, $filename, $filepath, $user];
                $stmtFallback = sqlsrv_query($this->conn, $sqlFallback, $paramsFallback);
                
                if ($stmtFallback === false) {
                    return false; // Both failed
                }
                return sqlsrv_rows_affected($stmtFallback);
            }
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
