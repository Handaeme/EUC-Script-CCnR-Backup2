<?php
session_start();
require_once 'config/database.php';

// Simple Router / Dispatcher placeholder
// For now, if no session, show Login.
// If session, show Dashboard (which matches "Header merah... Sidebar abu muda").

$request = $_SERVER['REQUEST_URI'];

// Basic Install Check
if (!file_exists(__DIR__ . '/config/database.php')) {
    header("Location: setup.php");
    exit;
}

// Basic Install Check
if (!file_exists(__DIR__ . '/config/database.php')) {
    header("Location: setup.php");
    exit;
}

// NATIVE AUTOLOADER (No Composer)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Logic: Check DB Connection
$config = require __DIR__ . '/config/database.php';
// We suppress errors here to avoid leaking sensitive info, or show friendly error
$conn = sqlsrv_connect($config['host'], isset($config['options']) ? $config['options'] : []);
if (!$conn) {
    echo "<h1>Database Connection Failed</h1>";
    echo "<p>Please ensure SQL Server is running and config/database.php is correct.</p>";
    die();
}

// Router
$controllerName = isset($_GET['controller']) ? $_GET['controller'] : 'Home';
$methodName = isset($_GET['action']) ? $_GET['action'] : 'index';

// Normalize Controller Name (e.g. 'request' -> 'RequestController')
$controllerClass = "App\\Controllers\\" . ucfirst($controllerName) . "Controller";

// If Controller doesn't exist, fall back to default logic (Dashboard in this file)
// BUT, if we want to move Dashboard to a controller, we should do that.
// For now, let's keep the existing "Dashboard" as the fallback if 'Home' controller is requested but not found (yet).

if (class_exists($controllerClass)) {
    $controller = new $controllerClass();
    if (method_exists($controller, $methodName)) {
        $controller->$methodName();
        exit; // Stop execution here, don't show the dashboard below
    } else {
        die("Method $methodName not found in $controllerClass");
    }
}

// --- FALLBACK DASHBOARD (Existing Code) ---


// Logout Logic
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $userid = $_POST['userid']; // Form still sends 'userid' name
    $password = $_POST['password']; 
    
    // ADAPTED TO EXISTING SCHEMA: use 'username' column
    $sql = "SELECT * FROM script_users WHERE username = ? AND password = ?";
    $stmt = sqlsrv_query($conn, $sql, [$userid, $password]);
    if ($stmt && sqlsrv_has_rows($stmt)) {
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        // Normalize Session Data for App
        $_SESSION['user'] = [
            'userid' => $user['username'], 
            'fullname' => $user['fullname'] ?? $user['username'],
            'role_code' => $user['role_code'], // CRITICAL for DashboardController
            'job_function' => $user['role_code'], // For display compatibility
            'group_name' => $user['group_name'] ?? ''
        ];
        
        header("Location: index.php"); // Reload to dashboard
        exit;
    } else {
        $error = "Invalid Username or Password";
    }
}

// --- FALLBACK DASHBOARD (Role Based) ---
if (isset($_SESSION['user'])) {
    $dashboard = new App\Controllers\DashboardController();
    $dashboard->index();
} else {
    // LOGIN PAGE
    ?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - CITRA</title>
    <style>
        body { background: #f0f2f5; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 300px; }
        .login-box h2 { text-align: center; color: #d32f2f; margin-bottom: 20px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #d32f2f; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #b71c1c; }
        .error { color: red; font-size: 14px; text-align: center; margin-bottom: 10px; }
        .hint { font-size: 12px; color: #888; margin-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>CITRA Login</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="userid" placeholder="User ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Sign In</button>
        </form>
        <div class="hint">
            Defaults: maker01 / 123
        </div>
    </div>
</body>
</html>
    <?php
}
?>
