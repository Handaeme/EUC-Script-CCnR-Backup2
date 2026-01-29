<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
?>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="color:var(--primary-red); margin:0;">Audit Trail</h2>
            <p style="color:var(--text-secondary);">System-wide activity log.</p>
        </div>
        <div>
            <a href="?controller=audit&action=export" class="btn btn-primary" style="background:#16a34a; text-decoration:none; padding:10px 20px; border-radius:4px; color:white; font-weight:bold;">
                Export to Excel
            </a>
        </div>
    </div>

    <div class="card">
        <h4 style="margin-bottom:15px; border-bottom:2px solid #eee; padding-bottom:10px;">Audit Summary</h4>
        
        <?php require_once 'app/views/layouts/filter_bar.php'; ?>
        
        <?php if (empty($logs)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No activity recorded yet.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table id="dataTable" class="table" style="width:100%; border-collapse:collapse; font-size:12px;">
                    <thead>
                        <tr style="background:#f9fafb; text-align:left; color:#555;">
                            <th style="padding:10px; border-bottom:2px solid #eee; width:100px;">Action</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Ticket Info</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Maker</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Script Content</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Reviewer</th>
                            <th style="padding:10px; border-bottom:2px solid #eee;">Status</th>
                            <th style="padding:10px; border-bottom:2px solid #eee; text-align:right;">Last Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $row):
                         
                            // Determine Dynamic Reviewer & Timestamp & Status
                            $reviewer = '-';
                            $displayStatus = 'SUBMITTED';
                            $statusColor = '#666';
                            $timestamp = $row['created_date']; // Default
                            
                            // Logic Hierarchy (Reverse Order of Approval) to determine current stage
                            if ($row['status_procedure'] === 'APPROVE_PROCEDURE') {
                                $reviewer = 'System (Published)';
                                $displayStatus = 'LIBRARY';
                                $statusColor = '#16a34a'; // Green
                                $timestamp = $row['timestamp_procedure'];
                            } elseif ($row['status_procedure'] === 'REVISION' || $row['status_procedure'] === 'REJECTED') {
                                $reviewer = 'CPMS / QPM'; 
                                $displayStatus = 'PROCEDURE ' . $row['status_procedure'];
                                $statusColor = '#dc2626'; // Red
                                $timestamp = $row['timestamp_procedure'];
                            } elseif ($row['status_pic'] === 'APPROVE_PIC') {
                                $reviewer = 'CPMS / QPM';
                                $displayStatus = 'WAITING PROCEDURE';
                                $statusColor = '#eab308'; // Yellow
                                $timestamp = $row['timestamp_pic'];
                            } elseif ($row['status_pic'] === 'REVISION' || $row['status_pic'] === 'REJECTED') {
                                $reviewer = $row['pic'] ?? 'PIC';
                                $displayStatus = 'PIC ' . $row['status_pic'];
                                $statusColor = '#dc2626';
                                $timestamp = $row['timestamp_pic'];
                            } elseif ($row['status_spv'] === 'APPROVE_SPV') {
                                $reviewer = 'Coordinator Script';
                                $displayStatus = 'WAITING PIC';
                                $statusColor = '#eab308';
                                $timestamp = $row['timestamp_spv'];
                            } elseif ($row['status_spv'] === 'REVISION' || $row['status_spv'] === 'REJECTED') {
                                $reviewer = $row['selected_spv'] ?? 'SPV';
                                $displayStatus = 'SPV ' . $row['status_spv'];
                                $statusColor = '#dc2626';
                                $timestamp = $row['timestamp_spv'];
                            } else {
                                // Default / New Request (No SPV Action yet)
                                $reviewer = $row['selected_spv'] ?? 'Dept. / Division Head';
                                $displayStatus = 'SUBMITTED';
                                $statusColor = '#9ca3af'; // Gray (Neutral)
                                $timestamp = $row['created_date'];
                            }

                            // Content Snippet
                            $snippet = '';
                            if (($row['mode'] ?? '') === 'FILE_UPLOAD') {
                                $filename = $row['script_content'] ?? '';
                                if (empty($filename)) {
                                     $filename = '<span style="color:#9ca3af; font-style:italic;">(No File)</span>';
                                }
                                $snippet = '<div style="display:flex; align-items:center; gap:5px; color:#4b5563;">
                                                <i class="fs-4 bi-file-earmark-text"></i> 
                                                <span style="font-weight:600;">' . $filename . '</span>
                                            </div>';
                            } else {
                                // Free Input - Truncate
                                $rawContent = strip_tags($row['script_content'] ?? ''); // Remove HTML tags
                                $rawContent = trim($rawContent); // Remove whitespace
                                
                                if (strlen($rawContent) === 0) {
                                    $snippet = '<div style="color:#d1d5db; font-style:italic; font-size:11px;">(No Content Preview)</div>';
                                } else {
                                    $truncated = strlen($rawContent) > 60 ? substr($rawContent, 0, 60) . '...' : $rawContent;
                                    $snippet = '<div style="color:#4b5563; font-family:\'Inter\', system-ui, -apple-system, sans-serif; font-size:12px; line-height:1.4;">' . htmlspecialchars($truncated) . '</div>';
                                }
                            }
                            // Format Ticket ID (SC-XXXX)
                            $ticketDisplay = $row['ticket_id'];
                            if (is_numeric($ticketDisplay)) {
                                $ticketDisplay = sprintf("SC-%04d", $ticketDisplay);
                            }
                        ?>
                        <tr style="border-bottom:1px solid #eee; hover:background-color:#f9f9f9;">
                            <!-- Action Button -->
                            <td style="padding:10px;">
                                <a href="?controller=audit&action=detail&id=<?php echo $row['id']; ?>" style="display:inline-block; padding:6px 12px; background:#fff; border:1px solid #d1d5db; border-radius:6px; color:#374151; font-weight:600; text-decoration:none; font-size:11px; transition:all 0.2s; box-shadow:0 1px 2px rgba(0,0,0,0.05);" onmouseover="this.style.background='#f3f4f6'; this.style.color='#111';" onmouseout="this.style.background='#fff'; this.style.color='#374151';">Lihat Detail</a>
                            </td>

                            <!-- Ticket Info -->
                            <td style="padding:10px;">
                                <div style="font-weight:bold; color:var(--primary-red); font-size:13px;"><?php echo htmlspecialchars($ticketDisplay); ?></div>
                                <div style="font-size:11px; color:#666; margin-top:2px;"><?php echo htmlspecialchars($row['script_number']); ?></div>
                                <div style="font-size:11px; font-weight:600; color:#333; margin-top:4px;"><?php echo htmlspecialchars($row['title'] ?? ''); ?></div>
                            </td>
                            
                            <!-- Maker -->
                            <td style="padding:10px;">
                                <div style="font-weight:600; color:#374151;"><?php echo htmlspecialchars($row['maker']); ?></div>
                                <div style="font-size:10px; color:#9ca3af;">Created</div>
                            </td>

                            <!-- Script Content -->
                            <td style="padding:10px; max-width:250px;">
                                <?php echo $snippet; ?>
                            </td>
                            
                            <!-- Dynamic Reviewer -->
                            <td style="padding:10px;">
                                <div style="font-weight:600; color:#1f2937;"><?php echo htmlspecialchars($reviewer); ?></div>
                            </td>
                            
                            <!-- Status -->
                            <td style="padding:10px;">
                                <span style="display:inline-block; padding:4px 10px; border-radius:20px; font-size:10px; font-weight:bold; background-color:<?php echo $statusColor; ?>20; color:<?php echo $statusColor; ?>; border:1px solid <?php echo $statusColor; ?>40;">
                                    <?php echo $displayStatus; ?>
                                </span>
                            </td>

                            <!-- Last Updated (Jam Dynamic) -->
                            <!-- Last Updated (Date Only) -->
                            <td style="padding:10px; text-align:right;">
                                <div style="font-weight:700; color:#374151; font-size:12px;"><?php echo $timestamp ? date('d M Y', strtotime($timestamp)) : '-'; ?></div>
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
