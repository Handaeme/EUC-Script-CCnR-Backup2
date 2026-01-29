<?php
namespace App\Models;

class RequestModel {
    private $conn;

    public function __construct() {
        // Fix Path: Go up 2 levels from app/models -> root, then into config
        $configFile = __DIR__ . '/../../config/database.php';
        
        if (!file_exists($configFile)) {
            die("Database config not found at: " . $configFile);
        }
        $config = require $configFile;
        $this->conn = sqlsrv_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);
        if (!$this->conn) {
            die(print_r(sqlsrv_errors(), true));
        }
    }
    public function getUsersByRole($role) {
        // Query both columns because setup.php might have swapped them
        // Schema Adapt: User table uses 'username', not 'userid'. No 'fullname' column mentioned in error.
        $sql = "SELECT username as userid, username as fullname FROM script_users WHERE job_function = ? OR group_name = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [$role, $role]);
        $users = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $users[] = $row;
            }
        }
        return $users;
    }

    // NEW: Specific fetch for Division Head (SPV) using Simplified Role
    public function getSupervisors() {
        // Schema Adapt: Use 'username'
        $sql = "SELECT username as userid, username as fullname FROM script_users WHERE role_code = 'SPV'";
        $stmt = sqlsrv_query($this->conn, $sql);
        $users = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $users[] = $row;
            }
        }
        return $users;
    }

    // NEW: Fetch Pending Requests for a Specific User/Role

    public function getPendingRequests($userid, $role, $startDate = null, $endDate = null) {
        $sql = "";
        $params = [];
        $where = "";

        // Define base conditions based on role
        if ($role === 'SPV') {
            $where = "WHERE selected_spv = ? AND status = 'CREATED'";
            $params[] = $userid;
        } elseif ($role === 'PIC') {
            $where = "WHERE status = 'APPROVED_SPV'";
        } elseif ($role === 'PROCEDURE') {
            $where = "WHERE status = 'APPROVED_PIC'";
        } elseif ($role === 'MAKER') {
            $where = "WHERE created_by = ? AND status IN ('REVISION', 'REJECTED')";
            $params[] = $userid;
        }

        // Apply filters and order if base condition exists
        if ($where) {
            // Date Filter
            if ($startDate && $endDate) {
                // Determine which date column to use based on role
                // SPV usually checks created_at, others often check updated_at or created_at
                // Let's standardize on created_at for all filtering for consistency from user perspective?
                // Or updated_at? "When was it assigned to me?"
                // Standard Practice: "Created Date" usually implies request creation.
                $col = ($role === 'MAKER' || $role === 'SPV') ? 'created_at' : 'updated_at';
                
                $where .= " AND CAST($col AS DATE) >= ? AND CAST($col AS DATE) <= ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }

            // Order By (Preserve existing logic)
            $orderBy = "ORDER BY updated_at DESC";
            if ($role === 'SPV') {
                $orderBy = "ORDER BY created_at DESC";
            }

            $sql = "SELECT * FROM script_request $where $orderBy";
            
            $stmt = sqlsrv_query($this->conn, $sql, $params);
            $requests = [];
            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $requests[] = $row;
                }
            }
            return $requests;
        }
        return [];
    }

    public function getMakerStats($userId) {
        $sql = "SELECT 
                    SUM(CASE WHEN status IN ('REVISION', 'REJECTED') THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status IN ('CREATED', 'APPROVED_SPV', 'APPROVED_PIC', 'APPROVED_PROCEDURE') THEN 1 ELSE 0 END) as wip,
                    SUM(CASE WHEN status IN ('CLOSED', 'LIBRARY') THEN 1 ELSE 0 END) as completed
                FROM script_request 
                WHERE created_by = ?";
        
        $stmt = sqlsrv_query($this->conn, $sql, [$userId]);
        if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            return $row;
        }
        return ['pending' => 0, 'wip' => 0, 'completed' => 0];
    }

    public function getRequestById($id) {
        $sql = "SELECT * FROM script_request WHERE id = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [$id]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return null;
    }

    public function getPreviewContent($requestId) {
        $sql = "SELECT * FROM script_preview_content WHERE request_id = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [$requestId]);
        $rows = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
    public function createRequest($data) {
        // 1. Generate Ticket ID (SC-XXXX)
        // Get the last ticket_id
        // 1. Generate Ticket ID (SC-XXXX)
        // Get the last ticket_id
        $lastSql = "SELECT TOP 1 ticket_id FROM script_request WHERE ticket_id LIKE 'SC-%' ORDER BY id DESC";
        $lastStmt = sqlsrv_query($this->conn, $lastSql);
        
        $nextNumber = 1;
        if ($lastStmt && $lastRow = sqlsrv_fetch_array($lastStmt, SQLSRV_FETCH_ASSOC)) {
            // Extract number from "SC-XXXX"
            $parts = explode('-', $lastRow['ticket_id'] ?? '');
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $nextNumber = intval($parts[1]) + 1;
            }
        }
        
        $ticketId = sprintf("SC-%04d", $nextNumber);
        $ticketNumber = $ticketId; // Store string directly
        
        // 2. Generate Script Number (KONV/SYR-MEDIA-DD/MM/YY-0001-01)
        $jenisCode = ($data['jenis'] === 'Konvensional') ? 'KONV' : 'SYR';
        
        // Media code mapping
        $mediaMapping = [
            'WhatsApp' => 'WA',
            'Robocoll' => 'RC',
            'Surat' => 'SR',
            'Email' => 'EM',
            'VB' => 'VB',
            'Chatbot' => 'CB',
            'SMS' => 'SM',
            'Others' => 'OT'
        ];
        
        // Abbreviate all selected media
        $mediaParts = array_map('trim', explode(',', $data['media']));
        $abbreviations = [];
        foreach ($mediaParts as $part) {
            $abbreviations[] = isset($mediaMapping[$part]) ? $mediaMapping[$part] : 'OT';
        }
        $mediaCode = implode('/', array_unique($abbreviations));
        
        $dateCode = date('d/m/y'); // DD/MM/YY
        
        // Counter logic (per media combination)
        $counterSql = "SELECT COUNT(*) as total FROM script_request WHERE script_number LIKE ?";
        $pattern = $jenisCode . '-' . $mediaCode . '-%';
        $counterStmt = sqlsrv_query($this->conn, $counterSql, [$pattern]);
        $counterRow = sqlsrv_fetch_array($counterStmt, SQLSRV_FETCH_ASSOC);
        $counter = $counterRow['total'] + 1;
        
        $version = 1; // Default version
        
        $scriptNumber = sprintf("%s-%s-%s-%04d-%02d", $jenisCode, $mediaCode, $dateCode, $counter, $version);

        // 3. Insert into script_request
        $sql = "INSERT INTO script_request (
            ticket_id, script_number, title, jenis, produk, kategori, media, mode, 
            status, current_role, version, created_by, selected_spv, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'CREATED', 'Supervisor', ?, ?, ?, GETDATE()); SELECT SCOPE_IDENTITY() as id";

        // Title (Use provided or auto-generated)
        $title = !empty($data['title']) ? $data['title'] : ("Script Request " . $data['jenis'] . " - " . $data['media']);
        
        $params = [
            $ticketNumber, // Insert the integer Ticket Number
            $scriptNumber,
            $title,
            $data['jenis'],
            $data['produk'],
            $data['kategori'],
            $data['media'],
            $data['mode'],
            $version,
            $data['creator_id'],
            $data['selected_spv']
        ];

        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
             $errors = sqlsrv_errors();
             $msg = "SQL Error: ";
             if ($errors != null) {
                 foreach ($errors as $error) {
                     $msg .= $error['message'] . " ";
                 }
             }
             return ['error' => $msg];
        }

        sqlsrv_next_result($stmt);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        return ['id' => $row['id'], 'number' => $scriptNumber, 'ticket_id' => $ticketId];
    }

    public function savePreviewContent($scriptId, $media, $content, $user) {
        $sql = "INSERT INTO script_preview_content (request_id, media, content, updated_by, updated_at) VALUES (?, ?, ?, ?, GETDATE())";
        $params = [$scriptId, $media, $content, $user];
        return sqlsrv_query($this->conn, $sql, $params);
    }

    public function saveFileInfo($scriptId, $type, $originalName, $path, $user) {
        $sql = "INSERT INTO script_files (request_id, file_type, original_filename, filepath, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, GETDATE()); SELECT SCOPE_IDENTITY() AS id";
        $params = [$scriptId, $type, $originalName, $path, $user];
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            return false;
        }

        sqlsrv_next_result($stmt); // Move to the SELECT result
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        return $row['id'] ?? false;
    }

    public function deleteReviewDoc($fileId) {
        $sql = "DELETE FROM script_files WHERE id = ?";
        return sqlsrv_query($this->conn, $sql, [$fileId]);
    }

    public function getFileById($fileId) {
        $sql = "SELECT * FROM script_files WHERE id = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [$fileId]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return null;
    }
    public function updatePreviewContent($id, $content) {
        $sql = "UPDATE script_preview_content SET content = ? WHERE id = ?";
        return sqlsrv_query($this->conn, $sql, [$content, $id]);
    }

    public function updateStatus($id, $status, $nextRole, $user) {
        $sql = "UPDATE script_request SET status = ?, current_role = ?, updated_at = GETDATE() WHERE id = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [$status, $nextRole, $id]);
        return $stmt;
    }

    public function setDraftStatus($id, $hasDraft) {
        $sql = "UPDATE script_request SET has_draft = ?, updated_at = GETDATE() WHERE id = ?";
        return sqlsrv_query($this->conn, $sql, [$hasDraft, $id]);
    }

    public function updateRequestMetadata($id, $data) {
        $sql = "UPDATE script_request SET jenis = ?, produk = ?, kategori = ?, media = ?, mode = ?, selected_spv = ?, updated_at = GETDATE() WHERE id = ?";
        $params = [
            $data['jenis'],
            $data['produk'],
            $data['kategori'],
            $data['media'],
            $data['mode'], // Note: Controller usually passes this as 'input_mode', ensure mapping correct
            $data['selected_spv'],
            $id
        ];
        return sqlsrv_query($this->conn, $sql, $params);
    }
    
    public function getRejectionReason($requestId) {
        $sql = "SELECT TOP 1 details FROM script_audit_trail WHERE request_id = ? AND action IN ('REVISION', 'REJECTED') ORDER BY created_at DESC";
        $stmt = sqlsrv_query($this->conn, $sql, [$requestId]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return $row['details'];
        }
        return '';
    }

    public function getLatestDraftNote($requestId) {
        $sql = "SELECT TOP 1 details FROM script_audit_trail WHERE request_id = ? AND action = 'DRAFT_SAVED' ORDER BY created_at DESC";
        $stmt = sqlsrv_query($this->conn, $sql, [$requestId]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            // Details format: "Draft saved by Maker. Note: [Content]"
            // We need to extract the content.
            $raw = $row['details'];
            if (strpos($raw, 'Note: ') !== false) {
                return trim(substr($raw, strpos($raw, 'Note: ') + 6));
            }
        }
        return '';
    }

    public function getFiles($requestId) {
        $sql = "SELECT * FROM script_files WHERE request_id = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [$requestId]);
        $rows = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function logAudit($requestId, $scriptNumber, $action, $role, $user, $details) {
        $sql = "INSERT INTO script_audit_trail (request_id, script_number, action, user_role, user_id, details, created_at) VALUES (?, ?, ?, ?, ?, ?, GETDATE())";
        return sqlsrv_query($this->conn, $sql, [$requestId, $scriptNumber, $action, $role, $user, $details]);
    }

    public function finalizeLibrary($requestId) {
        // 1. Get Request Info
        $req = $this->getRequestById($requestId);
        if (!$req) return false;

        // 2. Get Content
        $contentList = $this->getPreviewContent($requestId);

        // 3. Insert specific rows into Library
        if ($req['mode'] === 'FILE_UPLOAD' && !empty($contentList)) {
            // FIX: File Upload has identical content for all media (WA, SMS, etc.)
            // So we only insert ONE row to avoid duplicates in Library.
            $c = $contentList[0]; 
            
            // 1. Move to Library (Single Entry)
            $sql = "INSERT INTO script_library (request_id, script_number, media, content, version, created_at) VALUES (?, ?, ?, ?, ?, GETDATE())";
            $params = [$requestId, $req['script_number'], $c['media'], $c['content'], $req['version']];
            if (!sqlsrv_query($this->conn, $sql, $params)) {
                return false;
            }
        } else {
            // Free Input: Insert ALL rows (WA, SMS, Email have different text)
            foreach ($contentList as $c) {
                $sql = "INSERT INTO script_library (request_id, script_number, media, content, version, created_at) VALUES (?, ?, ?, ?, ?, GETDATE())";
                $params = [$requestId, $req['script_number'], $c['media'], $c['content'], $req['version']];
                if (!sqlsrv_query($this->conn, $sql, $params)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getLibraryItems($startDate = null, $endDate = null) {
        $where = "";
        $params = [];
        if ($startDate && $endDate) {
            $where = "WHERE CAST(created_at AS DATE) >= ? AND CAST(created_at AS DATE) <= ?";
            $params = [$startDate, $endDate];
        }
        
        // Simple query for now (will fix JOIN later)
        $sql = "SELECT * FROM script_library $where ORDER BY created_at DESC";
        
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            error_log("SQL Error in getLibraryItems: " . print_r(sqlsrv_errors(), true));
            return [];
        }
        
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }
        
        error_log("Library items count: " . count($rows));
        return $rows;
    }
    
    public function getLibraryItemsWithContent($startDate = null, $endDate = null) {
        $items = $this->getLibraryItems($startDate, $endDate);
        
        // Deduplication Logic: Group by Request ID
        $uniqueScripts = [];

        // Enrich each item with content preview AND ticket_id from script_requests
        foreach ($items as &$item) {
            $reqId = $item['request_id'];

            // Skip if already processed for the list view
            if (isset($uniqueScripts[$reqId])) {
                continue;
            }

            // Get content preview
            $sql = "SELECT TOP 1 html_content FROM script_preview_content WHERE request_id = ? ORDER BY id ASC";
            $stmt = sqlsrv_query($this->conn, $sql, [$item['request_id']]);
            
            if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $item['content_preview'] = $row['html_content'];
            } else {
                $item['content_preview'] = '';
                error_log("Request ID {$item['request_id']}: No content found in script_preview_content");
            }
            
            
            // Get ticket_id, title, mode, and metadata from script_request
            $sql2 = "SELECT ticket_id, title, mode, jenis, produk, kategori, media, created_at FROM script_request WHERE id = ?";
            $stmt2 = sqlsrv_query($this->conn, $sql2, [$item['request_id']]);
            
            if ($stmt2 && $row2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
                $item['ticket_id'] = $row2['ticket_id'];
                $item['title'] = $row2['title'];
                $item['mode'] = $row2['mode'];
                $item['jenis'] = $row2['jenis'];
                $item['produk'] = $row2['produk'];
                $item['kategori'] = $row2['kategori'];
                $item['media'] = $row2['media'];
                $item['request_created_at'] = $row2['created_at'];
                
                // If File Upload, get original filename
                if ($row2['mode'] === 'FILE_UPLOAD') {
                    // Try to get TEMPLATE file first (main script file)
                    $sql3 = "SELECT TOP 1 original_filename FROM script_files WHERE request_id = ? AND file_type = 'TEMPLATE'";
                    $stmt3 = sqlsrv_query($this->conn, $sql3, [$item['request_id']]);
                    if ($stmt3 && $row3 = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC)) {
                        $item['filename'] = $row3['original_filename'];
                    } else {
                        $item['filename'] = 'Attached File';
                    }
                }
                
                error_log("Request ID {$item['request_id']}: ticket_id = " . ($row2['ticket_id'] ?? 'NULL'));
            } else {
                error_log("Request ID {$item['request_id']}: Failed to fetch from script_request");
            }

            // Add to unique list
            $uniqueScripts[$reqId] = $item;
        }
        
        // Return indexed array of unique items
        return array_values($uniqueScripts);
    }

    public function getAllAuditLogs($startDate = null, $endDate = null) {
        // Get all audit logs without JOIN (simpler, more reliable)
        $where = "";
        $params = [];
        if ($startDate && $endDate) {
            $where = "WHERE CAST(created_at AS DATE) >= ? AND CAST(created_at AS DATE) <= ?";
            $params = [$startDate, $endDate];
        }
        $sql = "SELECT * FROM script_audit_trail $where ORDER BY created_at DESC";
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        $rows = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function getUserRequests($userId, $startDate = null, $endDate = null) {
        // Get all requests created by specific user
        $where = "WHERE created_by = ?";
        $params = [$userId];
        if ($startDate && $endDate) {
            $where .= " AND CAST(created_at AS DATE) >= ? AND CAST(created_at AS DATE) <= ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        $sql = "SELECT * FROM script_request $where ORDER BY created_at DESC";
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        $rows = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function getScriptFile($requestId) {
        $sql = "SELECT TOP 1 * FROM script_files WHERE request_id = ? AND file_type = 'TEMPLATE' ORDER BY id DESC";
        $stmt = sqlsrv_query($this->conn, $sql, [$requestId]);
        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return null;
    }

    public function getReviewDocuments($requestId) {
        // Get review documents for a request
        $sql = "SELECT * FROM script_files WHERE request_id = ? AND file_type IN ('LEGAL', 'CX', 'LEGAL_SYARIAH', 'LPP')";
        $stmt = sqlsrv_query($this->conn, $sql, [$requestId]);
        $rows = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function getUserApprovalHistory($userId, $startDate = null, $endDate = null) {
        $dateFilter = "";
        $params = [$userId];
        
        if ($startDate && $endDate) {
            $dateFilter = " AND CAST(a.created_at AS DATE) >= ? AND CAST(a.created_at AS DATE) <= ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql = "SELECT a.action as my_last_action, a.created_at as my_action_date, a.note as my_note, r.*
                FROM script_audit_trail a
                JOIN script_request r ON a.request_id = r.id
                WHERE a.user_id = ? $dateFilter
                ORDER BY a.created_at DESC";
        
        $stmt = sqlsrv_query($this->conn, $sql, [$userId]);
        $rows = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function getAuditExportData($startDate = null, $endDate = null) {
        // Get all requests with aggregated audit data
        $where = "";
        $params = [];
        if ($startDate && $endDate) {
            $where = "WHERE CAST(r.created_at AS DATE) >= ? AND CAST(r.created_at AS DATE) <= ?";
            $params = [$startDate, $endDate];
        }
        $sql = "SELECT r.* FROM script_request r $where ORDER BY r.created_at DESC";
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        $exportData = [];
        if ($stmt) {
            while ($req = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $requestId = $req['id'];
                
                // Get audit logs for this request
                $auditSql = "SELECT * FROM script_audit_trail WHERE request_id = ? ORDER BY created_at ASC";
                $auditStmt = sqlsrv_query($this->conn, $auditSql, [$requestId]);
                
                $maker = $req['created_by'];
                $spv = $spvStatus = $spvTimestamp = '';
                $pic = $picStatus = $picTimestamp = '';
                $procStatus = $procTimestamp = '';
                
                if ($auditStmt) {
                    while ($audit = sqlsrv_fetch_array($auditStmt, SQLSRV_FETCH_ASSOC)) {
                        $action = $audit['action'];
                        $role = $audit['user_role'];
                        $userId = $audit['user_id'];
                        $timestamp = $audit['created_at'] ? $audit['created_at']->format('Y-m-d H:i:s') : '';
                        
                        if ($role === 'SPV' && in_array($action, ['APPROVE_SPV', 'REJECTED', 'REVISION'])) {
                            $spv = $userId;
                            $spvStatus = $action;
                            $spvTimestamp = $timestamp;
                        } elseif ($role === 'PIC' && in_array($action, ['APPROVE_PIC', 'REJECTED', 'REVISION'])) {
                            $pic = $userId;
                            $picStatus = $action;
                            $picTimestamp = $timestamp;
                        } elseif ($role === 'PROCEDURE' && in_array($action, ['APPROVE_PROCEDURE', 'REJECTED', 'REVISION'])) {
                            $procStatus = $action;
                            $procTimestamp = $timestamp;
                        }
                    }
                }
                
                // Get Content
                $contentSql = "SELECT * FROM script_preview_content WHERE request_id = ?";
                $contentStmt = sqlsrv_query($this->conn, $contentSql, [$requestId]);
                $scriptContent = '';
                
                if ($req['mode'] === 'FILE_UPLOAD') {
                    // Get filename
                    $fileSql = "SELECT original_filename FROM script_files WHERE request_id = ? AND file_type = 'TEMPLATE'";
                    $fileStmt = sqlsrv_query($this->conn, $fileSql, [$requestId]);
                    if ($fileStmt && $fileRow = sqlsrv_fetch_array($fileStmt, SQLSRV_FETCH_ASSOC)) {
                        $scriptContent = $fileRow['original_filename'];
                    }
                } else {
                    // Free input: format as "WA:\n[content]\n\nSMS:\n[content]"
                    $contentParts = [];
                    if ($contentStmt) {
                        while ($content = sqlsrv_fetch_array($contentStmt, SQLSRV_FETCH_ASSOC)) {
                            $media = $content['media'];
                            $text = strip_tags($content['content']); // Remove HTML
                            $contentParts[] = "$media:\n$text";
                        }
                    }
                    $scriptContent = implode("\n\n", $contentParts);
                }
                
                // Get files for Legal/CX review (placeholder, you'll add upload logic later)
                $legalReview = '';
                $cxReview = '';
                $legalSyariah = '';
                $lpp = '';
                
                // Fetch document status
                $docsSql = "SELECT * FROM script_files WHERE request_id = ? AND file_type IN ('LEGAL', 'CX', 'LEGAL_SYARIAH', 'LPP')";
                $docsStmt = sqlsrv_query($this->conn, $docsSql, [$requestId]);
                if ($docsStmt) {
                    while ($doc = sqlsrv_fetch_array($docsStmt, SQLSRV_FETCH_ASSOC)) {
                        if ($doc['file_type'] === 'LEGAL') $legalReview = 'Uploaded';
                        if ($doc['file_type'] === 'CX') $cxReview = 'Uploaded';
                        if ($doc['file_type'] === 'LEGAL_SYARIAH') $legalSyariah = 'Uploaded';
                        if ($doc['file_type'] === 'LPP') $lpp = 'Uploaded';
                    }
                }
                
                // Determine overall status
                $overallStatus = 'SUBMITTED';
                if ($spvStatus || $picStatus || $procStatus) {
                    $overallStatus = 'WIP';
                }
                if ($procStatus === 'APPROVE_PROCEDURE') {
                    $overallStatus = 'CLOSED';
                }
                
                $exportData[] = [
                    'id' => $requestId, // Add id for detail links
                    'ticket_id' => $req['ticket_id'] ?? $requestId, // Use real Ticket ID
                    'script_number' => $req['script_number'],
                    'mode' => $req['mode'], // Add mode for view logic
                    'jenis' => $req['jenis'],
                    'produk' => $req['produk'],
                    'kategori' => $req['kategori'],
                    'status' => $overallStatus,
                    'channel' => $req['media'],
                    'script_content' => $scriptContent,
                    'created_date' => $req['created_at'] ? $req['created_at']->format('Y-m-d H:i:s') : '',
                    'maker' => $maker,
                    'spv' => $spv,
                    'status_spv' => $spvStatus,
                    'timestamp_spv' => $spvTimestamp,
                    'pic' => $pic,
                    'status_pic' => $picStatus,
                    'timestamp_pic' => $picTimestamp,
                    'status_procedure' => $procStatus,
                    'timestamp_procedure' => $procTimestamp,
                    'legal_review' => $legalReview,
                    'cx_review' => $cxReview,
                    'legal_syariah' => $legalSyariah,
                    'lpp' => $lpp
                ];
            }
        }
        
        return $exportData;
    }

    public function getRequestDetail($id) {
        // 1. Get Request Metadata
        $request = $this->getRequestById($id);
        if (!$request) return null;

        // 2. Get Audit Logs (Joined with User Info for Group Name)
        $sql = "SELECT a.*, u.group_name 
                FROM script_audit_trail a 
                LEFT JOIN script_users u ON a.user_id = u.username 
                WHERE a.request_id = ? 
                ORDER BY a.created_at ASC";
        $stmt = sqlsrv_query($this->conn, $sql, [$id]);
        $logs = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $logs[] = $row;
            }
        }

        // 3. Get Documents
        $sql = "SELECT * FROM script_files WHERE request_id = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [$id]);
        $files = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $files[$row['file_type']] = $row;
            }
        }

        // 4. Get Script Content with Version History
        $content = [];
        
        if (($request['mode'] ?? '') === 'FILE_UPLOAD') {
            // File Upload Mode
            $content['type'] = 'file';
            
            if (isset($files['TEMPLATE'])) {
                $content['filename'] = $files['TEMPLATE']['original_filename'];
                $content['path'] = $files['TEMPLATE']['filepath'];
            }
            
            // Fetch ALL versions with metadata (ordered chronologically)
            $sql = "SELECT 
                        id,
                        content,
                        workflow_stage,
                        created_by,
                        version_number,
                        action_type,
                        CONVERT(varchar, created_at, 120) as formatted_date,
                        created_at
                    FROM script_preview_content 
                    WHERE request_id = ? 
                    ORDER BY created_at ASC, id ASC";
            
            $allVersions = [];
            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $allVersions[] = $row; // Store raw rows
                }
            }
            
            // GROUPING LOGIC: Group rows by timestamp (Tolerance: Same Minute)
            // Needed because Multi-Media Uploads create multiple rows (Email, Whatsapp)
            $groupedVersions = [];
            foreach ($allVersions as $ver) {
                $ts = $ver['formatted_date'] ?? $ver['created_at']->format('Y-m-d H:i:s');
                $key = substr($ts, 0, 16); // Minute Grouping
                $key .= '_' . $ver['workflow_stage']; 
                
                if (!isset($groupedVersions[$key])) {
                     $groupedVersions[$key] = [
                         'meta' => $ver,
                         'sheets' => []
                     ];
                }
                $groupedVersions[$key]['sheets'][] = $ver;
            }

            // Synthesize Final Versions (Tabbed)
            $finalVersions = [];
            $vCounter = 1;

            foreach ($groupedVersions as $group) {
                $meta = $group['meta'];
                $sheets = $group['sheets'];
                
                // Construct HTML for this version
                $uniqueId = time() . rand(1000, 9999);
                $html = '<div class="sheet-container" id="container-' . $uniqueId . '">';
                
                // FIX: Check if content already contains tabs (Corrupt/Formatted Data)
                // If yes, we use the first sheet's content (which is the full formatted script) 
                // and skip generating outer media tabs.
                $hasPrebuiltTabs = false;
                if (!empty($sheets) && isset($sheets[0]['content']) && strpos($sheets[0]['content'], 'sheet-tabs-nav') !== false) {
                    $hasPrebuiltTabs = true;
                }

                if ($hasPrebuiltTabs) {
                     // Use First Row Only (Duplicate Detection)
                     $html .= $sheets[0]['content'];
                } else {
                    // Standard Logic: Generate Tabs for each sheet/media
                    
                    // TABS HEADER
                    $html .= '<div class="sheet-tabs-nav" contenteditable="false" style="background:#f9fafb; padding:10px; border-bottom:1px solid #eee; display:flex; gap:8px; overflow-x:auto;">';
                    foreach ($sheets as $idx => $sheet) {
                        $activeClass = ($idx === 0) ? 'active' : '';
                        $sheetName = htmlspecialchars($sheet['media']); // e.g. "EMAIL"
                        $sheetId = 'sheet-' . $uniqueId . '-' . ($idx + 1);
                        $btnId = 'btn-' . $sheetId;
                        
                        // Icon logic
                        $icon = '<i class="bi-file-text"></i>';
                        if (stripos($sheetName, 'WHATSAPP') !== false) $icon = '<i class="bi-whatsapp"></i>';
                        elseif (stripos($sheetName, 'EMAIL') !== false) $icon = '<i class="bi-envelope"></i>';
                        
                        $html .= "<button type='button' id='$btnId' class='btn-sheet $activeClass' onclick=\"changeSheet('$sheetId')\" style='display:flex; align-items:center; gap:6px;'>$icon $sheetName</button>";
                    }
                    $html .= '</div>';
                    
                    // CONTENT PANES
                    foreach ($sheets as $idx => $sheet) {
                        $displayStyle = ($idx === 0) ? 'block' : 'none';
                        $sheetId = 'sheet-' . $uniqueId . '-' . ($idx + 1);
                        
                        // For File Upload, content IS HTML string (from FileHandler)
                        // We must output it as is.
                        $contentSafe = $sheet['content']; 
                        
                        $html .= "<div id='$sheetId' class='sheet-pane' contenteditable='false' style='display:$displayStyle;'>";
                        $html .= $contentSafe;
                        $html .= "</div>";
                    }
                }
                $html .= '</div>';

                $finalVersions[] = [
                    'id' => $meta['id'],
                    'version_number' => $vCounter++,
                    'workflow_stage' => $meta['workflow_stage'],
                    'created_by' => $meta['created_by'],
                    'formatted_date' => $meta['formatted_date'],
                    'content' => $html
                ];
            }
            
            $content['versions'] = $finalVersions;
            
            // Keep backward compatibility: Also provide latest version in old format
            if (!empty($versions)) {
                $latestVersion = end($versions);
                $content['html_preview'] = $latestVersion['content'];
            }
            
        } else {
            // Free Input Mode - Get ALL versions grouped by media
            $sql = "SELECT 
                        id,
                        media,
                        content,
                        workflow_stage,
                        created_by,
                        version_number,
                        action_type,
                        CONVERT(varchar, created_at, 120) as formatted_date,
                        created_at
                    FROM script_preview_content 
                    WHERE request_id = ?
                    ORDER BY created_at ASC, id ASC";
            
            $stmt = sqlsrv_query($this->conn, $sql, [$id]);
            $allVersions = [];
            
            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $allVersions[] = $row;
                }
            }
            
            // GROUPING LOGIC: Group rows by timestamp (Tolerance: Same Minute)
            // This treats multiple sheets saved close together as ONE version
            $groupedVersions = [];
            foreach ($allVersions as $ver) {
                // Get timestamp string
                $ts = $ver['formatted_date'] ?? $ver['created_at']->format('Y-m-d H:i:s');
                
                // Truncate to Minute (First 16 chars: "YYYY-MM-DD HH:MM")
                // This ensures that even if inserts crossed a second boundary (10:00:05 vs 10:00:06), they group together.
                $key = substr($ts, 0, 16);
                
                // Use workflow stage as secondary key to prevent merging different stages
                $key .= '_' . $ver['workflow_stage']; 
                
                if (!isset($groupedVersions[$key])) {
                     $groupedVersions[$key] = [
                         'meta' => $ver,
                         'sheets' => []
                     ];
                }
                $groupedVersions[$key]['sheets'][] = $ver;
            }

            // Synthesize Final Versions Array with HTML Content (Tabs)
            $finalVersions = [];
            $vCounter = 1;

            foreach ($groupedVersions as $group) {
                $meta = $group['meta'];
                $sheets = $group['sheets'];
                
                // Construct HTML for this version (Tabbed Interface)
                $uniqueId = time() . rand(1000, 9999);
                $html = '<div class="sheet-container" id="container-' . $uniqueId . '">';
                
                // TABS HEADER
                $html .= '<div class="sheet-tabs-nav" contenteditable="false" style="background:#f9fafb; padding:10px; border-bottom:1px solid #eee; display:flex; gap:8px; overflow-x:auto;">';
                foreach ($sheets as $idx => $sheet) {
                    $activeClass = ($idx === 0) ? 'active' : '';
                    $sheetName = htmlspecialchars($sheet['media']);
                    $sheetId = 'sheet-' . $uniqueId . '-' . ($idx + 1);
                    $btnId = 'btn-' . $sheetId;
                    
                    // Icon logic
                    $icon = '<i class="bi-file-text"></i>';
                    if (stripos($sheetName, 'WHATSAPP') !== false) $icon = '<i class="bi-whatsapp"></i>';
                    elseif (stripos($sheetName, 'EMAIL') !== false) $icon = '<i class="bi-envelope"></i>';
                    
                    $html .= "<button type='button' id='$btnId' class='btn-sheet $activeClass' onclick=\"changeSheet('$sheetId')\" style='display:flex; align-items:center; gap:6px;'>$icon $sheetName</button>";
                }
                $html .= '</div>';
                
                // CONTENT PANES
                foreach ($sheets as $idx => $sheet) {
                    $displayStyle = ($idx === 0) ? 'block' : 'none';
                    $sheetId = 'sheet-' . $uniqueId . '-' . ($idx + 1);
                    $contentSafe = trim($sheet['content']); // Already HTML or text
                    
                    // Preserve whitespace for text
                    $html .= "<div id='$sheetId' class='sheet-pane' contenteditable='false' style='display:$displayStyle; padding:20px; font-family:\"Inter\", sans-serif; white-space:pre-line;'>";
                    $html .= $contentSafe;
                    $html .= "</div>";
                }
                $html .= '</div>';

                // Add to list
                $finalVersions[] = [
                    'id' => $meta['id'], // Use ID of first item
                    'version_number' => $vCounter++, // Sequential numbering
                    'workflow_stage' => $meta['workflow_stage'],
                    'created_by' => $meta['created_by'],
                    'formatted_date' => $meta['formatted_date'],
                    'content' => $html // The synthesized HTML
                ];
            }

            $content['type'] = 'text';
            $content['versions'] = $finalVersions;
            $content['all_versions'] = $allVersions; // Keep raw for reference if needed
            
            // Keep backward compatibility: Also provide latest version per media in old format
            $latestByMedia = [];
            foreach ($allVersions as $version) {
                $media = $version['media'];
                // Keep only the latest for each media (since ordered by created_at ASC, last one wins)
                $latestByMedia[$media] = $version;
            }
            
            $content['data'] = array_values($latestByMedia);
        }

        return [
            'request' => $request,
            'logs' => $logs,
            'files' => $files,
            'content' => $content
        ];
    }

    public function deletePreviewContent($scriptId) {
        $sql = "DELETE FROM script_preview_content WHERE request_id = ?";
        return sqlsrv_query($this->conn, $sql, [$scriptId]);
    }
}
