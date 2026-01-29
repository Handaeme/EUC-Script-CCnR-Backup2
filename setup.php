<?php
// SETUP.PHP - AUTOMATED INSTALLER FOR CITRA

// 1. Directory Structure
$directories = [
    'app/controllers',
    'app/models',
    'app/views',
    'app/helpers',
    'config',
    'public',
    'storage/uploads',
    'storage/templates',
    'logs'
];

echo "<h1>CITRA System Setup</h1>";
echo "<pre>";

foreach ($directories as $dir) {
    if (!file_exists(__DIR__ . '/' . $dir)) {
        if (mkdir(__DIR__ . '/' . $dir, 0777, true)) {
            echo "[OK] Created directory: $dir\n";
        } else {
            echo "[ERROR] Failed to create directory: $dir. Check permissions.\n";
            die();
        }
    } else {
        echo "[SKIP] Directory exists: $dir\n";
    }
}

// 2. Database Connection Check
$configFile = __DIR__ . '/config/database.php';
if (!file_exists($configFile)) {
    die("[ERROR] config/database.php not found. Please create it first.\n");
}

$config = require $configFile;
$host = $config['host'];
$dbName = $config['dbname'];

// Connection info specifically for Master to check/create DB
$connectionInfoMaster = [
    "Database" => "master",
    "CharacterSet" => "UTF-8"
];
if (!empty($config['user'])) {
    $connectionInfoMaster['UID'] = $config['user'];
    $connectionInfoMaster['PWD'] = $config['pass'];
}

echo "\nConnecting to SQL Server ($host)...\n";
$conn = sqlsrv_connect($host, $connectionInfoMaster);

if ($conn === false) {
    echo "[ERROR] Could not connect to SQL Server.\n";
    echo print_r(sqlsrv_errors(), true);
    die();
}
echo "[OK] Connected to SQL Server.\n";

// 3. Create Database if not exists
$sql = "SELECT name FROM sys.databases WHERE name = ?";
$params = [$dbName];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) die(print_r(sqlsrv_errors(), true));

if (sqlsrv_has_rows($stmt) === false) {
    $createSql = "CREATE DATABASE [$dbName]";
    $createStmt = sqlsrv_query($conn, $createSql);
    if ($createStmt === false) die(print_r(sqlsrv_errors(), true));
    echo "[OK] Database '$dbName' created.\n";
} else {
    echo "[SKIP] Database '$dbName' already exists.\n";
}

// Close Master connection and connect to CITRA
sqlsrv_close($conn);

$connectionInfoApp = $config['options']; // Derived from config
// Ensure we connect to the right DB now
$connectionInfoApp['Database'] = $dbName;
if (!empty($config['user'])) {
    $connectionInfoApp['UID'] = $config['user'];
    $connectionInfoApp['PWD'] = $config['pass'];
}

$conn = sqlsrv_connect($host, $connectionInfoApp);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
echo "[OK] Connected to database '$dbName'.\n";

// 4. Schema Migration (Create Tables)
function executeQuery($conn, $sql, $msg) {
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        echo "[ERROR] $msg: " . print_r(sqlsrv_errors(), true) . "\n";
    } else {
        echo "[OK] $msg\n";
    }
}

// Table: script_users
$sqlUsers = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='script_users' AND xtype='U')
CREATE TABLE script_users (
    username VARCHAR(50) PRIMARY KEY,
    fullname VARCHAR(100),
    password VARCHAR(255) DEFAULT '123',
    role_code VARCHAR(20), -- NEW: MAKER, SPV, PIC, PROCEDURE, LIBRARY
    job_function VARCHAR(50), 
    group_name VARCHAR(50),
    created_date DATETIME DEFAULT GETDATE(),
    is_active INT DEFAULT 1
)";
executeQuery($conn, $sqlUsers, "Table script_users check/create");

// Table: script_request
$sqlRequest = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='script_request' AND xtype='U')
CREATE TABLE script_request (
    id INT IDENTITY(1,1) PRIMARY KEY,
    ticket_id INT, -- NEW: Ticket ID (SC-XXXX)
    script_number VARCHAR(100),
    title VARCHAR(255),
    jenis VARCHAR(50), -- Konvensional / Syariah
    produk VARCHAR(50),
    kategori VARCHAR(50),
    media VARCHAR(50),
    mode VARCHAR(20), -- FREE_INPUT / FILE_UPLOAD
    status VARCHAR(50), -- DRAFT, CREATED, WIP, REVISED, REJECTED, APPROVED_SPV, PIC_REVIEW, PROCEDURE, LIBRARY
    current_role VARCHAR(50),
    version INT DEFAULT 1,
    is_active INT DEFAULT 1,
    is_deleted INT DEFAULT 0,
    created_by VARCHAR(50),
    selected_spv VARCHAR(50),
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME
)";
executeQuery($conn, $sqlRequest, "Table script_request check/create");

// MIGRATION: Add ticket_id if not exists
$checkCol = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request' AND COLUMN_NAME = 'ticket_id'";
$checkStmt = sqlsrv_query($conn, $checkCol);
if ($checkStmt && !sqlsrv_has_rows($checkStmt)) {
    $alterSql = "ALTER TABLE script_request ADD ticket_id INT";
    executeQuery($conn, $alterSql, "Added column 'ticket_id' to script_request");
}

// Table: script_preview_content
$sqlPreview = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='script_preview_content' AND xtype='U')
CREATE TABLE script_preview_content (
    id INT IDENTITY(1,1) PRIMARY KEY,
    request_id INT, -- Refers to script_request.id
    media VARCHAR(50),
    content NVARCHAR(MAX), -- HTML Table / Text
    updated_by VARCHAR(50),
    updated_at DATETIME DEFAULT GETDATE()
)";
executeQuery($conn, $sqlPreview, "Table script_preview_content check/create");

// Table: script_files
$sqlFiles = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='script_files' AND xtype='U')
CREATE TABLE script_files (
    id INT IDENTITY(1,1) PRIMARY KEY,
    request_id INT,
    file_type VARCHAR(20), -- TEMPLATE, LEGAL, CX, FINAL
    original_filename VARCHAR(255),
    filepath VARCHAR(255),
    uploaded_by VARCHAR(50),
    uploaded_at DATETIME DEFAULT GETDATE()
)";
executeQuery($conn, $sqlFiles, "Table script_files check/create");

// Table: script_audit_trail
$sqlAudit = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='script_audit_trail' AND xtype='U')
CREATE TABLE script_audit_trail (
    id INT IDENTITY(1,1) PRIMARY KEY,
    request_id INT,
    script_number VARCHAR(100),
    action VARCHAR(50),
    status_before VARCHAR(50),
    status_after VARCHAR(50),
    user_role VARCHAR(50),
    user_id VARCHAR(50),
    details NVARCHAR(MAX),
    created_at DATETIME DEFAULT GETDATE()
)";
executeQuery($conn, $sqlAudit, "Table script_audit_trail check/create");

// Table: script_library
$sqlLibrary = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='script_library' AND xtype='U')
CREATE TABLE script_library (
    id INT IDENTITY(1,1) PRIMARY KEY,
    request_id INT,
    script_number VARCHAR(100),
    media VARCHAR(50),
    content NVARCHAR(MAX),
    version INT,
    created_at DATETIME DEFAULT GETDATE()
)";
executeQuery($conn, $sqlLibrary, "Table script_library check/create");

// Table: script_templates
$sqlTemplates = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='script_templates' AND xtype='U')
CREATE TABLE script_templates (
    id INT IDENTITY(1,1) PRIMARY KEY,
    title VARCHAR(100),
    filename VARCHAR(255),
    filepath VARCHAR(255),
    uploaded_by VARCHAR(50),
    created_at DATETIME DEFAULT GETDATE()
)";
executeQuery($conn, $sqlTemplates, "Table script_templates check/create");


// 5. Seed Users (If empty)
$checkUsers = sqlsrv_query($conn, "SELECT COUNT(*) as count FROM script_users");
$row = sqlsrv_fetch_array($checkUsers);

if ($row['count'] == 0) {
    echo "\n[INFO] Seeding default users...\n";
    $users = [
        ['maker01', 'Budi Maker', 'Maker', 'Department Head'],
        ['spv01', 'Siti SPV', 'Checker', 'Division Head'],
        ['pic01', 'Andi PIC', 'Checker', 'Coordinator Script'],
        ['proc01', 'Dewi Procedure', 'Approval', 'CPMS'], // Mapped Procedure -> Approval
        ['admin', 'Administrator', 'Approval', 'IT']
    ];

    foreach ($users as $u) {
        $sql = "INSERT INTO script_users (username, fullname, password, job_function, group_name) VALUES (?, ?, '123', ?, ?)";
        $params = [$u[0], $u[1], $u[3], $u[2]]; // job_function is index 2 (Maker/Checker...), group_name index 3
        
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt) echo " - Created user: {$u[0]} ({$u[2]})\n";
        else echo " - Failed user {$u[0]}\n";
    }
} else {
    echo "[SKIP] Users already exist.\n";
}

echo "\n<h1>SETUP COMPLETED SUCCESSFULLY!</h1>";
echo "<p><a href='index.php'>Go to Login</a></p>";
echo "</pre>";
?>
