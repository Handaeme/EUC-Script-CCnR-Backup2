<?php
namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller {

    public function index() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $role = $_SESSION['user']['role_code'] ?? '';
        
        // Route based on Role Code (MAKER, SPV, PIC, PROCEDURE)
        switch ($role) {
            case 'SPV':
                $this->spv();
                break;
            case 'PIC':
                // Placeholder for PIC Dashboard
                $this->pic();
                break;
            case 'PROCEDURE':
                // Placeholder for Procedure Dashboard
                $this->procedure();
                break;
            case 'MAKER':
            default:
                $this->maker();
                break;
        }
    }

    public function maker() {
        $user = $_SESSION['user'];
        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        // Fetch revisions
        $revisions = $reqModel->getPendingRequests($user['userid'], 'MAKER', $startDate, $endDate);
        
        // Fetch Stats
        $stats = $reqModel->getMakerStats($user['userid']);
        
        $this->view('dashboard/maker', [
            'revisions' => $revisions,
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function spv() {
        $user = $_SESSION['user'];
        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $pendingRequests = $reqModel->getPendingRequests($user['userid'], 'SPV', $startDate, $endDate);
        
        $this->view('dashboard/approval', [
            'pendingRequests' => $pendingRequests,
            'pageTitle' => 'Supervisor Dashboard',
            'pageDesc' => 'Review and approve new script requests.',
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function pic() {
        $user = $_SESSION['user'];
        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $pendingRequests = $reqModel->getPendingRequests($user['userid'], 'PIC', $startDate, $endDate); // View All APPROVED_SPV
        
        $this->view('dashboard/approval', [
            'pendingRequests' => $pendingRequests,
            'pageTitle' => 'PIC Dashboard',
            'pageDesc' => 'Quality check for approved scripts.',
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function procedure() {
        $user = $_SESSION['user'];
        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $pendingRequests = $reqModel->getPendingRequests($user['userid'], 'PROCEDURE', $startDate, $endDate); // View All APPROVED_PIC
        
        $this->view('dashboard/approval', [
            'pendingRequests' => $pendingRequests,
            'pageTitle' => 'Procedure Dashboard',
            'pageDesc' => 'Final review and library publication.',
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function library() {
        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $sortPublished = $_GET['sort_published'] ?? 'DESC'; // Default Newest First
        
        // Validate Sort
        if (!in_array(strtoupper($sortPublished), ['ASC', 'DESC'])) {
            $sortPublished = 'DESC';
        }
        
        $libraryItems = $reqModel->getLibraryItemsWithContent($startDate, $endDate, $sortPublished);
        
        $this->view('dashboard/library', [
            'libraryItems' => $libraryItems,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sortPublished' => $sortPublished
        ]);
    }
    
    public function exportLibrary() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $libraryItems = $reqModel->getLibraryItemsWithContent($startDate, $endDate);
        
        // Filename
        $filename = "Script_Library_Export_" . date('Ymd_His') . ".xls";
        
        // Headers for HTML-Excel
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Start HTML Output
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<!--[if gte mso 9]><xml>';
        echo '<x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Script Library</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook>';
        echo '</xml><![endif]-->';
        echo '<style>';
        echo 'body, table { font-family: Arial, sans-serif; font-size: 11pt; }';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th, td { border: 1px solid #000000; padding: 8px; vertical-align: top; white-space: nowrap; }'; // Added nowrap and increased padding
        echo 'th { background-color: #f2f2f2; font-weight: bold; text-align: left; }';
        echo '.text { mso-number-format:"\@"; }'; // Force text format
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Ticket ID</th>';
        echo '<th>Script Number</th>';
        echo '<th>Title</th>';
        echo '<th>Type (Jenis)</th>';
        echo '<th>Product</th>';
        echo '<th>Category</th>';
        echo '<th>Media Channel</th>';
        echo '<th>Mode</th>';
        echo '<th>Filename</th>';
        echo '<th>Status</th>';
        echo '<th>Created Date</th>';
        echo '<th>Published Date</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($libraryItems as $item) {
            // Published Date (created_at in library table)
            $publishedDateRaw = $item['created_at'] ?? null;
            if ($publishedDateRaw instanceof \DateTime) {
                $publishedDate = $publishedDateRaw->format('Y-m-d H:i');
            } elseif (is_string($publishedDateRaw) && !empty($publishedDateRaw)) {
                $publishedDate = date('Y-m-d H:i', strtotime($publishedDateRaw));
            } else {
                $publishedDate = '-';
            }

            // Original Request Date
            $createdDateRaw = $item['request_created_at'] ?? null;
            if ($createdDateRaw instanceof \DateTime) {
                $createdDate = $createdDateRaw->format('Y-m-d H:i');
            } elseif (is_string($createdDateRaw) && !empty($createdDateRaw)) {
                $createdDate = date('Y-m-d H:i', strtotime($createdDateRaw));
            } else {
                $createdDate = '-';
            }
                
            $filenameVal = ($item['mode'] === 'FILE_UPLOAD') ? ($item['filename'] ?? '-') : '-';
            
            $tId = $item['ticket_id'] ?? '-';
            if (is_numeric($tId)) $tId = sprintf("SC-%04d", $tId);

            echo '<tr>';
            echo '<td class="text">' . htmlspecialchars($tId) . '</td>';
            echo '<td class="text">' . htmlspecialchars($item['script_number'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['title'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['jenis'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['produk'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['kategori'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['media'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['mode'] ?? 'Free Input') . '</td>';
            echo '<td>' . htmlspecialchars($filenameVal) . '</td>';
            echo '<td>LIBRARY</td>';
            echo '<td>' . htmlspecialchars($createdDate) . '</td>';
            echo '<td>' . htmlspecialchars($publishedDate) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit;
    }
}
