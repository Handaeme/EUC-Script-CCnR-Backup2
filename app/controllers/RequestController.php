<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\FileHandler;

class RequestController extends Controller {

    public function index() {
        // My Tasks - Show user's own requests
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }
        
        $user = $_SESSION['user'];
        $reqModel = $this->model('RequestModel');
        
        // Get all requests created by this user
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $requests = $reqModel->getUserRequests($user['userid'], $startDate, $endDate);
        
        $this->view('request/index', [
            'requests' => $requests,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function create() {
        $reqModel = $this->model('RequestModel');
        // Fetch SPVs specifically (Division Head Unit)
        $spvList = $reqModel->getSupervisors(); 
        $this->view('request/create', ['spvList' => $spvList]);
    }

    public function review() {
        if (!isset($_GET['id'])) {
            die("Invalid Request ID");
        }
        $id = $_GET['id'];
        
        $reqModel = $this->model('RequestModel');
        $request = $reqModel->getRequestById($id);
        
        if (!$request) {
            die("Request not found");
        }
        

        // Security Check: Only assigned SPV can review (or Admin)
        // Ignoring strict check for now to ease testing, but should be:
        // if ($request['selected_spv'] != $_SESSION['user']['userid']) die("Unauthorized");

        $content = $reqModel->getPreviewContent($id);
        $files = $reqModel->getFiles($id);
        
        // Fix: Fetch Timeline/Audit Logs for PDF
        $detail = $reqModel->getRequestDetail($id);
        $timeline = $detail['logs'] ?? [];
        
        $this->view('request/review', [
            'request' => $request,
            'content' => $content,
            'files' => $files,
            'timeline' => $timeline
        ]);
    }

    public function saveDraft() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['request_id'])) {
             echo json_encode(['success'=>false, 'error'=>'Missing ID']); return; 
        }

        $reqModel = $this->model('RequestModel');
        
        // Save Updated Content
        if (isset($input['updated_content']) && is_array($input['updated_content'])) {
            foreach ($input['updated_content'] as $contentId => $html) {
                $reqModel->updatePreviewContent($contentId, $html);
            }
        }
        
        
        // Mark as Draft
        $reqModel->setDraftStatus($input['request_id'], 1);
        
        echo json_encode(['success'=>true]);
    }

    public function approve() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['request_id'])) {
             echo json_encode(['success'=>false, 'error'=>'Missing ID']); return; 
        }

        $reqModel = $this->model('RequestModel');
        $req = $reqModel->getRequestById($input['request_id']);
        
        // Save Updated Content if exists
        if (isset($input['updated_content']) && is_array($input['updated_content'])) {
            foreach ($input['updated_content'] as $contentId => $html) {
                $reqModel->updatePreviewContent($contentId, $html);
            }
        }

        // Determine Next Status & Role based on Current Role
        $currentRole = $_SESSION['user']['role_code'];
        $nextStatus = '';
        $nextRole = '';
        $auditAction = '';
        $auditDetails = '';

        if ($currentRole === 'SPV') {
            $nextStatus = 'APPROVED_SPV';
            $nextRole = 'PIC';
            $auditAction = 'APPROVE_SPV';
            $auditDetails = 'Approved by ' . ($_SESSION['user']['userid'] ?? 'Supervisor');
        } 
        elseif ($currentRole === 'PIC') {
             $nextStatus = 'APPROVED_PIC';
             $nextRole = 'PROCEDURE';
             $auditAction = 'APPROVE_PIC';
             $auditDetails = 'Approved by ' . ($_SESSION['user']['userid'] ?? 'PIC');
        }
        elseif ($currentRole === 'PROCEDURE') {
             $nextStatus = 'LIBRARY'; // Or FINAL
             $nextRole = 'LIBRARY';   // Finished
             $auditAction = 'APPROVE_PROCEDURE';
             $auditDetails = 'Published to Library by ' . ($_SESSION['user']['userid'] ?? 'Procedure');
             
             // Finalize to Library
             if (!$reqModel->finalizeLibrary($req['id'])) {
                 echo json_encode(['success'=>false, 'error'=>'Failed to publish to Library']);
                 return;
             }
        }
        else {
             echo json_encode(['success'=>false, 'error'=>'Unauthorized Role']);
             return;
        }

        // Update Status
        $reqModel->updateStatus($req['id'], $nextStatus, $nextRole, $_SESSION['user']['userid']);
        $reqModel->setDraftStatus($req['id'], 0); // Clear draft flag
        $reqModel->logAudit($req['id'], $req['script_number'], $auditAction, $currentRole, $_SESSION['user']['userid'], $auditDetails);

        echo json_encode(['success'=>true]);
    }

    public function reject() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        
        $reqModel = $this->model('RequestModel');
        $req = $reqModel->getRequestById($input['request_id']);
        
        // Save Updated Content if exists (even on Reject/Revise)
        if (isset($input['updated_content']) && is_array($input['updated_content'])) {
            foreach ($input['updated_content'] as $contentId => $html) {
                $reqModel->updatePreviewContent($contentId, $html);
            }
        }
        
        $status = ($input['decision'] === 'REJECT') ? 'REJECTED' : 'REVISION';
        $remarks = isset($input['remarks']) ? $input['remarks'] : '';

        // Return to Maker
        $currentRole = $_SESSION['user']['role_code'];
        $reqModel->updateStatus($req['id'], $status, 'Maker', $_SESSION['user']['userid']);
        $reqModel->setDraftStatus($req['id'], 0); // Clear draft flag
        $reqModel->logAudit($req['id'], $req['script_number'], $status, $currentRole, $_SESSION['user']['userid'], $remarks);

        echo json_encode(['success'=>true]);
    }

    public function revise() {
        $this->reject();
    }

    public function upload() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            return;
        }

        if (!isset($_FILES['file'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            return;
        }

        $file = $_FILES['file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetDir = '../storage/uploads/';
        
        // Ensure dir exists
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $filename = uniqid() . '_' . basename($file['name']);
        $targetPath = $targetDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Parse File
            $parseResult = FileHandler::parseFile($targetPath, $ext);
            
            // Handle both old (string) and new (array) formats
            $previewHtml = is_array($parseResult) ? $parseResult['preview_html'] : $parseResult;
            
            echo json_encode([
                'success' => true,
                'filepath' => $targetPath,
                'preview' => $previewHtml
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
        }
    }

    // Handle final submission (saving everything)
    public function store() {
        header('Content-Type: application/json');
        
        try {
            // Read FormData from POST (not JSON)
            $input = $_POST;
            
            // Handle file upload if present
            if (isset($_FILES['script_file'])) {
                // File upload mode - process file first
                $file = $_FILES['script_file'];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $targetDir = '../storage/uploads/';
                
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                
                $filename = uniqid() . '_' . basename($file['name']);
                $targetPath = $targetDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $input['filepath'] = $targetPath;
                    $input['filename'] = $filename;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to upload file']);
                    return;
                }
            }
            
            // Decode script_content if it's JSON string
            if (isset($input['script_content']) && is_string($input['script_content'])) {
                $decoded = json_decode($input['script_content'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input['script_content'] = $decoded;
                }
            }
            
            if (empty($input)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid data - no input received']);
                return;
            }
            
            // Start Session if not started
            if (session_status() == PHP_SESSION_NONE) session_start();
            $user = $_SESSION['user']['userid'] ?? 'maker01';

            $reqModel = $this->model('RequestModel');

            // VALDIATION: SPV SELECT
            if (empty($input['selected_spv'])) {
                echo json_encode(['status' => 'error', 'message' => 'Please select an SPV/Supervisor!']);
                return;
            }

            // 1. Create Request
            $requestData = [
                'title' => $input['title'],
                'jenis' => $input['jenis'],
                'produk' => $input['produk'],
                'kategori' => $input['kategori'],
                'media' => $input['media'],
                'mode' => $input['input_mode'], // Frontend sends 'input_mode', DB expects 'mode'
                'creator_id' => $user,
                'selected_spv' => $input['selected_spv']
            ];
            
            $reqResult = $reqModel->createRequest($requestData);

            if (!$reqResult || isset($reqResult['error'])) {
                $errMsg = isset($reqResult['error']) ? $reqResult['error'] : 'Create Request Failed';
                echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $errMsg]);
                return;
            }
            
            $scriptId = $reqResult['id'];
            $scriptNumber = $reqResult['number'];
            $ticketId = $reqResult['ticket_id'] ?? $scriptNumber;

            // 2. Save Preview Content
            // Logic: Content might be a single string (from File Upload) or an Object/Array (from Free Input Tabs)
            
            $mediaList = explode(',', $input['media']); // "WA, SMS" -> ["WA", " SMS"]
            $contentData = $input['script_content'] ?? null; // Changed from 'content' to 'script_content'

            if (is_array($contentData)) {
                // Free Input with specific content per media
                foreach ($contentData as $item) {
                    $sheetName = $item['sheet_name'] ?? 'unknown';
                    $text = $item['content'] ?? '';
                    $reqModel->savePreviewContent($scriptId, trim($sheetName), $text, $user);
                }
            } else if ($contentData) {
                // Single content (File Upload) - Check if we need to parse for multiple sheets
                // Re-parse the file to get individual sheets
                if ($input['input_mode'] === 'FILE_UPLOAD' && !empty($input['filepath'])) {
                    $ext = pathinfo($input['filepath'], PATHINFO_EXTENSION);
                    $parseResult = FileHandler::parseFile($input['filepath'], $ext);
                    
                    // DEBUG: Log parse result structure
                    error_log("MULTI-SHEET DEBUG - Parse Result Type: " . gettype($parseResult));
                    if (is_array($parseResult)) {
                        error_log("MULTI-SHEET DEBUG - Has 'sheets' key: " . (isset($parseResult['sheets']) ? 'YES' : 'NO'));
                        if (isset($parseResult['sheets'])) {
                            error_log("MULTI-SHEET DEBUG - Sheets count: " . count($parseResult['sheets']));
                            error_log("MULTI-SHEET DEBUG - Sheets data: " . json_encode($parseResult['sheets']));
                        }
                    }
                    
                    // If parseResult has 'sheets' array, save each sheet separately
                    if (is_array($parseResult) && isset($parseResult['sheets']) && count($parseResult['sheets']) > 0) {
                        error_log("MULTI-SHEET DEBUG - Saving " . count($parseResult['sheets']) . " sheets separately");
                        foreach ($parseResult['sheets'] as $sheet) {
                            $sheetName = $sheet['name'] ?? 'Sheet';
                            $sheetContent = $sheet['content'] ?? '';
                            error_log("MULTI-SHEET DEBUG - Saving sheet: " . $sheetName);
                            $reqModel->savePreviewContent($scriptId, trim($sheetName), $sheetContent, $user);
                        }
                    } else {
                        // Fallback: Old format or single sheet
                        error_log("MULTI-SHEET DEBUG - Fallback: Saving to media list (count: " . count($mediaList) . ")");
                        foreach ($mediaList as $mediaName) {
                            $reqModel->savePreviewContent($scriptId, trim($mediaName), $contentData, $user);
                        }
                    }
                } else {
                    // Not file upload, save to each media
                    foreach ($mediaList as $mediaName) {
                        $reqModel->savePreviewContent($scriptId, trim($mediaName), $contentData, $user);
                    }
                }
            }

            // 3. Save File Info (If Upload Mode)
            if ($input['input_mode'] === 'FILE_UPLOAD' && !empty($input['filepath'])) {
                $reqModel->saveFileInfo($scriptId, 'TEMPLATE', basename($input['filepath']), $input['filepath'], $user);
            }

            // 4. Audit Trail
            $reqModel->logAudit($scriptId, $scriptNumber, 'SUBMIT_REQUEST', 'Maker', $user, 'Submitted by ' . ($_SESSION['user']['userid'] ?? 'Maker'));

            echo json_encode(['status' => 'success', 'ticket_id' => $ticketId, 'script_number' => $scriptNumber]);
            
        } catch (Exception $e) {
            // Catch any PHP errors and return JSON error instead of HTML
            echo json_encode([
                'status' => 'error', 
                'message' => 'Server Error: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
    
    public function edit() {
        if (!isset($_GET['id'])) die("Invalid ID");
        $id = $_GET['id'];
        
        $reqModel = $this->model('RequestModel');
        $request = $reqModel->getRequestById($id);
        
        // Security: Ensure only Creator can edit, AND status is REVISION (or REJECTED)
        // Ignoring strict check for demo speed, but logic is:
        // if ($request['created_by'] != $_SESSION['user']['userid']) die("Unauthorized");
        
        $content = $reqModel->getPreviewContent($id);
        $spvList = $reqModel->getSupervisors();
        $rejectionNote = $reqModel->getRejectionReason($id);
        $draftNote = $reqModel->getLatestDraftNote($id);

        $this->view('request/edit', [
            'request' => $request,
            'content' => $content,
            'spvList' => $spvList,
            'rejectionNote' => $rejectionNote,
            'draftNote' => $draftNote
        ]);
    }

    public function update() {
        // Handle Re-Submission
        // Clear any previous output buffers to prevent JSON corruption
        if (ob_get_length()) ob_end_clean();
        ob_start();

        header('Content-Type: application/json');
        
        try {
            // Read FormData
            $input = $_POST;
            
            // Handle JSON Input
            if (empty($input)) {
                $raw = file_get_contents('php://input');
                $input = json_decode($raw, true);
            }
        
        if (isset($input['script_content']) && is_string($input['script_content'])) {
            $decoded = json_decode($input['script_content'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input['content'] = $decoded;
            } else {
                $input['content'] = $input['script_content']; // Raw HTML string
            }
        }
        
        if (!$input) {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
            return;
        }

        $reqModel = $this->model('RequestModel');
        $id = $input['request_id'];
        $user = $_SESSION['user']['userid'] ?? 'maker01';

        // 2. Handle File Upload if present in update
        if (isset($_FILES['script_file'])) {
             $file = $_FILES['script_file'];
             $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
             $targetDir = '../storage/uploads/';
             if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
             $filename = uniqid() . '_' . basename($file['name']);
             $targetPath = $targetDir . $filename;
             if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                 $reqModel->saveFileInfo($id, 'TEMPLATE', basename($targetPath), $targetPath, $user);
             }
        }
        
        // 3. Update Content
        if (isset($input['content'])) {
             $contentData = $input['content'];
             $reqModel->deletePreviewContent($id);
             
             if (is_array($contentData)) {
                 // Free Input
                 foreach ($contentData as $item) {
                     $sheetName = $item['sheet_name'] ?? 'unknown';
                     $text = $item['content'] ?? '';
                     $reqModel->savePreviewContent($id, trim($sheetName), $text, $user);
                 }
             } else if (!empty($contentData)) {
                 // File Upload (Fallback)
                 // If content is still string (Legacy or Single Sheet Word), save as "Document Preview"
                 // Do NOT use "Formatted Script" to avoid confusion
                 $reqModel->savePreviewContent($id, 'Document Preview', $contentData, $user);
             }
        }
        
        // 3b. Update Metadata (Checklists) - FIX for Persistence
        $reqModel->updateRequestMetadata($id, $input);

        // 4. Update Status (Only if NOT Draft)
        $msg = "Re-submitted by " . ($_SESSION['user']['userid'] ?? 'Maker');
        $action = "RESUBMIT";

        if (isset($input['is_draft']) && $input['is_draft'] == '1') {
             // DRAFT MODE: Do not change status, just save content
             $msg = "Draft saved by " . ($_SESSION['user']['userid'] ?? 'Maker');
             $action = "DRAFT_SAVED";
             $reqModel->setDraftStatus($id, 1);
        } else {
             // SUBMIT MODE: Forward to SPV
             $reqModel->updateStatus($id, 'CREATED', 'SPV', $user);
             $reqModel->setDraftStatus($id, 0); // Clear draft flag
        }
        
        $logDetails = $msg;
        if (!empty($input['maker_note'])) {
            $logDetails .= '. Note: ' . $input['maker_note'];
        }

        $reqModel->logAudit($id, $input['script_number'], $action, 'Maker', $user, $logDetails);
        
        ob_clean(); // Ensure clean JSON
        echo json_encode(['success' => true]);
        
        } catch (Exception $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function uploadReviewDoc() {
        // Clear any previous output/warnings to ensure valid JSON
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/json');
        
        try {
            if (!isset($_SESSION['user'])) {
                throw new \Exception('Not authenticated');
            }
            
            if (!isset($_FILES['file']) || !isset($_POST['doc_type']) || !isset($_POST['request_id'])) {
                throw new \Exception('Missing parameters');
            }
            
            $file = $_FILES['file'];
            $docType = $_POST['doc_type']; // LEGAL, CX, LEGAL_SYARIAH, LPP
            $requestId = $_POST['request_id'];
            
            // Validate file type
            // $allowedExts = ['pdf', 'doc', 'docx', 'msg', 'eml'];
            // $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // if (!in_array($ext, $allowedExts)) {
            //     throw new \Exception('Invalid file type. Only PDF, DOC, DOCX, MSG, EML allowed.');
            // }

            // Allow any extension (User Request)
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Create upload directory
            $uploadDir = 'uploads/review_docs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $filename = $requestId . '_' . $docType . '_' . time() . '.' . $ext;
            $filepath = $uploadDir . $filename;
            
            // Move file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new \Exception('Failed to save file to disk');
            }
            
            // Save to database
            $reqModel = $this->model('RequestModel');
            $user = $_SESSION['user']['userid'];
            
            $newFileId = $reqModel->saveFileInfo($requestId, $docType, $file['name'], $filepath, $user);
            
            if ($newFileId) {
                echo json_encode(['success' => true, 'filename' => $file['name'], 'path' => $filepath, 'id' => $newFileId]);
            } else {
                // If DB save fails, maybe delete the file?
                unlink($filepath);
                throw new \Exception('Failed to save to database (SQL Error)');
            }
            
        } catch (\Exception $e) {
            // Log the full error for server admin
            error_log("Upload Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function deleteReviewDoc() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['file_id'])) {
            echo json_encode(['success' => false, 'error' => 'Missing File ID']);
            return;
        }
        
        $fileId = $input['file_id'];
        $reqModel = $this->model('RequestModel');
        
        // Security: Check file exists
        $file = $reqModel->getFileById($fileId);
        if (!$file) {
            echo json_encode(['success' => false, 'error' => 'File not found']);
            return;
        }
        
        // Optional: Check permission (e.g., only Uploader or Admin)
        // if ($file['uploaded_by'] != $_SESSION['user']['userid']) ...

        // Delete Physical File
        if (file_exists($file['filepath'])) {
            unlink($file['filepath']);
        }
        
        // Delete DB Record
        if ($reqModel->deleteReviewDoc($fileId)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database delete failed']);
        }
    }

    public function viewLibrary() {
        if (!isset($_GET['id'])) {
            die("Invalid Script ID");
        }
        
        $scriptId = $_GET['id'];
        $reqModel = $this->model('RequestModel');
        
        // Get Full Request Details (Metadata + Logs + Content + Files)
        $detail = $reqModel->getRequestDetail($scriptId);
        
        if (!$detail || !$detail['request']) {
             die("Script not found");
        }

        $request = $detail['request'];
        $logs = $detail['logs'] ?? [];
        
        // Extract specific components for the view
        // Note: getRequestDetail already structures 'content' and 'files'
        // But the View expects specific variables, so we'll adapt slightly
        
        // Content: View expects raw list of rows for preview
        $content = $reqModel->getPreviewContent($scriptId);
        
        // Review Docs
        $reviewDocs = $reqModel->getReviewDocuments($scriptId);
        
        // Original Script File
        $scriptFile = $reqModel->getScriptFile($scriptId);
        
        $this->view('library/detail', [
            'request' => $request,
            'content' => $content,
            'reviewDocs' => $reviewDocs,
            'scriptFile' => $scriptFile,
            'logs' => $logs // Pass logs to view
        ]);
    }

    public function history() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }
        $userId = $_SESSION['user']['userid'];
        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $history = $reqModel->getUserApprovalHistory($userId, $startDate, $endDate);
        
        $this->view('request/history', [
            'history' => $history,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
}
