<?php
namespace App\Controllers;

use App\Core\Controller;

class AuditController extends Controller {

    public function index() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $reqModel = $this->model('RequestModel');
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $logs = $reqModel->getAuditExportData($startDate, $endDate);
        
        $this->view('audit/index', [
            'logs' => $logs,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function export() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $reqModel = $this->model('RequestModel');
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $data = $reqModel->getAuditExportData($startDate, $endDate);
        
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="Audit_Trail_' . date('Y-m-d_His') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Generate Excel (HTML table format)
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        echo '<table border="1">';
        echo '<thead>';
        echo '<tr style="background-color: #d32f2f; color: white; font-weight: bold;">';
        echo '<th>No</th>';
        echo '<th>Ticket ID</th>';
        echo '<th>Nomor Script</th>';
        echo '<th>Jenis</th>';
        echo '<th>Produk</th>';
        echo '<th>Kategori</th>';
        echo '<th>Status</th>';
        echo '<th>Channel</th>';
        echo '<th>Script Content / Filename</th>';
        echo '<th>Created Date</th>';
        echo '<th>Maker</th>';
        echo '<th>SPV</th>';
        echo '<th>Status SPV</th>';
        echo '<th>Timestamp SPV</th>';
        echo '<th>PIC</th>';
        echo '<th>Status PIC</th>';
        echo '<th>Timestamp PIC</th>';
        echo '<th>Status Procedure</th>';
        echo '<th>Timestamp Procedure</th>';
        echo '<th>Legal Review</th>';
        echo '<th>CX Review</th>';
        echo '<th>Legal Syariah</th>';
        echo '<th>LPP</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $no = 1;
        foreach ($data as $row) {
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            // Format Ticket ID
            $tID = $row['ticket_id'];
            if(is_numeric($tID)) $tID = sprintf("SC-%04d", $tID);
            echo '<td>' . htmlspecialchars($tID) . '</td>';
            echo '<td>' . htmlspecialchars($row['script_number']) . '</td>';
            echo '<td>' . htmlspecialchars($row['jenis']) . '</td>';
            echo '<td>' . htmlspecialchars($row['produk']) . '</td>';
            echo '<td>' . htmlspecialchars($row['kategori']) . '</td>';
            echo '<td>' . htmlspecialchars($row['status']) . '</td>';
            echo '<td>' . htmlspecialchars($row['channel']) . '</td>';
            echo '<td style="white-space: pre-wrap;">' . htmlspecialchars($row['script_content']) . '</td>';
            echo '<td>' . htmlspecialchars($row['created_date']) . '</td>';
            echo '<td>' . htmlspecialchars($row['maker']) . '</td>';
            echo '<td>' . htmlspecialchars($row['spv']) . '</td>';
            echo '<td>' . htmlspecialchars($row['status_spv']) . '</td>';
            echo '<td>' . htmlspecialchars($row['timestamp_spv']) . '</td>';
            echo '<td>' . htmlspecialchars($row['pic']) . '</td>';
            echo '<td>' . htmlspecialchars($row['status_pic']) . '</td>';
            echo '<td>' . htmlspecialchars($row['timestamp_pic']) . '</td>';
            echo '<td>' . htmlspecialchars($row['status_procedure']) . '</td>';
            echo '<td>' . htmlspecialchars($row['timestamp_procedure']) . '</td>';
            echo '<td>' . htmlspecialchars($row['legal_review']) . '</td>';
            echo '<td>' . htmlspecialchars($row['cx_review']) . '</td>';
            echo '<td>' . htmlspecialchars($row['legal_syariah']) . '</td>';
            echo '<td>' . htmlspecialchars($row['lpp']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit;
    }

    public function detail() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: ?controller=audit");
            exit;
        }

        $reqModel = $this->model('RequestModel');
        $detailFn = 'getRequestDetail'; // Just to be safe with method existence check if needed, but direct call is fine.
        
        $data = $reqModel->getRequestDetail($id);

        if (!$data) {
            // Handle not found
            echo "Request not found.";
            exit;
        }

        $this->view('audit/detail', $data);
    }
}
