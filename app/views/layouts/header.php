<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EUC Script CCnR</title>
    <link rel="stylesheet" href="public/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <div style="display:flex;align-items:center;gap:15px;">
            <h3>EUC Script CCnR</h3>
        </div>
        <div>
            <?php 
            // Handle session check loosely here if not strictly enforced elsewhere
            if(session_status() == PHP_SESSION_NONE) session_start();
            $user = $_SESSION['user'] ?? ['fullname' => 'Guest', 'job_function' => 'Visitor'];
            ?>
            <span>Selamat Datang! <?php echo htmlspecialchars($user['userid']); ?> (<?php echo htmlspecialchars($user['group_name'] ?: $user['role_code']); ?>)</span>
            <a href="index.php?action=logout" style="color:white;margin-left:15px;text-decoration:none;font-weight:bold;">Logout</a>
        </div>
    </div>
