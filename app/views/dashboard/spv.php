<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
?>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px;">
        <h2 style="color:var(--primary-red); margin:0;">Dashboard Supervisor</h2>
        <p style="color:var(--text-secondary);">Manage your team's script requests.</p>
    </div>

    <div class="card">
        <h4 style="margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:10px;">Pending Approval</h4>
        
        <?php if (empty($pendingRequests)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No pending requests found.</p>
            </div>
        <?php else: ?>
            <table class="table" style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f9fafb; text-align:left;">
                        <th style="padding:12px; border-bottom:2px solid #eee;">#</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Script No</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Job Title</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Kategori</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Requester</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Date</th>
                        <th style="padding:12px; border-bottom:2px solid #eee;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRequests as $index => $req): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px;"><?php echo $index + 1; ?></td>
                        <td style="padding:12px; font-weight:600; color:var(--primary-red);"><?php echo htmlspecialchars($req['script_number']); ?></td>
                        <td style="padding:12px;"><?php echo htmlspecialchars($req['title']); ?></td>
                        <td style="padding:12px;">
                            <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px; font-size:11px;">
                                <?php echo htmlspecialchars($req['kategori']); ?>
                            </span>
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
                               Review
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
