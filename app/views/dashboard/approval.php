<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

// Defaults
$title = isset($pageTitle) ? $pageTitle : 'Dashboard Approval';
$desc = isset($pageDesc) ? $pageDesc : 'Manage pending script requests.';
?>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px;">
        <h2 style="color:var(--primary-red); margin:0;"><?php echo htmlspecialchars($title); ?></h2>
        <p style="color:var(--text-secondary);"><?php echo htmlspecialchars($desc); ?></p>
    </div>

    <div class="card">
        <h4 style="margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:10px;">Pending Approval Queue</h4>
        
        <?php require_once 'app/views/layouts/filter_bar.php'; ?>
        
        <?php if (empty($pendingRequests)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No pending requests found in your queue.</p>
            </div>
        <?php else: ?>
            <table id="dataTable" class="table" style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f9fafb; text-align:left;">
                        <th style="padding:12px; border-bottom:2px solid #eee;">#</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Script No</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Job Title</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Kategori</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Current Status</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Requester</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Date</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRequests as $index => $req): ?>
                    <!-- DEBUG KEYS: <?php echo implode(', ', array_keys($req)); ?> -->
                    <!-- DEBUG HAS_DRAFT: <?php var_dump($req['has_draft'] ?? 'MISSING'); ?> -->
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px;"><?php echo $index + 1; ?></td>
                        <td style="padding:12px;">
                            <div style="font-weight:600; color:var(--primary-red);"><?php 
                                $tId = $req['ticket_id'] ?? 'Pending';
                                echo is_numeric($tId) ? sprintf("SC-%04d", $tId) : $tId; 
                            ?></div>
                            <div style="font-size:11px; color:#666; margin-top:2px;">#<?php echo htmlspecialchars($req['script_number']); ?></div>
                        </td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($req['title']); ?></td>
                        <td style="padding:12px;">
                            <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px; font-size:11px;">
                                <?php echo htmlspecialchars($req['kategori']); ?>
                            </span>
                        </td>
                        <td style="padding:12px;">
                             <span style="background:#fef3c7; color:#b45309; padding:2px 8px; border-radius:10px; font-size:11px;">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                            <?php if (!empty($req['has_draft'])): ?>
                                <span style="background:#f3e8ff; color:#6b21a8; padding:2px 8px; border-radius:10px; font-size:11px; margin-left:5px; font-weight:bold; border:1px solid #e9d5ff;">
                                    DRAFT SAVED
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($req['created_by']); ?></td>
                        <td style="padding:12px;">
                            <?php 
                            if ($req['created_at'] instanceof DateTime) {
                                echo $req['created_at']->format('d M Y');
                            } else {
                                echo date('d M Y', strtotime($req['created_at']));
                            }
                            ?>
                        </td>
                        <td style="padding:12px;">
                            <a href="index.php?controller=request&action=review&id=<?php echo $req['id']; ?>" 
                               style="background:var(--primary-red); color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size:12px;">
                               Review / Process
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
