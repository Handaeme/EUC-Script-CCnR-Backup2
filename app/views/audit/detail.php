<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

$req = $data['request'];
$logs = $data['logs'];
$files = $data['files'];
$content = $data['content'];

// Format Ticket ID
// Format Ticket ID
$ticketId = isset($req['ticket_id']) ? $req['ticket_id'] : null;
if (is_numeric($ticketId)) {
    $ticketId = sprintf("SC-%04d", $ticketId);
}

// Status Color Logic
$statusColor = '#6b7280'; // Gray (default)
if ($req['status'] === 'CLOSED') $statusColor = '#16a34a'; // Green
else if ($req['status'] === 'WIP') $statusColor = '#f59e0b'; // Orange
else if ($req['status'] === 'REJECTED') $statusColor = '#dc2626'; // Red
else if ($req['status'] === 'CREATED' || $req['status'] === 'SUBMITTED') $statusColor = '#6b7280'; // Gray
?>

<style>
    /* Scoped Styles for Audit Detail */
    .detail-container {
        padding: 20px;
        width: 100%;
        box-sizing: border-box;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 340px; /* Fluid Content + Fixed Sidebar */
        gap: 24px;
        align-items: start;
    }

    /* Content & Panel Columns */
    .content-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
        min-width: 0; /* Prevents flex/grid blowout */
    }

    .panel-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Mobile Responsiveness */
    @media (max-width: 900px) {
        .detail-grid {
            grid-template-columns: 100%; /* Stack vertically */
        }
        .panel-column {
            order: -1; /* Optional: Show metadata/history first on mobile? Or keep below. Let's keep below for now. */
            order: 1; 
        }
    }

    /* Excel Tab Styles - Global */
    .sheet-tabs-nav { background:#f3f4f6; padding:10px; border-bottom:1px solid #ddd; display:flex; gap:5px; overflow-x:auto; }
    .btn-sheet { padding:6px 12px; border:1px solid #d1d5db; background:white; cursor:pointer; border-radius:4px; font-size:12px; font-weight:500; font-family:'Inter', sans-serif; }
    .btn-sheet.active { background:#3b82f6; color:white; border-color:#3b82f6; }
    .sheet-container { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .excel-preview { width:100%; border-collapse:collapse; font-size:13px; }
    .excel-preview td { border:1px solid #ddd; padding:8px; min-width:50px; }

    /* Inline Comment Style */
    .inline-comment {
        background-color: #fef08a !important;
        cursor: pointer;
        border-bottom: 2px solid #eab308;
        transition: background-color 0.3s;
    }
    .inline-comment:hover {
        background-color: #fde047 !important;
    }

    /* Blink Animation */
    @keyframes blink-animation {
        0% { outline: 3px solid #eab308; box-shadow: 0 0 10px rgba(234, 179, 8, 0.5); transform: scale(1.02); z-index: 10; position: relative; }
        50% { outline: 3px solid #eab308; box-shadow: 0 0 15px rgba(234, 179, 8, 0.7); }
        100% { outline: 3px solid transparent; box-shadow: none; transform: scale(1); z-index: auto; position: static; }
    }
    .blink-highlight {
        animation: blink-animation 1.5s ease-out forwards;
    }
</style>

<div class="main detail-container">
    <!-- Breadcrumb & Header -->
    <div style="margin-bottom: 24px;">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
            <a href="?controller=audit" style="text-decoration:none; color:#666; font-size:14px; display:flex; align-items:center; gap:5px; padding:6px 12px; background:white; border:1px solid #ddd; border-radius:4px; transition:all 0.2s;">
                <i class="bi-arrow-left"></i> Back to Audit
            </a>
            <span style="color:#ccc;">|</span>
            <span style="color:#888; font-size:14px; font-weight:500;">Request Detail</span>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:start; flex-wrap:wrap; gap:15px;">
            <div>
                <h2 style="color:var(--primary-red); margin:0; display:flex; align-items:center; gap:12px; font-size:22px;">
                    <?php echo htmlspecialchars(!empty($ticketId) ? $ticketId : 'Pending Ticket'); ?>
                </h2>
                <div style="display:flex; align-items:center; gap:10px; margin-top:8px;">
                    <span style="font-size:13px; color:#666; font-family:monospace;">
                        <?php echo htmlspecialchars($req['script_number']); ?>
                    </span>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:12px; color:#888; margin-bottom:2px;">Created Date</div>
                <div style="font-weight:600; color:#333; font-size:14px;">
                    <?php 
                    $date = $req['created_at'];
                    echo ($date instanceof DateTime) ? $date->format('d M Y, H:i') : date('d M Y, H:i', strtotime($date)); 
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="detail-grid">
        
        <!-- LEFT CONTENT COLUMN -->
        <div class="content-column">
            
            <!-- Metadata Card (Grid inside Grid) -->
            <div class="card" style="box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee;">
                <h4 style="margin-bottom:20px; border-bottom:1px solid #f0f0f0; padding-bottom:10px; color:#444; font-size:15px; font-weight:700;">Request Information</h4>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px 24px; font-size:13px;">
                    <!-- Row 1 -->
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Script Number</div>
                        <div style="font-weight:600; font-size:14px; color:#111; font-family:monospace;"><?php echo htmlspecialchars($req['script_number']); ?></div>
                    </div>
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Ticket ID</div>
                        <div style="font-weight:600; font-size:14px; color:#111;"><?php echo htmlspecialchars(!empty($ticketId) ? $ticketId : 'Pending'); ?></div>
                    </div>
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Status</div>
                        <div>
                            <span style="font-size:12px; background:<?php echo $statusColor; ?>; color:white; padding:4px 10px; border-radius:12px; font-weight:600; text-transform:uppercase; display:inline-block;">
                                <?php echo htmlspecialchars($req['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Created Date</div>
                        <div style="font-weight:600; font-size:14px; color:#111;">
                            <?php 
                            $date = $req['created_at'];
                            echo ($date instanceof DateTime) ? $date->format('d M Y, H:i') : date('d M Y, H:i', strtotime($date)); 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Row 2 -->
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Title</div>
                        <div style="font-weight:600; font-size:14px; color:#111;"><?php echo htmlspecialchars($req['title'] ?? '-'); ?></div>
                    </div>
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Maker</div>
                        <div style="font-weight:600; font-size:14px; color:#111;"><?php echo htmlspecialchars($req['created_by']); ?></div>
                    </div>
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Input Mode</div>
                        <div style="font-weight:600; font-size:14px; color:#111;">
                            <?php 
                            $mode = $req['mode'] ?? 'FREE_INPUT';
                            echo $mode === 'FILE_UPLOAD' ? 'File Upload' : 'Free Input';
                            ?>
                        </div>
                    </div>
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Last Updated</div>
                        <div style="font-weight:600; font-size:14px; color:#111;">
                            <?php 
                            $updated = $req['updated_at'] ?? $req['created_at'];
                            echo ($updated instanceof DateTime) ? $updated->format('d M Y, H:i') : date('d M Y, H:i', strtotime($updated)); 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Row 3 -->
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Jenis</div>
                        <div style="font-weight:600; font-size:14px; color:#111;"><?php echo htmlspecialchars($req['jenis']); ?></div>
                    </div>
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Produk</div>
                        <div style="font-weight:600; font-size:14px; color:#111;"><?php echo htmlspecialchars($req['produk']); ?></div>
                    </div>
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Media</div>
                        <div style="font-weight:600; font-size:14px; color:#111;"><?php echo htmlspecialchars($req['media']); ?></div>
                    </div>
                    <div>
                        <div style="color:#888; margin-bottom:5px; font-size:12px;">Kategori</div>
                        <div style="font-weight:600; font-size:14px; color:#111;"><?php echo htmlspecialchars($req['kategori']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Content Card -->
            <div class="card" style="flex:1; box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee;">
                <h4 style="margin-bottom:20px; border-bottom:1px solid #f0f0f0; padding-bottom:10px; color:#444; font-size:15px; font-weight:700;">Script Content</h4>
                
                <?php if (($req['mode'] ?? '') === 'FILE_UPLOAD'): ?>
                    <!-- File Preview - Version Timeline -->
                    <?php include 'app/views/audit/_version_timeline.php'; ?>
                <?php else: ?>
                    <!-- Free Input Display (Tabbed) -->
                    <div style="background:#fff; border:1px solid #eee; border-radius:8px; width:100%; min-height:450px;">
                        
                        <?php if (empty($content['data'])): ?>
                            <div style="color:#9ca3af; font-style:italic; text-align:center; padding:40px;">(No Content Available)</div>
                        <?php else: ?>
                            
                            <!-- 1. Tabs Header -->
                            <div class="sheet-tabs-nav" style="background:#f9fafb; padding:10px; border-bottom:1px solid #eee; display:flex; gap:8px; overflow-x:auto;">
                                <?php foreach ($content['data'] as $index => $item): ?>
                                    <?php 
                                    $isActive = ($index === 0) ? 'active' : '';
                                    $mediaType = htmlspecialchars($item['media']);
                                    $uniqueId = 'tab-btn-' . $index;
                                    $sheetId = 'sheet-free-' . $index;
                                    ?>
                                    <button 
                                        id="<?php echo $uniqueId; ?>"
                                        class="btn-sheet <?php echo $isActive; ?>" 
                                        onclick="changeSheet('<?php echo $sheetId; ?>')"
                                        style="display:flex; align-items:center; gap:6px;">
                                        
                                        <!-- Icon Logic based on Media -->
                                        <?php if(stripos($mediaType, 'WHATSAPP') !== false): ?>
                                            <i class="bi-whatsapp" style="font-size:11px;"></i>
                                        <?php elseif(stripos($mediaType, 'EMAIL') !== false): ?>
                                            <i class="bi-envelope" style="font-size:11px;"></i>
                                        <?php elseif(stripos($mediaType, 'SMS') !== false): ?>
                                            <i class="bi-chat-dots" style="font-size:11px;"></i>
                                        <?php else: ?>
                                            <i class="bi-file-text" style="font-size:11px;"></i>
                                        <?php endif; ?>

                                        <?php echo $mediaType; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- 2. Content Panes -->
                            <div class="sheet-container" style="padding:0;">
                                <?php foreach ($content['data'] as $index => $item): ?>
                                    <?php 
                                    $displayStyle = ($index === 0) ? 'block' : 'none';
                                    $sheetId = 'sheet-free-' . $index;
                                    ?>
                                    <div id="<?php echo $sheetId; ?>" class="sheet-pane" style="display:<?php echo $displayStyle; ?>; padding:20px;">
                                        <div style="font-family:'Inter', system-ui, -apple-system, sans-serif; white-space:pre-line; font-size:13px; color:#333; line-height:1.6; background:white;" contenteditable="false"><?php echo trim($item['content']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- RIGHT PANEL COLUMN -->
        <div class="panel-column">
            
            <!-- EX-COMMENT SIDEBAR (Review Notes) -->
            <!-- Hidden by default, shown by JS if comments exist -->
            <div id="comment-sidebar" class="card" style="box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee; display:none; max-height:600px; overflow-y:auto;">
                <h4 style="margin-bottom:20px; border-bottom:1px solid #f0f0f0; padding-bottom:10px; color:#444; font-size:15px; font-weight:700;">
                    Review Notes
                    <span style="font-size:11px; color:#ef4444; background:#fef2f2; padding:2px 8px; border-radius:12px; margin-left:8px; border:1px solid #fecaca;">Action Required</span>
                </h4>
                <div id="comment-list"></div>
            </div>

            <!-- Original File Card -->
            <?php if (($req['mode'] ?? '') === 'FILE_UPLOAD' && isset($content['filename'])): ?>
            <div class="card" style="box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                    <h4 style="color:#444; font-size:15px; margin:0; font-weight:700;">Original File</h4>
                </div>
                
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="display:flex; align-items:center; gap:10px; background:#fafafa; padding:12px; border-radius:6px; border:1px solid #e5e7eb;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;">
                            <rect x="4" y="2" width="12" height="20" rx="2" fill="#10b981" opacity="0.1"/>
                            <path d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 2V8H20" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 13H15" stroke="#10b981" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M9 17H15" stroke="#10b981" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:600; font-size:12px; color:#111; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($content['filename']); ?>">
                                <?php echo htmlspecialchars($content['filename']); ?>
                            </div>
                            <div style="font-size:10px; color:#888; margin-top:2px;">Excel File</div>
                        </div>
                    </div>
                    <a href="?controller=request&action=download&file=TEMPLATE&id=<?php echo $req['id']; ?>" style="background:#dc2626; color:white; padding:10px 16px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:background 0.2s;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 10L12 15L17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Download File
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Attachments Card -->
            <div class="card" style="box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f0f0f0; padding-bottom:10px;">
                    <h4 style="color:#444; font-size:15px; margin:0; font-weight:700;">Attachments</h4>
                </div>
                
                <?php 
                $hasDocs = false;
                $docTypes = ['LEGAL' => 'Legal Review', 'CX' => 'CX Review', 'LEGAL_SYARIAH' => 'Legal Syariah', 'LPP' => 'Checklist LPP'];
                ?>
                <ul style="list-style:none; padding:0; margin:0;">
                    <?php foreach ($docTypes as $type => $label): ?>
                        <?php if (isset($files[$type])): $hasDocs = true; ?>
                            <li style="margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; background:#fafafa; padding:12px; border-radius:6px; border:1px solid #eee; transition:background 0.2s;">
                                <div style="overflow:hidden; flex:1; margin-right:10px;">
                                    <div style="font-size:10px; color:#888; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px; font-weight:600;"><?php echo $label; ?></div>
                                    <div style="font-weight:600; font-size:13px; color:#333; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($files[$type]['original_filename'] ?? $files[$type]['filename'] ?? 'Attached File'); ?>">
                                        <?php echo htmlspecialchars($files[$type]['original_filename'] ?? $files[$type]['filename'] ?? 'Attached File'); ?>
                                    </div>
                                </div>
                                <a href="?controller=request&action=download&file=<?php echo $type; ?>&id=<?php echo $req['id']; ?>" class="btn-icon" style="color:#3b82f6; padding:6px; border-radius:4px; background:#eff6ff;">
                                    ⬇
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php if (!$hasDocs): ?>
                    <div style="text-align:center; padding:25px 10px; color:#9ca3af; font-size:13px; font-style:italic; background:#fafafa; border-radius:6px; border:1px dashed #eee;">
                        No documents attached.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Approval History Card -->
            <div class="card" style="flex:1; box-shadow:0 1px 2px rgba(0,0,0,0.05); border:1px solid #eee; overflow:hidden;">
                <h4 style="margin-bottom:20px; border-bottom:1px solid #f0f0f0; padding-bottom:10px; color:#444; font-size:15px; font-weight:700;">Timeline</h4>
                
                <div class="timeline" style="border-left:2px solid #e5e7eb; margin-left:8px; padding-left:24px; padding-bottom:10px; position:relative;">
                    <?php foreach ($logs as $log): 
                        $roleColor = '#6b7280';
                        if ($log['user_role'] === 'MAKER') $roleColor = '#3b82f6';
                        if ($log['user_role'] === 'SPV') $roleColor = '#f59e0b';
                        if ($log['user_role'] === 'PIC') $roleColor = '#8b5cf6';
                        if ($log['user_role'] === 'PROCEDURE') $roleColor = '#10b981';
                    ?>
                    <div style="position:relative; margin-bottom:28px;">
                        <!-- Dot -->
                        <div style="position:absolute; left:-31px; top:4px; width:10px; height:10px; border-radius:50%; background:white; border:2px solid <?php echo $roleColor; ?>; box-shadow:0 0 0 2px white;"></div>
                        
                        <div style="font-size:11px; color:#9ca3af; margin-bottom:3px; font-family:var(--font-family, sans-serif);">
                            <?php 
                            $logDate = $log['created_at'];
                            echo ($logDate instanceof DateTime) ? $logDate->format('d M Y, H:i') : date('d M Y, H:i', strtotime($logDate)); 
                            ?>
                        </div>
                        <div style="font-weight:700; color:#333; font-size:13px; margin-bottom:3px; line-height:1.4;">
                            <?php echo htmlspecialchars($log['action']); ?>
                        </div>
                        <div style="font-size:12px; color:#666;">
                            by <span style="font-weight:600; color:<?php echo $roleColor; ?>"><?php echo htmlspecialchars($log['group_name'] ?? $log['user_role']); ?></span>
                        </div>
                        <?php if (!empty($log['details'])): ?>
                            <div style="margin-top:8px; background:#fffbeb; padding:10px; border-radius:6px; font-size:12px; border:1px solid #fcd34d; color:#92400e; line-height:1.5;">
                                <?php 
                                $detailText = $log['details'];
                                // Logic: If it's a generic legacy string, inject the username
                                if ($detailText === 'Approved by Supervisor') $detailText = 'Approved by ' . $log['user_id'];
                                elseif ($detailText === 'Approved by PIC') $detailText = 'Approved by ' . $log['user_id'];
                                elseif ($detailText === 'Initial Submission') $detailText = 'Submitted by ' . $log['user_id'];
                                elseif ($detailText === 'Published to Library') $detailText = 'Published to Library by ' . $log['user_id'];
                                elseif ($detailText === 'Re-submitted by Maker') $detailText = 'Re-submitted by ' . $log['user_id'];
                                elseif ($detailText === 'Draft saved by Maker') $detailText = 'Draft saved by ' . $log['user_id'];
                                
                                echo htmlspecialchars($detailText); 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Start Point -->
                    <div style="position:relative;">
                        <div style="position:absolute; left:-30px; top:5px; width:8px; height:8px; border-radius:50%; background:#d1d5db;"></div>
                        <div style="font-size:12px; color:#9ca3af; font-style:italic;">Created</div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
// Version Timeline Toggle Function
function toggleVersionContent(idx) {
    const content = document.getElementById('version-content-' + idx);
    const icon = document.getElementById('icon-' + idx);
    const header = content.previousElementSibling;
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-up');
        header.style.background = '#f3f4f6';
    } else {
        content.style.display = 'none';
        icon.classList.remove('bi-chevron-up');
        icon.classList.add('bi-chevron-down');
        header.style.background = '#f9fafb';
    }
    
    // Re-render side comments if they exist in this version
    if (typeof renderSideComments === 'function') {
        setTimeout(() => renderSideComments(), 100);
    }
}

// Global Excel Tab Switching Function
// Global Excel Tab Switching Function (Scoped to Container)
function changeSheet(sheetId) {
    const selectedSheet = document.getElementById(sheetId);
    if (!selectedSheet) return;

    // Find parent container (closest wrapper)
    const container = selectedSheet.closest('.sheet-container') || selectedSheet.parentElement;

    // Hide only sheets IN THIS container
    const sheets = container.querySelectorAll('.sheet-pane');
    sheets.forEach(pane => pane.style.display = 'none');
    
    // Deactivate only buttons IN THIS container
    // Buttons are usually in .sheet-tabs-nav which is a sibling of panes, or inside container
    const btns = container.querySelectorAll('.btn-sheet');
    btns.forEach(btn => btn.classList.remove('active'));
    
    // Show selected sheet
    selectedSheet.style.display = 'block';
    
    // Activate clicked button (event.target)
    // Note: If called programmatically, event might not be the button. 
    // Ideally we find the button by ID too.
    if (event && event.target && event.target.classList.contains('btn-sheet')) {
        event.target.classList.add('active');
    } else {
        // Fallback: Find button by ID matching sheet ID logic
        // But ID logic is loose now. Simple fallback:
        // Try to find button that targets this sheet
        const btn = container.querySelector(`button[onclick*="'${sheetId}'"]`);
        if (btn) btn.classList.add('active');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Audit View Context
    const editorContainer = document.getElementById('audit-editor-container');
    const sidebar = document.getElementById('comment-sidebar');
    
    if (editorContainer && sidebar) {
        // Initial Render
        renderSideComments();
        
        // Show Sidebar if there are comments
        const comments = editorContainer.querySelectorAll('span[data-comment-id]');
        if (comments.length > 0) {
            sidebar.style.display = 'block';
        }

        // Add Resize Observer to re-align comments if layout changes
        const resizeObserver = new ResizeObserver(() => {
            renderSideComments();
        });
        resizeObserver.observe(editorContainer);
    }
    
    // Initialize Excel features if present
    renderSideComments();
    updateSheetTabBadges();
    
    // STRICT READ-ONLY ENFORCEMENT
    setTimeout(enforceReadOnly, 500); // Run after initial render
    
    // Re-run on clicks (tab switches) just in case
    document.addEventListener('click', () => setTimeout(enforceReadOnly, 100));
});

function enforceReadOnly() {
    // 1. Selector for all preview containers (Timeline versions + Free Input tabs)
    const containers = document.querySelectorAll('.version-content, .sheet-pane, #audit-editor-container');
    
    containers.forEach(container => {
        // A. Disable ContentEditable
        container.setAttribute('contenteditable', 'false');
        container.querySelectorAll('[contenteditable]').forEach(el => el.setAttribute('contenteditable', 'false'));
        
        // B. Disable Inputs
        container.querySelectorAll('input, textarea, select').forEach(el => el.disabled = true);
        
        // C. Visual Cues inside tables
        container.querySelectorAll('td').forEach(td => {
            td.style.cursor = 'default'; 
            td.onclick = null; // Remove inline click handlers if any
        });
    });
}

function renderSideComments() {
    const editor = document.getElementById('audit-editor-container');
    const commentsList = document.getElementById('comment-list');
    
    if (!editor || !commentsList) return;
    
    commentsList.innerHTML = '';
    
    const comments = editor.querySelectorAll('span[data-comment-id]');
    
    // Use getBoundingClientRect for absolute positioning relative to the container
    const containerRect = commentsList.parentElement.getBoundingClientRect();
    const editorRect = editor.getBoundingClientRect();
    const containerScrollTop = commentsList.parentElement.scrollTop;
    
    // Timeline View (Newest First)
    commentsList.style.position = 'static';
    
    const uniqueComments = [];
    const processedIds = new Set();
    
    comments.forEach(commentSpan => {
        const id = commentSpan.getAttribute('data-comment-id');
        if (processedIds.has(id)) return;
        processedIds.add(id);
        
        uniqueComments.push({
            id: id,
            text: commentSpan.getAttribute('data-comment-text') || '',
            user: commentSpan.getAttribute('data-comment-user') || 'Audit Log',
            time: commentSpan.getAttribute('data-comment-time') || '',
            timestamp: parseInt(id.replace('c', '')) || 0,
            element: commentSpan
        });
    });
    
    // Sort Newest First
    uniqueComments.sort((a, b) => b.timestamp - a.timestamp);
    
    uniqueComments.forEach(c => {
        // Create Card
        const card = document.createElement('div');
        card.className = 'comment-card';
        card.setAttribute('data-for', c.id);
        
        // Static Styling
        card.style.position = 'relative';
        card.style.marginBottom = '15px';
        card.style.background = 'white';
        card.style.border = '1px solid #e2e8f0';
        card.style.borderRadius = '12px';
        card.style.padding = '16px';
        card.style.boxShadow = '0 4px 6px rgba(0,0,0,0.02)';
        
        // New Design HTML
        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; background:#eff6ff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; color:#3b82f6; font-weight:bold; border:1px solid #dbeafe;">
                        ${c.user.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#1e293b;">${c.user}</div>
                        <div style="font-size:11px; color:#94a3b8;">${c.time}</div>
                    </div>
                </div>
            </div>
            
             <!-- Comment Body Bubble -->
            <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:12px; font-size:14px; color:#334155; line-height:1.6;">
                ${c.text}
            </div>
        `;
        
        // Interaction: Click to Scroll to Text
        card.addEventListener('click', () => {
             // 1. Cross-Tab Navigation
             const parentSheet = c.element.closest('.sheet-pane');
             if (parentSheet && parentSheet.style.display === 'none') {
                 const sheetId = parentSheet.id;
                 let tabBtn = document.querySelector(`button[onclick*="'${sheetId}'"]`);
                 
                 // Try Free Input Tab Button
                 if (!tabBtn && sheetId.startsWith('tab-')) {
                     const idx = sheetId.replace('tab-', '');
                     tabBtn = document.getElementById(`tab-btn-${idx}`);
                 }

                 if (tabBtn) tabBtn.click();
             }

             // 2. Scroll and Blink
             setTimeout(() => {
                 c.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 
                 c.element.classList.remove('blink-highlight');
                 void c.element.offsetWidth; // Trigger reflow
                 c.element.classList.add('blink-highlight');

                 c.element.classList.add('blink-highlight');

                 document.querySelectorAll('.comment-card').forEach(x => {
                     x.style.borderColor = '#e2e8f0'; 
                     x.style.backgroundColor = 'white';
                     x.style.transform = 'scale(1)';
                 });
                 card.style.borderColor = '#ef4444';
                 card.style.backgroundColor = '#fef2f2';
                 card.style.transform = 'scale(1.02)';
             }, 100);
        });

        commentsList.appendChild(card);
    });
}

function updateSheetTabBadges() {
    // 1. Clear existing badges
    document.querySelectorAll('.tab-badge-dot').forEach(el => el.remove());
    
    // 2. Scan Sheets for Comments
    document.querySelectorAll('.sheet-pane').forEach(pane => {
        const hasComments = pane.querySelector('span[style*="color: rgb(239, 68, 68)"], span[style*="color:#ef4444"], span.inline-comment');
        
        if (hasComments) {
            const sheetId = pane.id;
            let btn = document.querySelector(`button[onclick*="'${sheetId}'"]`);
            
            // Try Free Input naming
            if (!btn && sheetId.startsWith('tab-')) {
                const idx = sheetId.replace('tab-', '');
                btn = document.getElementById(`tab-btn-${idx}`);
            }

            if (btn) {
                if (!btn.querySelector('.tab-badge-dot')) {
                    const dot = document.createElement('span');
                    dot.className = 'tab-badge-dot';
                    dot.style.cssText = `
                        display: inline-flex; 
                        justify-content: center; 
                        align-items: center; 
                        width: 16px; 
                        height: 16px; 
                        background: #ef4444; 
                        color: white;
                        font-size: 10px;
                        font-weight: bold;
                        border-radius: 50%; 
                        margin-left: 6px; 
                        vertical-align: middle;
                    `;
                    dot.innerText = '!';
                    dot.title = "Review Notes Inside";
                    btn.appendChild(dot);
                }
            }
        }
    });
}
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>
