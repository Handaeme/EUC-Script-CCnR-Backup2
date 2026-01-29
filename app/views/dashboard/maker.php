<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

$pendingCount = $stats['pending'] ?? 0;
$wipCount = $stats['wip'] ?? 0;
$completedCount = $stats['completed'] ?? 0;
?>
<div class="main" style="background: #f8fafc; min-height: 100vh; padding: 15px;">
    
    <!-- Compact Header -->
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin: 0;">Dashboard</h2>
        <div style="font-size: 13px; color: #64748b;">
            Logged: <strong><?php echo htmlspecialchars($_SESSION['user']['fullname']); ?></strong>
        </div>
    </div>

    <!-- Small Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        
        <!-- Pending -->
        <div style="padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #f59e0b; background: white; display: flex; align-items: center; gap: 12px;">
            <div style="background: #fef3c7; padding: 6px; border-radius: 8px; color: #d97706;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Pending</div>
                <div style="font-size: 20px; font-weight: 800; color: #1e293b;"><?php echo $pendingCount; ?></div>
            </div>
        </div>

        <!-- WIP -->
        <div style="padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; background: white; display: flex; align-items: center; gap: 12px;">
            <div style="background: #eff6ff; padding: 6px; border-radius: 8px; color: #2563eb;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">WIP</div>
                <div style="font-size: 20px; font-weight: 800; color: #1e293b;"><?php echo $wipCount; ?></div>
            </div>
        </div>

        <!-- Completed -->
        <div style="padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-left: 4px solid #10b981; background: white; display: flex; align-items: center; gap: 12px;">
            <div style="background: #ecfdf5; padding: 6px; border-radius: 8px; color: #059669;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
            </div>
            <div>
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Done</div>
                <div style="font-size: 20px; font-weight: 800; color: #1e293b;"><?php echo $completedCount; ?></div>
            </div>
        </div>
    </div>

    <!-- Data Section -->
    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        
        <?php require_once 'app/views/layouts/filter_bar.php'; ?>

        <div style="margin-top: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 14px; font-weight: 700; color: #334155;">Task: Revisions Needed</h3>
                <span style="background: #fef3c7; color: #b45309; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; margin-left: auto;">
                    <?php echo count($revisions); ?> Items
                </span>
            </div>

            <div style="overflow-x: auto;">
                <table id="dataTable" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc;">
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">ID / Ticket</th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Request Title</th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px;">Status</th>
                            <th style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 10px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($revisions)): ?>
                            <tr>
                                <td colspan="4" style="padding: 30px; text-align: center; color: #94a3b8;">
                                    ✨ No revisions needed.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($revisions as $rev): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px; vertical-align: middle;">
                                    <div style="font-weight: 700; color: #1e293b; font-family: monospace;">#<?php echo htmlspecialchars($rev['script_number']); ?></div>
                                </td>
                                <td style="padding: 10px; vertical-align: middle;">
                                    <div style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($rev['title']); ?></div>
                                    <div style="font-size: 10px; color: #94a3b8;"><?php echo htmlspecialchars($rev['jenis']); ?> &bull; <?php echo htmlspecialchars($rev['media']); ?></div>
                                </td>
                                <td style="padding: 10px; vertical-align: middle;">
                                    <?php 
                                        $badgeBg = ($rev['status'] === 'REJECTED') ? '#fee2e2' : '#fef3c7';
                                        $badgeColor = ($rev['status'] === 'REJECTED') ? '#dc2626' : '#b45309';
                                    ?>
                                    <span style="background:<?php echo $badgeBg; ?>; color:<?php echo $badgeColor; ?>; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:700;">
                                        <?php echo htmlspecialchars($rev['status']); ?>
                                        <?php if(isset($rev['has_draft']) && $rev['has_draft'] == 1): ?> (Draft)<?php endif; ?>
                                    </span>
                                </td>
                                <td style="padding: 10px; vertical-align: middle; text-align: right;">
                                    <a href="index.php?controller=request&action=edit&id=<?php echo $rev['id']; ?>" 
                                       style="background: #1e293b; color: white; text-decoration: none; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                       Revise
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>

