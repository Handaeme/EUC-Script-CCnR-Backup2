<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
?>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px;">
        <h2 style="color:var(--primary-red); margin:0;">My Tasks</h2>
        <p style="color:var(--text-secondary);">All your script requests.</p>
    </div>

    <div class="card">
        <h4 style="margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:10px;">My Script Requests</h4>
        
        <?php require_once 'app/views/layouts/filter_bar.php'; ?>
        <?php if (empty($requests)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>You haven't created any requests yet.</p>
                <a href="?controller=request&action=create" class="btn btn-primary" style="margin-top:15px; display:inline-block;">Create New Request</a>
            </div>
        <?php else: ?>
            <table id="dataTable" class="table" style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f9fafb; text-align:left;">
                        <th style="padding:12px; border-bottom:2px solid #eee;">#</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Script No</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Jenis</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Kategori</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Status</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Current Role</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Date</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $index => $req): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px;"><?php echo $index + 1; ?></td>
                        <td style="padding:12px; font-weight:600; color:var(--primary-red);"><?php echo htmlspecialchars($req['script_number']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($req['jenis']); ?></td>
                        <td style="padding:12px;">
                            <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px; font-size:11px;">
                                <?php echo htmlspecialchars($req['kategori']); ?>
                            </span>
                        </td>
                        <td style="padding:12px;">
                            <?php 
                            $statusColor = '#fef3c7'; // default yellow
                            $statusTextColor = '#b45309';
                            if ($req['status'] === 'LIBRARY') {
                                $statusColor = '#d1fae5';
                                $statusTextColor = '#065f46';
                            } elseif (in_array($req['status'], ['REJECTED', 'REVISION'])) {
                                $statusColor = '#fee2e2';
                                $statusTextColor = '#b91c1c';
                            }
                            ?>
                            <span style="background:<?php echo $statusColor; ?>; color:<?php echo $statusTextColor; ?>; padding:2px 8px; border-radius:10px; font-size:11px;">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                        </td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($req['current_role']); ?></td>
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
                            <?php if (in_array($req['status'], ['REVISION', 'REJECTED'])): ?>
                                <a href="?controller=request&action=edit&id=<?php echo $req['id']; ?>" style="background:#f59e0b; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size:12px;">
                                    Edit & Resubmit
                                </a>
                            <?php else: ?>
                                <span style="color:#999; font-size:12px;">In Process</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
