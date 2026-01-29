    <!-- SIDEBAR -->
    <?php
    // Helper to determine active class
    $currentController = $_GET['controller'] ?? 'dashboard';
    $currentAction = $_GET['action'] ?? 'index';
    
    function isActive($c, $a = null) {
        global $currentController, $currentAction;
        if ($c == 'dashboard' && $currentController == 'dashboard' && $currentAction == 'index' && !isset($_GET['controller'])) return 'active';
        if ($c == $currentController && ($a == null || $a == $currentAction)) return 'active';
        return '';
    }
    ?>
    <style>
        .menu-item.active { background: #ffebee; color: #d32f2f; font-weight: bold; border-right: 3px solid #d32f2f; }
    </style>
    
    <div class="sidebar">
        <a href="index.php" class="menu-item <?php echo isActive('dashboard'); ?>">Dashboard</a>
        
        <?php 
        $role = $_SESSION['user']['role_code'] ?? '';
        if ($role === 'MAKER' || $role === 'Maker') {
            // Count Pending Revisions
            $reqModel = new \App\Models\RequestModel();
            $stats = $reqModel->getMakerStats($_SESSION['user']['userid']);
            $revCount = $stats['pending'] ?? 0;
        ?>
            <a href="?controller=request&action=index" class="menu-item <?php echo isActive('request', 'index'); ?>">
                My Tasks
                <?php if($revCount > 0): ?>
                    <span style="background:#ef4444; color:white; font-size:10px; padding:2px 6px; border-radius:10px; margin-left:8px; font-weight:bold;"><?php echo $revCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="?controller=request&action=create" class="menu-item <?php echo isActive('request', 'create'); ?>">Create New Request</a>
        <?php } else { ?>
            <a href="?controller=request&action=history" class="menu-item <?php echo isActive('request', 'history'); ?>">My History (Approvals)</a>
        <?php } ?>
        
        <a href="?controller=template&action=index" class="menu-item <?php echo isActive('template'); ?>">Template Library</a>
        
        <!-- Library (Procedure/All) -->
        <a href="?controller=dashboard&action=library" class="menu-item <?php echo isActive('dashboard', 'library'); ?>">Script Library</a>
        <a href="?controller=audit&action=index" class="menu-item <?php echo isActive('audit'); ?>">Audit Trail</a>
    </div>
