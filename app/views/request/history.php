<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
?>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px;">
        <h2 style="color:var(--primary-red); margin:0;">My History (Approvals)</h2>
        <p style="color:var(--text-secondary);">List of requests you have processed.</p>
    </div>

    <div class="card">
        <?php require_once 'app/views/layouts/filter_bar.php'; ?>
        <?php if (empty($requests)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No approval history found.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table id="dataTable" class="table" style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="background:#f9fafb; text-align:left;">
                            <th style="padding:10px; border-bottom:2px solid #eee;">Ticket / Script</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Details</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">My Action</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Date</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Current Status</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px;">
                                <div style="font-weight:bold; color:var(--primary-red);"><?php echo htmlspecialchars($req['ticket_id'] ?? '-'); ?></div>
                                <div style="font-size:11px; color:#666; margin-top:2px;"><?php echo htmlspecialchars($req['script_number']); ?></div>
                            </td>
                            <td style="padding:10px;">
                                <div><?php echo htmlspecialchars($req['jenis']); ?></div>
                                <div style="font-size:11px; color:#666;"><?php echo htmlspecialchars($req['produk']); ?></div>
                                <div style="margin-top:2px;">
                                    <span style="background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:bold;">
                                        <?php echo htmlspecialchars($req['media']); ?>
                                    </span>
                                </div>
                            </td>
                            <td style="padding:10px;">
                                <?php 
                                $action = $req['my_last_action'];
                                $color = '#666';
                                if (strpos($action, 'APPROVE') !== false) $color = '#16a34a';
                                if (strpos($action, 'REJECT') !== false) $color = '#dc2626';
                                if (strpos($action, 'REVISION') !== false) $color = '#d97706';
                                ?>
                                <div style="font-weight:bold; color:<?php echo $color; ?>;">
                                    <?php echo htmlspecialchars($action); ?>
                                </div>
                                <?php if (!empty($req['my_note'])): ?>
                                    <div style="font-size:11px; color:#555; background:#fffbeb; padding:2px 4px; border-radius:2px; margin-top:4px; border:1px solid #eee;">
                                        <?php echo htmlspecialchars($req['my_note']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:10px;">
                                <?php echo date('d M Y H:i', strtotime($req['my_action_date']->format('Y-m-d H:i:s'))); ?>
                            </td>
                            <td style="padding:10px;">
                                <span style="background:#f3f4f6; color:#374151; padding:4px 8px; border-radius:12px; font-size:11px; font-weight:bold;">
                                    <?php echo htmlspecialchars($req['status']); ?>
                                </span>
                            </td>
                            <td style="padding:10px;">
                                <a href="?controller=request&action=review&id=<?php echo $req['id']; ?>" style="text-decoration:none; color:#3b82f6; font-weight:bold;">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
