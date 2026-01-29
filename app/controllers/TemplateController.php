<?php
namespace App\Controllers;

use App\Core\Controller;

class TemplateController extends Controller {

    public function index() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $templateModel = $this->model('TemplateModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $templates = $templateModel->getAll($startDate, $endDate);
        
        $role = $_SESSION['user']['role_code'] ?? '';
        // Only Procedure can add/delete
        $isProcedure = ($role === 'PROCEDURE');
        
        $this->view('template/index', [
            'templates' => $templates,
            'isProcedure' => $isProcedure,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function upload() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_code'] !== 'PROCEDURE') {
            die("Access Denied");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['template_file'])) {
            $file = $_FILES['template_file'];
            $title = $_POST['title'] ?? $file['name'];
            $targetDir = "uploads/templates/";
            
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $filename = time() . '_' . basename($file['name']);
            $targetPath = $targetDir . $filename;
            
            $allowed = ['xlsx', 'xls', 'docx', 'doc'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                header("Location: ?controller=template&action=index&error=Invalid file type. Only Excel and Word allowed.");
                exit;
            }

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $templateModel = $this->model('TemplateModel');
                $user = $_SESSION['user']['username'] ?? $_SESSION['user']['userid'];
                $result = $templateModel->add($title, $file['name'], $targetPath, $user);
                
                if ($result) {
                    header("Location: ?controller=template&action=index&success=Uploaded");
                } else {
                    // Start Debugging: Return error to user
                    $errors = print_r(sqlsrv_errors(), true); // This won't work as conn is in Model.
                    // Just generic error for now, maybe with title for context.
                    header("Location: ?controller=template&action=index&error=Database Insert Failed using user: $user");
                }
            } else {
                header("Location: ?controller=template&action=index&error=Upload Failed");
            }
        }
    }

    public function delete() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_code'] !== 'PROCEDURE') {
            die("Access Denied");
        }

        $id = $_GET['id'] ?? null;
        if ($id) {
            $model = $this->model('TemplateModel');
            $template = $model->getById($id);
            if ($template) {
                if (file_exists($template['filepath'])) {
                    unlink($template['filepath']);
                }
                $model->delete($id);
            }
        }
        header("Location: ?controller=template&action=index");
    }
}
