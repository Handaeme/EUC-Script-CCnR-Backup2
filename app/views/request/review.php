<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

// Determine Mode
$isFileUpload = ($request['mode'] === 'FILE_UPLOAD');
?>

<style>
/* Global Box Sizing */
*, *::before, *::after { box-sizing: border-box; }

/* CSS for Excel Preview Tabs */
.sheet-tabs-nav { display: flex; flex-wrap: wrap; border-bottom: 1px solid #ccc; background: #f1f1f1; }
.sheet-tabs-nav::-webkit-scrollbar { display: none; }
.btn-sheet { border: 1px solid #ccc; border-bottom: none; background: #e0e0e0; padding: 8px 16px; cursor: pointer; font-size: 13px; margin-right: 2px; }
.btn-sheet.active { background: #fff; font-weight: bold; border-top: 2px solid var(--primary-red); }
.sheet-pane { 
    padding: 15px; 
    background: #fff; 
    border: 1px solid #ccc; 
    border-top: none; 
    overflow: auto; 
    position: relative;
    width: 100%;
    box-sizing: border-box;
}

/* Plain Text Editor Styles for Review */
.review-editor {
    width: 100%; min-height: 400px; max-height: 600px; padding: 15px; border: 1px solid #ccc; border-radius: 4px;
    font-family: 'Inter', system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.6;
    resize: vertical; outline: none; background: #fff; color: #333;
    overflow: auto !important; max-width: 100%; display: block; box-sizing: border-box;
}
.review-editor:focus { border-color: var(--primary-red); }

/* Blink Animation for Comment Navigation */
/* Inline Comment Style (Enforced) */
.inline-comment {
    background-color: #fef08a !important; /* Force Yellow */
    cursor: pointer;
    border-bottom: 2px solid #eab308;
    transition: background-color 0.3s;
}
.inline-comment:hover {
    background-color: #fde047 !important; /* Darker Yellow on Hover */
}

/* Blink Animation for Comment Navigation */
/* Only animate Outline and Shadow, NEVER background (handled by class) */
@keyframes blink-animation {
    0% { outline: 3px solid #eab308; box-shadow: 0 0 10px rgba(234, 179, 8, 0.5); transform: scale(1.02); z-index: 10; position: relative; }
    50% { outline: 3px solid #eab308; box-shadow: 0 0 15px rgba(234, 179, 8, 0.7); }
    100% { outline: 3px solid transparent; box-shadow: none; transform: scale(1); z-index: auto; position: static; }
}
.blink-highlight {
    animation: blink-animation 1.5s ease-out forwards;
}

/* Real-Time Revision Styles */
.revision-span {
    color: var(--primary-red) !important;
}
.revision-span.draft {
    
}
.comment-card.draft {
    border: 1px dashed #ef4444 !important;
    background: #fef2f2 !important;
    opacity: 0.95;
}
/* Global Modal Styles (Sync with edit.php) */
@keyframes modalFadeIn {
    from { opacity:0; transform:scale(0.9) translateY(-20px); }
    to { opacity:1; transform:scale(1) translateY(0); }
}
@keyframes warningPulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}
.show { display: flex !important; }

/* PRINT STYLES */
/* PRINT STYLES */
@media print {
    @page { size: A4; margin: 0; }
    body * { visibility: hidden; }
    #print-area, #print-area * { visibility: visible; }
    #print-area {
        display: block !important;
        position: absolute;
        left: 0;
        top: 0;
        width: 210mm;
        min-height: 297mm;
        padding: 15mm;
        background: white;
        color: black; /* Force black text */
        font-family: 'Times New Roman', Times, serif; /* Formal Font */
    }
    
    /* Hide specific non-print elements explicitly */
    .no-print { display: none !important; }

    /* Layout Utilities for Print - FORMAL B&W */
    .print-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; border-bottom: 3px double #000; padding-bottom: 15px; }
    .print-logo { max-height: 110px; } /* Logo Enlarged Further */
    .print-title { font-size: 22px; font-weight: bold; text-transform: uppercase; margin-top: 10px; letter-spacing: 1px; }
    
    .print-section { margin-bottom: 25px; }
    .print-section-title { font-size: 14px; font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000; text-transform: uppercase; padding-bottom: 2px; }
    
    .print-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px; }
    .print-label { font-weight: bold; color: #000; } /* Black labels */
    
    .print-content-box { border: 1px solid #000; padding: 15px; font-size: 12px; white-space: pre-wrap; word-wrap: break-word; line-height: 1.6; text-align: justify; }
    
    /* Timeline Table - Simple Black Borders */
    .print-timeline-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px; }
    .print-timeline-table th, .print-timeline-table td { border: 1px solid #000; padding: 6px; text-align: left; }
    .print-timeline-table th { background: transparent; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #000; }

    /* Approval Columns - Formal Box */
    .approval-container { display: flex; justify-content: space-between; gap: 15px; margin-top: 50px; }
    .approval-box { flex: 1; border: 1px solid #000; padding: 0; display: flex; flex-direction: column; height: 140px; }
    .approval-title { background: transparent; border-bottom: 1px solid #000; padding: 8px; text-align: center; font-weight: bold; font-size: 11px; text-transform: uppercase; }
    .approval-sign { flex: 1; display: flex; align-items: flex-end; justify-content: center; padding: 10px; }
    .approval-line { width: 90%; border-bottom: 1px solid #000; text-align: center; font-size: 10px; padding-top: 5px; }

    /* Footer */
    .print-footer { position: fixed; bottom: 10mm; left: 15mm; right: 15mm; font-size: 10px; color: #000; border-top: 1px solid #000; padding-top: 5px; display: flex; justify-content: space-between; font-style: italic; }
}
</style>

<!-- SUCCESS/ERROR MODAL -->
<div id="successModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:32px; max-width:400px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:modalFadeIn 0.3s ease;">
        <div id="modalIcon" style="width:64px; height:64px; background:#dcfce7; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <h3 id="modalTitle" style="color:#1e293b; font-size:20px; font-weight:700; margin:0 0 10px 0;">Success!</h3>
        <p id="modalMessage" style="color:#64748b; font-size:14px; line-height:1.6; margin:0;">Operation completed successfully.</p>
        <button id="modalBtn" onclick="closeModal()" style="display:none; background:var(--primary-red); color:white; border:none; padding:12px 32px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer; box-shadow:0 2px 8px rgba(211,47,47,0.3); transition:all 0.2s; margin-top:20px;">OK</button>
    </div>
</div>

<!-- WARNING/CONFIRM MODAL -->
<div id="confirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:32px; max-width:450px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.3); animation:modalFadeIn 0.3s ease;">
        <div style="width:64px; height:64px; background:#fef3c7; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; animation:warningPulse 2s ease-in-out infinite;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <h3 id="confirmTitle" style="color:#1e293b; font-size:20px; font-weight:700; margin:0 0 10px 0;">⚠️ Perhatian</h3>
        <div id="confirmMessage" style="background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid #f59e0b; padding:15px; border-radius:8px; margin-bottom:24px; text-align:left; color:#475569; font-size:14px; line-height:1.6;">
        </div>
        <div style="display:flex; gap:12px; justify-content:center;">
            <button id="confirmCancelBtn" style="flex:1; background:white; color:#64748b; border:1px solid #cbd5e1; padding:12px 24px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; transition:all 0.2s;">Batal</button>
            <button id="confirmOkBtn" style="flex:1; background:#f59e0b; color:white; border:none; padding:12px 24px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; box-shadow:0 4px 12px rgba(245, 158, 11, 0.2); transition:all 0.2s;">Ya, Kirim</button>
        </div>
    </div>
</div>


<div class="main">
    <div class="header-box" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h2 style="color:var(--primary-red); margin:0;">Review Request</h2>
            <p style="color:var(--text-secondary);">Script No: <strong><?php echo htmlspecialchars($request['script_number']); ?></strong> (<?php echo $request['mode']; ?> Mode)</p>
        </div>
        <div>
             <button onclick="printTicketScript()" class="btn-print" style="margin-right:10px; padding:8px 16px; background:#4f46e5; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600; display:inline-flex; align-items:center;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Final Ticket Script
            </button>
             <a href="index.php" class="btn-cancel" style="text-decoration:none; padding:8px 16px; border:1px solid #ccc; border-radius:4px; color:#555; background:white;">Back to Dashboard</a>
        </div>
    </div>

    <div class="grid-container" style="display:grid; grid-template-columns: 3fr 1fr; gap:20px;">
        
        <!-- LEFT COLUMN: SCRIPT CONTENT -->
        <div class="card" style="background:white; padding:20px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1); min-width:0;">
            
            <!-- Request Metadata Card -->
            <div style="background:#fff; padding:20px; border-radius:8px; border:1px solid #ddd; margin-bottom:20px; font-size:14px; color:#333; font-family: system-ui, -apple-system, sans-serif;">
                <h5 style="margin:0 0 15px 0; color:#000; border-bottom:1px solid #eee; padding-bottom:8px; font-size:16px; font-weight:600;">Request Information</h5>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <strong style="color:#555; font-size:12px;">Ticket ID</strong><br>
                        <span style="color:#d32f2f; font-weight:bold;">
                            <?php 
                                $dispID = $request['ticket_id'];
                                if (is_numeric($dispID)) $dispID = sprintf("SC-%04d", $dispID);
                                echo htmlspecialchars($dispID);
                            ?>
                        </span>
                    </div>
                    <div><strong style="color:#555; font-size:12px;">Script Number</strong><br><span style="font-weight:600;"><?php echo htmlspecialchars($request['script_number']); ?></span></div>
                    
                    <div><strong style="color:#555; font-size:12px;">Title</strong><br><?php echo htmlspecialchars($request['title']); ?></div>
                    <div>
                        <strong style="color:#555; font-size:12px;">Created Date</strong><br>
                        <?php 
                            if ($request['created_at'] instanceof DateTime) {
                                echo $request['created_at']->format('d M Y, H:i');
                            } else {
                                echo date('d M Y, H:i', strtotime($request['created_at']));
                            }
                        ?>
                    </div>
                    
                    <div><strong style="color:#555; font-size:12px;">Jenis</strong><br><?php echo htmlspecialchars($request['jenis']); ?></div>
                    <div><strong style="color:#555; font-size:12px;">Kategori</strong><br><?php echo htmlspecialchars($request['kategori']); ?></div>
                    <div><strong style="color:#555; font-size:12px;">Product</strong><br><?php echo htmlspecialchars($request['produk']); ?></div>
                    
                    <div style="grid-column: span 2;"><strong style="color:#555; font-size:12px;">Media Channels</strong><br>
                        <?php echo htmlspecialchars(implode('; ', array_map('trim', explode(',', $request['media'])))); ?>
                    </div>
                </div>
            </div>



            <h4 style="border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:20px; color:#333; display:flex; justify-content:space-between; align-items:center;">
                <span>Script Content (Editable)</span>
                <span style="font-size:12px; color:#888; font-weight:normal;">Approver can refine the script before final approval</span>
            </h4>

            <!-- MODE: FILE UPLOAD -->
            <?php if ($isFileUpload): ?>
                <div style="margin-bottom:10px; font-size:12px; color:#666; font-style:italic; display:block;">
                    <div style="margin-bottom:5px;">* Editing content in File Upload mode updates the preview for all media channels. Original content is Black, new edits will be Red automatically.</div>
                    
                    <!-- Manual Color Toolbar -->
                    <style>
                        .btn-tool {
                            background: white; border:1px solid #cbd5e1; border-radius:4px; padding:6px 10px; 
                            cursor:pointer; font-weight:600; font-size:12px; transition:all 0.2s;
                            display:flex; align-items:center; gap:6px; color: #64748b;
                        }
                        .btn-tool:hover { background: #f8fafc; border-color: #94a3b8; }
                        
                        /* Active States */
                        .btn-tool.active-black { 
                            background: #333 !important; color: white !important; border-color: #000 !important;
                            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
                        }
                        .btn-tool.active-red { 
                            background: var(--primary-red) !important; color: white !important; border-color: #dc2626 !important;
                            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
                        }
                    </style>
                    <div class="editor-toolbar" style="background:#f1f5f9; padding:8px 12px; border-radius:8px; border:1px solid #e2e8f0; display:flex; gap:12px; align-items:center; justify-content: flex-end;">
                         
                         <!-- ENABLE EDIT BUTTON (HIDDEN - Enabled by default) -->
                         <div id="edit-controls" style="display:none;">
                             <button type="button" onclick="enableEditMode()" style="background:var(--primary-red); color:white; border:none; border-radius:4px; padding:6px 12px; cursor:pointer; font-weight:bold; font-size:12px; display:flex; align-items:center; gap:6px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Enable Editing
                             </button>
                         </div>

                         <!-- COLOR TOOLS (Visible by default) -->
                         <div id="color-tools" style="display:flex; align-items:center; gap:12px;">
                             <!-- Hidden Mode Indicator (Functionality Preserved) -->
                             <span style="display:none; font-size:11px; font-weight:bold; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Mode:</span>
                             
                             <button type="button" id="btn-red" onclick="setMode('RED')" class="btn-tool active-red" style="display:none;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                </svg>
                                Edit Red
                             </button>
                             
                             <!-- Finish Button Removed as requested -->

                             <button type="button" onclick="performUndo()" style="background:white; color:#64748b; border:1px solid #cbd5e1; border-radius:4px; padding:6px; cursor:pointer;" title="Undo (Ctrl+Z)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"></path><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"></path></svg>
                             </button>
                             <button type="button" onclick="performRedo()" style="background:white; color:#64748b; border:1px solid #cbd5e1; border-radius:4px; padding:6px; cursor:pointer;" title="Redo (Ctrl+Y)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7v6h-6"></path><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 3.7"></path></svg>
                             </button>

                             <div style="width:1px; height:20px; background:#e2e8f0; margin:0 5px;"></div>

                             <button type="button" onclick="addComment()" id="btn-comment" style="background:linear-gradient(135deg, #eab308 0%, #f59e0b 100%); color:#fff; border:none; border-radius:6px; padding:8px 14px; cursor:pointer; font-weight:600; font-size:11px; display:flex; align-items:center; gap:6px; box-shadow:0 2px 4px rgba(234, 179, 8, 0.2); transition:all 0.2s ease;" title="Add Comment / Highlight" onmouseenter="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(234, 179, 8, 0.3)';" onmouseleave="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(234, 179, 8, 0.2)';">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                                </svg>
                                Comment
                             </button>
                         </div>
                    </div>
                </div>
                <!-- Review Content Area with Split View -->
                <!-- Review Content Area with Split View -->
                <!-- EDITOR CONTAINER -->
                <!-- EDITOR CONTAINER -->
                <div id="unified-file-editor" class="review-editor">
                    <?php 
                        // If file upload and we have content
                        if ($isFileUpload && !empty($content)) {
                             // Check if content already contains tabs (Pre-formatted HTML from Editor/FileHandler)
                             // This handles both "Single Formatted Row" (Fixed) and "Multiple Duplicate Media Rows" (Corrupt)
                             $hasPrebuiltTabs = false;
                             foreach ($content as $row) {
                                 if (strpos($row['content'], 'sheet-tabs-nav') !== false) {
                                     $hasPrebuiltTabs = true;
                                     break;
                                 }
                             }

                             if ($hasPrebuiltTabs) {
                                 // Case A: Content is already a full container (with internal tabs).
                                 // Just render the FIRST one. Ignores duplicates if they exist (Corrupt Data Fix).
                                 echo $content[0]['content'];
                             } elseif (count($content) > 1) {
                                 // Case B: Content is separate sheets (Tables only). Render our own tabs.
                                 // Render Tabs
                                 echo '<div class="sheet-tabs-nav">';
                                 foreach ($content as $idx => $row) {
                                     $active = ($idx === 0) ? 'active' : '';
                                     $media = htmlspecialchars($row['media'] ?? 'Part ' . ($idx+1));
                                     // Changed onclick to openMediaTab
                                     echo "<div id='tab-media-btn-$idx' class='btn-sheet btn-media-tab $active' onclick=\"openMediaTab(event, 'tab-media-$idx')\">$media</div>";
                                 }
                                 echo '</div>';
                                 
                                 // Render Panes (Using media-pane class)
                                 foreach ($content as $idx => $row) {
                                     $display = ($idx === 0) ? 'block' : 'none';
                                     echo "<div id='tab-media-$idx' class='media-pane review-tab-content' style='display:$display'>";
                                     echo $row['content']; 
                                     echo "</div>";
                                 }
                                 
                                 // Inline JS for Media Tabs to avoid global scope conflict
                                 echo "<script>
                                    function openMediaTab(evt, tabId) {
                                        // Hide all media panes
                                        var panes = document.getElementsByClassName('media-pane');
                                        for (var i = 0; i < panes.length; i++) {
                                            panes[i].style.display = 'none';
                                        }
                                        // Deactivate buttons
                                        var btns = document.getElementsByClassName('btn-media-tab');
                                        for (var i = 0; i < btns.length; i++) {
                                            btns[i].classList.remove('active');
                                        }
                                        // Show target
                                        document.getElementById(tabId).style.display = 'block';
                                        evt.currentTarget.classList.add('active');
                                    }
                                 </script>";
                             } else {
                                 // Case C: Single raw sheet
                                 echo $content[0]['content']; 
                             }
                        }
                    ?>
                </div>

            <?php else: ?>
            <!-- MODE: FREE INPUT (Plain Text Tabs) -->
                <div style="background:#fee2e2; padding:5px; font-size:10px; color:red; margin-bottom:5px;">
                    DEBUG: Mode=<?php echo $request['mode']; ?>, Content Count=<?php echo count($content); ?>
                </div>
                <!-- Toolbar for Free Input -->
                <div style="margin-bottom:10px;">
                    <div class="editor-toolbar" style="background:#f1f5f9; padding:8px 12px; border-radius:8px; border:1px solid #e2e8f0; display:flex; gap:12px; align-items:center; justify-content: flex-end;">
                        <button type="button" onmousedown="event.preventDefault();" onclick="performUndo()" style="background:white; color:#64748b; border:1px solid #cbd5e1; border-radius:4px; padding:6px; cursor:pointer;" title="Undo (Ctrl+Z)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"></path><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"></path></svg>
                        </button>
                        <button type="button" onmousedown="event.preventDefault();" onclick="performRedo()" style="background:white; color:#64748b; border:1px solid #cbd5e1; border-radius:4px; padding:6px; cursor:pointer;" title="Redo (Ctrl+Y)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7v6h-6"></path><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 3.7"></path></svg>
                        </button>
                        
                        <div style="width:1px; height:20px; background:#e2e8f0; margin:0 5px;"></div>
                        
                        <button type="button" onmousedown="event.preventDefault();" onclick="addComment()" id="btn-comment-free" style="background:linear-gradient(135deg, #eab308 0%, #f59e0b 100%); color:#fff; border:none; border-radius:6px; padding:8px 14px; cursor:pointer; font-weight:600; font-size:11px; display:flex; align-items:center; gap:6px; box-shadow:0 2px 4px rgba(234, 179, 8, 0.2); transition:all 0.2s ease;" title="Add Comment / Highlight">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                            </svg>
                            Comment
                        </button>
                    </div>
                </div>
                
                <div class="sheet-tabs-nav" style="margin-bottom:0;">
                    <?php foreach ($content as $idx => $row): ?>
                        <div id="tab-btn-review-<?php echo $idx; ?>" class="btn-sheet <?php echo $idx===0?'active':''; ?>" onclick="openTab(event, 'tab-<?php echo $idx; ?>')">
                            <?php echo htmlspecialchars($row['media'] ?? 'Content'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($content as $idx => $row): ?>
                <div id="tab-<?php echo $idx; ?>" class="sheet-pane review-tab-content" style="display:<?php echo $idx===0?'block':'none'; ?>;">
                    <div 
                        class="review-editor free-input-editor" 
                        id="free-editor-<?php echo $idx; ?>"
                        data-id="<?php echo $row['id']; ?>"
                        data-media="<?php echo htmlspecialchars($row['media'] ?? 'Content'); ?>"
                        contenteditable="true"
                        style="overflow:auto !important; white-space:pre-wrap; word-wrap:break-word; padding:10px;"><?php echo trim($row['content']); ?></div>
        


        </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <!-- RIGHT COLUMN: APPROVAL FORM -->
        <div class="panel-column">
            
            <!-- Comment Sidebar (Moved Here) -->
            <div id="comment-sidebar" style="background:#fff; border:1px solid #ddd; border-radius:8px; padding:15px; margin-bottom:20px; display:none;">
                 <h4 style="margin:0 0 15px 0; color:#333; font-size:14px; font-weight:bold; border-bottom:1px solid #eee; padding-bottom:8px;">
                    Review Comments
                 </h4>
                 <div id="comment-list" style="position:relative;"></div>
            </div>

            <!-- Approval Form -->
            <div class="card" style="background:white; padding:16px; border-radius:10px; box-shadow:0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; border-top: 4px solid var(--primary-red);">
                <h4 style="margin:0 0 15px 0; color:#1e293b; font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-red);"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Approval Decision
                </h4>
                
                <form id="approvalForm">
                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                    
                    <div class="form-group" style="margin-bottom:15px;">
                        <label class="form-label" style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Decision</label>
                        <select id="decision" name="decision" class="form-select" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background-color:#f8fafc; font-weight:600; font-size:13px; color:#334155; cursor:pointer;" onchange="toggleRemarks()">
                            <option value="APPROVE">Approve (Acc)</option>
                            <option value="REVISE">Revise (Perbaikan)</option>
                            <option value="REJECT">Reject (Tolak)</option>
                        </select>
                    </div>

                    <div id="remarksGroup" style="display:none; margin-top:15px;">
                        <label class="form-label" style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Remarks / Notes <span style="color:red">*</span></label>
                        <textarea name="remarks" id="remarks" rows="3" class="form-control" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; background-color:#f8fafc; font-family:inherit; font-size:13px; resize:none;" placeholder="Reason..."></textarea>
                    </div>

                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role_code'] === 'PROCEDURE'): ?>
                    
                    <!-- SHEETJS FOR EXCEL DOWNLOAD -->
                    <script src="public/assets/js/xlsx.full.min.js"></script> 
                    <!-- Note: Jika file lokal belum ada, gunakan CDN sementara: -->
                    <!-- <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script> -->

                    <!-- PROCEDURE ONLY: Document Upload & Download -->
                    <div style="margin-top:20px; padding:15px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:10px;">
                        <h5 style="margin:0 0 12px 0; color:#475569; font-size:12px; font-weight:700; text-transform:uppercase; display:flex; align-items:center; justify-content:space-between;">
                            <span>Procedure Actions</span>
                            <span style="background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:4px; font-size:10px;">Role: Procedure</span>
                        </h5>
                        
                        <!-- EXCEL DOWNLOAD BUTTON -->
                        <div style="margin-bottom:15px; padding-bottom:15px; border-bottom:1px solid #cbd5e1;">
                            <button type="button" onclick="downloadReviewExcel()" style="width:100%; background:#0f766e; color:white; border:none; padding:10px; border-radius:6px; cursor:pointer; font-weight:600; font-size:13px; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 2px 4px rgba(15, 118, 110, 0.2);">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Download Excel (Multiple Sheets)
                            </button>
                            <div style="text-align:center; font-size:10px; color:#64748b; margin-top:5px;">Download script content for verification</div>
                        </div>

                        <h5 style="margin:0 0 12px 0; color:#475569; font-size:12px; font-weight:700; text-transform:uppercase; display:flex; align-items:center; gap:6px;">
                            Revision Documents
                        </h5>
                        
                        <div style="margin-bottom:12px;">
                            <label style="display:block; margin-bottom:4px; font-weight:700; font-size:10px; color:#64748b;">Legal Document <span style="color:red; font-size:11px;">* (Wajib)</span></label>
                            <input type="file" id="file_legal" class="form-control" style="font-size:11px; padding:4px;">
                            <div id="status_legal" style="font-size:10px; margin-top:2px;"></div>
                        </div>

                        <div style="margin-bottom:12px;">
                            <label style="display:block; margin-bottom:4px; font-weight:700; font-size:10px; color:#64748b;">CX Document <span style="color:red; font-size:11px;">* (Wajib)</span></label>
                            <input type="file" id="file_cx" class="form-control" style="font-size:11px; padding:4px;">
                            <div id="status_cx" style="font-size:10px; margin-top:2px;"></div>
                        </div>

                        <div style="margin-bottom:12px;">
                            <label style="display:block; margin-bottom:4px; font-weight:700; font-size:10px; color:#64748b;">Legal Syariah</label>
                            <input type="file" id="file_syariah" class="form-control" style="font-size:11px; padding:4px;">
                            <div id="status_syariah" style="font-size:10px; margin-top:2px;"></div>
                        </div>

                        <div style="margin-bottom:12px;">
                            <label style="display:block; margin-bottom:4px; font-weight:700; font-size:10px; color:#64748b;">LPP Document</label>
                            <input type="file" id="file_lpp" class="form-control" style="font-size:11px; padding:4px;">
                            <div id="status_lpp" style="font-size:10px; margin-top:2px;"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top:20px; display:flex; flex-direction:column; gap:8px;">
                        <button type="button" onclick="submitDecision()" style="width:100%; padding:11px; background:linear-gradient(135deg, var(--primary-red) 0%, #be123c 100%); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:14px; box-shadow:0 2px 4px rgba(220, 38, 38, 0.15);">
                            Submit Decision
                        </button>
                        <button type="button" onclick="saveDraft()" style="width:100%; padding:8px; background:#fff; color:#475569; border:1px solid #e2e8f0; border-radius:8px; font-weight:600; cursor:pointer; font-size:13px;">
                            Save Draft
                        </button>
                    </div>
                </form>
            </div>
            </div>
    </div>
</div>

<!-- PRINT AREA (Hidden by default, Visible on Print) -->
<div id="print-area" style="display:none;">
    <div class="print-header">
        <div>
            <!-- Placeholder for Company Logo -->
            <img src="public/assets/images/logo.png" alt="Company Logo" class="print-logo" onerror="this.style.display='none'; document.getElementById('print-logo-text').style.display='block';">
            <div id="print-logo-text" style="display:none; font-weight:bold; font-size:18px;">COMPANY LOGO</div>
        </div>
        <div style="text-align:right;">
            <div class="print-title">Final Ticket Script</div>
            <div style="font-size:12px; margin-top:5px;">Ref: <span id="p-script-id"></span></div>
            <div style="font-size:12px;">Generated: <span id="p-generated-date"></span></div>
        </div>
    </div>

    <!-- Request Information -->
    <div class="print-section">
        <div class="print-section-title">Request Information</div>
        <div class="print-grid">
            <div style="display:grid; grid-template-columns: 100px 1fr; gap:5px;">
                <div class="print-label">Script Number:</div> <div id="p-script-num"></div>
                <div class="print-label">Ticket ID:</div> <div id="p-ticket-id"></div>
                <div class="print-label">Title:</div> <div id="p-title"></div>
                <div class="print-label">Product:</div> <div id="p-product"></div>
            </div>
            <div style="display:grid; grid-template-columns: 100px 1fr; gap:5px;">
                <div class="print-label">Jenis:</div> <div id="p-jenis"></div>
                <div class="print-label">Kategori:</div> <div id="p-kategori"></div>
                <div class="print-label">Media Channels:</div> <div id="p-media"></div>
                <div class="print-label">Created Date:</div> <div id="p-created"></div>
            </div>
        </div>
    </div>

    <!-- Script Content -->
    <div class="print-section">
        <div class="print-section-title">Script Content</div>
        <div id="p-content-container">
            <!-- Content will be injected here -->
        </div>
    </div>

    <!-- Timeline / History -->
    <div class="print-section">
        <div class="print-section-title">Timeline & History</div>
        <table class="print-timeline-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>User / Role</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Created</td>
                    <td><span id="p-maker-name"></span><br><small id="p-maker-role">(Maker)</small></td>
                    <td id="p-maker-date"></td>
                    <td>Submitted</td>
                </tr>
                <tr>
                    <td>Reviewed</td>
                    <td><span id="p-spv-name">(Pending SPV)</span><br><small id="p-spv-role">(Supervisor)</small></td>
                    <td id="p-spv-date">-</td>
                    <td id="p-spv-status">Pending</td>
                </tr>
                <tr>
                    <td>Checked</td>
                    <td><span id="p-pic-name">(Pending PIC)</span><br><small id="p-pic-role">(PIC)</small></td>
                    <td id="p-pic-date">-</td>
                    <td id="p-pic-status">Pending</td>
                </tr>
                 <tr>
                    <td>Finalized</td>
                    <td><span id="p-proc-name"><?php echo htmlspecialchars($_SESSION['user']['fullname']); ?></span><br><small id="p-proc-role">(Procedure)</small></td>
                    <td id="p-proc-date">-</td>
                    <td id="p-proc-status">In Review</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Approval Columns -->
    <div class="approval-container">
        <div class="approval-box">
            <div class="approval-title">LEGAL</div>
            <div class="approval-sign" id="p-sign-legal">
                <div class="approval-line">Name & Signature</div>
            </div>
        </div>
        <div class="approval-box">
            <div class="approval-title">CX</div>
            <div class="approval-sign" id="p-sign-cx">
                 <div class="approval-line">Name & Signature</div>
            </div>
        </div>
        <div class="approval-box">
            <div class="approval-title">LEGAL SYARIAH</div>
            <div class="approval-sign" id="p-sign-syariah">
                 <div class="approval-line">Name & Signature</div>
            </div>
        </div>
         <div class="approval-box">
            <div class="approval-title">LPP</div>
            <div class="approval-sign" id="p-sign-lpp">
                 <div class="approval-line">Name & Signature</div>
            </div>
        </div>
    </div>

    <div class="print-footer">
        <div>Printed by System</div>
        <div>Page <span class="page-number"></span></div>
    </div>
</div>

<!-- Comment Modal -->
<div id="comment-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; padding:20px; border-radius:8px; width:400px; max-width:90%; box-shadow:0 4px 12px rgba(0,0,0,0.15); font-family:'Inter', sans-serif;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 style="margin:0; font-size:16px; font-weight:700; color:#333;">Add Comment</h3>
            <button onclick="closeCommentModal()" style="background:none; border:none; color:#888; cursor:pointer; font-size:18px;">&times;</button>
        </div>
        
        <textarea id="comment-input" style="width:100%; box-sizing:border-box; height:100px; padding:10px; border:1px solid #ccc; border-radius:4px; font-family:inherit; margin-bottom:15px; font-size:14px; resize:vertical; outline:none; line-height:1.5;" placeholder="Type correction here..."></textarea>
        
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button onclick="closeCommentModal()" style="padding:8px 16px; border:1px solid #ddd; background:white; color:#555; border-radius:4px; cursor:pointer; font-size:13px; font-weight:500;">Cancel</button>
            <button onclick="submitComment()" style="padding:8px 20px; background:#eab308; color:white; border:none; border-radius:4px; font-weight:600; cursor:pointer; font-size:13px;">Save</button>
        </div>
    </div>
</div>

<script>
const IS_FILE_UPLOAD = <?php echo $isFileUpload ? 'true' : 'false'; ?>;
const SERVER_CONTENT = <?php echo json_encode($content); ?>;
<?php
// Extract real filename for JS
$realFilename = 'Document.docx'; // Default
if (!empty($files)) {
    foreach ($files as $f) {
        if ($f['file_type'] === 'TEMPLATE') {
            $realFilename = $f['original_filename'];
            break;
        }
    }
}
// Extract Review Evidence (Array of files)
$reviewEvidence = [
    'LEGAL' => [],
    'CX' => [],
    'SYARIAH' => [],
    'LPP' => []
];
if (!empty($files)) {
    foreach ($files as $f) {
        if (array_key_exists($f['file_type'], $reviewEvidence)) {
            $reviewEvidence[$f['file_type']][] = [
                'id' => $f['id'],
                'filename' => $f['original_filename']
            ];
        }
    }
}
?>
const REVIEW_EVIDENCE = <?php echo json_encode($reviewEvidence); ?>;
const CURRENT_USER_NAME = "<?php echo htmlspecialchars($_SESSION['user']['fullname'] ?? 'Reviewer'); ?>";
const CURRENT_USER_ROLE = "<?php echo $_SESSION['user']['role_code'] ?? ''; ?>";

// Init Status if files exist
document.addEventListener('DOMContentLoaded', () => {
    // Helper to render list
    const renderList = (type, containerId) => {
        const container = document.getElementById(containerId);
        if(!container) return;
        container.innerHTML = '';
        
        if (REVIEW_EVIDENCE[type] && REVIEW_EVIDENCE[type].length > 0) {
            REVIEW_EVIDENCE[type].forEach(file => {
                const div = document.createElement('div');
                div.style.marginBottom = '2px';
                div.style.display = 'flex';
                div.style.alignItems = 'center';
                div.style.justifyContent = 'space-between';
                div.innerHTML = `
                    <span style="color:green">✅ ${file.filename}</span>
                    <button type="button" onclick="deleteReviewDoc('${type}', '${file.id}', this)" style="background:none; border:none; color:red; cursor:pointer; font-size:10px; margin-left:4px;" title="Delete">❌</button>
                `;
                container.appendChild(div);
            });
        }
    };

    renderList('LEGAL', 'status_legal');
    renderList('LEGAL', 'status_legal');
    renderList('CX', 'status_cx');
    renderList('SYARIAH', 'status_syariah');
    renderList('LPP', 'status_lpp');

    // AUTO-ENABLE EDIT MODE for SPV to ensure Auto-Red Listeners are active
    // This fixes the 'Resubmit -> Auto Red Fails' issue
    if (typeof enableEditMode === 'function') {
        // You might want to restrict this by Status too, but for SPV on review page, 
        // they usually expect to edit.
        if (CURRENT_USER_ROLE === 'SPV' || CURRENT_USER_ROLE === 'PROC') {
             console.log("[DEBUG] Auto-Enabling Edit Mode for Reviewer");
             enableEditMode();
        }
    }
});
function checkValidLawrAttributes() {
    return true;
}

// SHEETJS DOWNLOAD FUNCTION (DOM-BASED - Works for old and new requests)
function downloadReviewExcel() {
    if (typeof XLSX === 'undefined') {
        Swal.fire({
            title: 'Module Missing', 
            html: 'SheetJS libraries not found. <br><small>Please save xlsx.full.min.js in public/assets/js/</small>', 
            icon: 'error'
        });
        return;
    }

    const scriptNum = '<?php echo htmlspecialchars($request['script_number']); ?>';

    // Get sheets from DOM HTML instead of SERVER_CONTENT
    // This works for both old (combined HTML) and new (separate sheets) requests
    let sheets = document.querySelectorAll('.sheet-pane');
    if (sheets.length === 0) {
        // Fallback: try other selectors
        sheets = document.querySelectorAll('.media-tab-pane, [id^="sheet-"]');
    }
    
    if (sheets.length === 0) {
        Swal.fire('Error', 'No content available to download', 'warning');
        return;
    }

    console.log(`Found ${sheets.length} sheet(s) in DOM`);

    // Create new workbook
    const wb = XLSX.utils.book_new();
    let hasContent = false;
    
    sheets.forEach((sheet, index) => {
        // 1. Try data-media attribute (Free Input Mode)
        let sheetName = sheet.getAttribute('data-media');
        
        // 2. Try finding matching tab button (File Upload Mode)
        if (!sheetName && sheet.id) {
            const sheetId = sheet.id;
            const buttons = document.querySelectorAll('.btn-sheet');
            
            for (let btn of buttons) {
                const clickAttr = btn.getAttribute('onclick');
                if (clickAttr && (clickAttr.includes(`'${sheetId}'`) || clickAttr.includes(`"${sheetId}"`))) {
                    sheetName = btn.innerText.trim();
                    break;
                }
            }
            
            // Fallback: Use sheet ID
            if (!sheetName) {
                sheetName = sheetId.replace('sheet-', 'Sheet ');
            }
        }

        // Final fallback
        if (!sheetName) sheetName = `Sheet ${index + 1}`;
        
        // Clean sheet name (Excel limits: 31 chars, no special chars)
        sheetName = sheetName.replace(/[:\\/?*[\]]/g, '');
        if (sheetName.length > 31) sheetName = sheetName.substring(0, 31);
        
        console.log(`Processing sheet: "${sheetName}"`);
        
        // Convert HTML table to sheet
        const table = sheet.querySelector('table');
        let ws;
        
        if (table) {
            ws = XLSX.utils.table_to_sheet(table);
        } else {
            // Text content fallback
            const textContent = sheet.innerText || sheet.textContent || '';
            ws = XLSX.utils.aoa_to_sheet([[textContent]]);
        }
        
        // APPLY BORDERS & STYLING
        if (ws['!ref']) {
            const range = XLSX.utils.decode_range(ws['!ref']);
            for(let R = range.s.r; R <= range.e.r; ++R) {
                for(let C = range.s.c; C <= range.e.c; ++C) {
                    const cell_ref = XLSX.utils.encode_cell({c:C, r:R});
                    if(!ws[cell_ref]) ws[cell_ref] = { t: 's', v: '' }; 

                    if(!ws[cell_ref].s) ws[cell_ref].s = {};
                    
                    // 1. BORDERS
                    ws[cell_ref].s.border = {
                        top: { style: "thin", color: {rgb: "000000"} },
                        bottom: { style: "thin", color: {rgb: "000000"} },
                        left: { style: "thin", color: {rgb: "000000"} },
                        right: { style: "thin", color: {rgb: "000000"} }
                    };
                    
                    // 2. TEXT WRAPPING & ALIGNMENT
                    if(!ws[cell_ref].s.alignment) ws[cell_ref].s.alignment = {};
                    ws[cell_ref].s.alignment.wrapText = true;
                    ws[cell_ref].s.alignment.vertical = 'top';
                    
                    // 3. HEADER STYLING (First Row)
                    if (R === 0) {
                        ws[cell_ref].s.fill = { fgColor: { rgb: "E0E0E0" } };
                        ws[cell_ref].s.font = { bold: true, color: { rgb: "000000" } };
                        ws[cell_ref].s.alignment.horizontal = 'center';
                        ws[cell_ref].s.alignment.vertical = 'center';
                    }
                }
            }
            
            // Set Column Widths
            ws['!cols'] = [
                { wch: 25 }, // A: Process
                { wch: 10 }, // B: Row
                { wch: 25 }, // C: Node
                { wch: 100 }, // D: Script Content
                { wch: 25 }, // E: Next Action
                { wch: 25 }  // F: Error Script/Other
            ];
        }

        XLSX.utils.book_append_sheet(wb, ws, sheetName);
        hasContent = true;
        console.log(`✓ Added sheet: "${sheetName}"`);
    });

    if (!hasContent) {
        Swal.fire('Error', 'No content to download', 'warning');
        return;
    }

    console.log(`Total sheets in workbook: ${wb.SheetNames.length}`);
    console.log('Sheet names:', wb.SheetNames);

    // Download File
    XLSX.writeFile(wb, `Script_${scriptNum}.xlsx`);
    console.log('✓ Download complete!');
}


let currentColorMode = 'RED'; // Legacy Global
let isEditing = false;
let savedRange = null; 
let hasUnsavedChanges = false;
const uploadedDocs = { legal: null, cx: null };

// --- TOGGLE EDIT MODE ---
// --- TOGGLE EDIT MODE ---
function enableEditMode() {
    isEditing = true;
    
    // 1. Initialize Observer FIRST (before attaching)
    if (window.revisionObserver) window.revisionObserver.disconnect();
    window.revisionObserver = new MutationObserver((mutations) => {
        mutations.forEach(mut => {
            let target = mut.target;
            if (target.nodeType === 3) target = target.parentNode;
            
            // Robust Detection: Check for class OR style
            // UPDATED: Use same logic as isStyleRed (inline here to avoid scope issues or need for helper hoisting)
            const c = target.style.color || "";
            const s = c.toLowerCase().replace(/\s/g, ''); 
            const isRed = target && target.nodeType === 1 && (
                target.classList.contains('revision-span') || 
                s === 'red' || s === 'rgb(255,0,0)' || s === '#ef4444' || s === '#ff0000' || s === 'rgb(239,68,68)'
            );

            if (isRed) {
                const revId = target.id || target.getAttribute('data-comment-id');
                if (revId) {
                        let safeText = target.innerText || target.textContent || "";
                        safeText = safeText.replace(/\u200B/g, ''); 
                        updateDraftCard(revId, safeText);
                }
            }
        });
    });

    // 2. UI Unlock - Adaptive
    // GLOBAL DELEGATION: Ensuring we catch input everywhere
    if (!window.hasGlobalEditorListener) {
        document.addEventListener('beforeinput', (e) => {
            if (!isEditing) return;
            const target = e.target;
            // Check if editing inside our known containers
            if (target.closest('.media-pane') || target.closest('#unified-file-editor') || target.closest('.sheet-pane')) {
                handleEditorInput(e);
            }
        });
        window.hasGlobalEditorListener = true;
    }

    const panes = document.querySelectorAll('.media-pane');
    
    if ( panes.length > 0) {
        // Multi-Sheet Mode: Enable specific panes ONLY (Protect Tab Buttons)
        panes.forEach(pane => {
            pane.contentEditable = "true";
            pane.style.pointerEvents = 'auto'; // Ensure clickable
            pane.style.outline = "none"; // Clean look
            // Attach Listener to each pane (Input is ok to keep local, or move to global similarly but handleRealtimeInput is cheap)
            pane.addEventListener('input', handleRealtimeInput);     
        });
        
        // Ensure main container is NOT editable (to lock buttons)
        const editor = document.getElementById('unified-file-editor');
        if (editor) editor.contentEditable = "false"; 

    } else {
        // Single Sheet / Legacy Mode: Enable container
        const editor = document.getElementById('unified-file-editor');
        if(editor) {
            editor.contentEditable = "true";
            editor.style.pointerEvents = 'auto';
            editor.style.opacity = '1';
            editor.style.background = '#fff';
            editor.focus();
            editor.addEventListener('input', handleRealtimeInput);
        }
    }
    
    // Toolbar
    const ec = document.getElementById('edit-controls');
    if(ec) ec.style.display = 'none';
    const ct = document.getElementById('color-tools');
    if(ct) ct.style.display = 'flex';
}

function disableEditMode() {
    isEditing = false;
    
    const panes = document.querySelectorAll('.media-pane');
    if (panes.length > 0) {
        panes.forEach(pane => {
            pane.contentEditable = "false";
            pane.removeEventListener('beforeinput', handleEditorInput);
        });
    }

    const editor = document.getElementById('unified-file-editor');
    if(editor) {
        editor.contentEditable = "false";
        editor.style.pointerEvents = 'none'; // Lock clicks except buttons?
        // Wait, if we pointer-events none the container, buttons might be unclickable if inside?
        // Actually for File Upload mode, usually it's read only. 
        // But in Multi-Sheet, buttons are inside 'unified-file-editor'.
        // If we disable events, tabs won't click.
        // FIX: Only disable events if NO panes (Legacy). If panes exist, keep auto but panes non-editable.
        if (panes.length === 0) {
             editor.style.pointerEvents = 'none';
             editor.style.opacity = '0.8';
             editor.style.background = '#f9f9f9';
        } else {
             editor.style.pointerEvents = 'auto'; // Keep buttons clickable
             editor.style.opacity = '1';
             editor.style.background = 'transparent';
        }
        
        editor.removeEventListener('beforeinput', handleEditorInput);
        editor.removeEventListener('input', handleRealtimeInput);
        if (window.revisionObserver) {
            window.revisionObserver.disconnect();
            window.revisionObserver = null;
        }
    }
    
    // Toolbar
    const ec = document.getElementById('edit-controls');
    if(ec) ec.style.display = 'block';
    const ct = document.getElementById('color-tools');
    if(ct) ct.style.display = 'none';
}

// --- LEGACY COMMENT MODAL ---
function addComment() {
    // For Free Input, always allow comment (no need enable editing)
    const currentMode = IS_FILE_UPLOAD;
    
    if (currentMode && !isEditing) {
        Swal.fire({ title: 'Enable Editing First', text: 'Please click Enable Editing before adding comments.', icon: 'warning' });
        return;
    }
    
    const selection = window.getSelection();
    if (!selection.rangeCount || selection.isCollapsed) {
        Swal.fire({ title: 'Select Text First', text: 'Please select some text to comment on.', icon: 'info' });
        return;
    }
    
    // Safety: Check if selection is inside editable area
    const range = selection.getRangeAt(0);
    const container = range.commonAncestorContainer;
    const editableParent = container.nodeType === 3 
        ? container.parentElement.closest('[contenteditable="true"]')
        : container.closest('[contenteditable="true"]');
    
    if (!editableParent) {
        Swal.fire({ title: 'Invalid Selection', text: 'Please select text inside the editor.', icon: 'warning' });
        return;
    }
    
    savedRange = range;
    
    const modal = document.getElementById('comment-modal');
    modal.style.display = 'flex';
    void modal.offsetWidth; 
    modal.style.opacity = '1';
    modal.querySelector('div').style.transform = 'scale(1)';
    
    const inp = document.getElementById('comment-input');
    inp.value = '';
    inp.focus();
}

function closeCommentModal() {
    const modal = document.getElementById('comment-modal');
    modal.style.opacity = '0';
    modal.querySelector('div').style.transform = 'scale(0.95)';
    
    setTimeout(() => {
        modal.style.display = 'none';
        savedRange = null;
    }, 200);
}

function submitComment() {
    const inp = document.getElementById('comment-input');
    const text = inp.value.trim();
    if (!text) { 
        Swal.fire('Error', 'Comment cannot be empty.', 'error');
        return; 
    }
    
    if (!savedRange) { 
        closeCommentModal(); return; 
    }
    
    // SAFETY CHECK: Prevent Multi-Cell Selection (File Upload only)
    if (IS_FILE_UPLOAD) {
        const clone = savedRange.cloneContents();
        if (clone.querySelector('td, th, tr, tbody, table')) {
            Swal.fire({ 
                title: '⚠️ Aksi Dibatasi', 
                html: 'Tidak dapat memberi komentar lintas kolom tabel.<br>Mohon blok teks di dalam satu kolom saja.', 
                icon: 'warning' 
            });
            closeCommentModal();
            return;
        }
    }
    
    // Safety: Check for nested comments
    const clone = savedRange.cloneContents();
    if (clone.querySelector('.inline-comment')) {
        Swal.fire({ 
            title: 'Cannot Nest Comments', 
            text: 'Selected text already contains a comment. Please select plain text only.', 
            icon: 'warning' 
        });
        closeCommentModal();
        return;
    }
    
    // Restore selection
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);

    const commentId = "c" + Date.now();
    const commentTime = new Date().toLocaleString('id-ID', {
        day:'numeric', month:'short', year:'numeric', 
        hour:'2-digit', minute:'2-digit', hour12: false
    }).replace(/\./g, ':');
    
    // PROTECTIVE LOCK
    const previousEditingState = isEditing;
    isEditing = false; 

    try {
        // MANUAL DOM WRAPPING (No execCommand)
        const wrapper = document.createElement('span');
        wrapper.className = 'inline-comment';
        wrapper.setAttribute('data-comment-id', commentId);
        wrapper.setAttribute('data-comment-text', text);
        wrapper.setAttribute('data-comment-user', CURRENT_USER_NAME);
        wrapper.setAttribute('data-comment-time', commentTime);
        wrapper.title = text;
        wrapper.style.backgroundColor = 'yellow';
        wrapper.style.cursor = 'pointer';

        // Extract selected content and move into wrapper
        const fragment = savedRange.extractContents();
        wrapper.appendChild(fragment);

        // Insert wrapper back
        savedRange.insertNode(wrapper);

        // Clear selection
        window.getSelection().removeAllRanges();
        
        hasUnsavedChanges = true; 
        setTimeout(() => {
            renderSideComments();
            // Call appropriate badge update based on mode
            if (IS_FILE_UPLOAD) {
                updateSheetTabBadges();
            } else {
                updateFreeInputTabBadges();
            }
        }, 50);
        closeCommentModal();
    } catch (e) {
        console.error("DOM Wrap Error:", e);
        // Fallback: If extractContents fails (complex DOM), try surroundContents or alert
        alert("Failed to wrap selection. Please try selecting plain text.");
    } finally {
        isEditing = previousEditingState;
    }
}

function removeComment(id) {
    // For Free Input, allow deletion without isEditing requirement
    if (IS_FILE_UPLOAD && !isEditing) return;
    
    showCustomConfirm("Hapus Item?", "Apakah Anda yakin ingin menghapus komentar/revisi ini?", () => {
        // Support both Legacy Comments and Revisions
        let span = document.querySelector(`.inline-comment[data-comment-id='${id}']`);
        if (!span) {
            span = document.getElementById(id); // Revisions often use ID directly
        }
        
        if (span) {
            if (span.classList.contains('revision-span')) {
                span.remove();
            } else {
                const parent = span.parentNode;
                while (span.firstChild) parent.insertBefore(span.firstChild, span);
                parent.removeChild(span);
            }
            
            hasUnsavedChanges = true;
            renderSideComments();
            // Call appropriate badge update based on mode
            if (IS_FILE_UPLOAD) {
                updateSheetTabBadges();
            } else {
                updateFreeInputTabBadges();
            }
        }
    });
}

// --- NEW ROBUST REAL-TIME SYNC (Replaces Flaky Observer) ---
// --- NEW ROBUST REAL-TIME SYNC (Delegated) ---
// --- NEW ROBUST REAL-TIME SYNC (Selection-Based) ---
function handleRealtimeInput(e) {
    if (!isEditing) return;
    
    // IGNORE Sidebar Inputs & Comment Modals
    if (e.target.tagName === 'TEXTAREA' || e.target.closest('#comment-modal')) return;

    // Helper to check inline red (Robust) - DEFINED FIRST
    const isStyleRed = (el) => {
        if (!el || !el.style) return false;
        const c = el.style.color;
        if (!c) return false;
        const s = c.toLowerCase().replace(/\s/g, ''); 
        // Add Tailwind Red-500 (rgb(239, 68, 68))
        return s === 'red' || s === 'rgb(255,0,0)' || s === '#ef4444' || s === '#ff0000' || s === 'rgb(239,68,68)';
    };

    // DEBUG: Logs removed for production
    
    // Critical Fix: e.target is the CONTAINER (div), not the span.
    // We must use Selection to find the actual cursor position.
    const selection = window.getSelection();
    if (!selection.anchorNode) return;
    
    let target = selection.anchorNode;
    // Normalize text node to element
    if (target.nodeType === 3) target = target.parentNode;
    
    // Walk up to look for EXISTING revision container first
    let current = target;
    let groupId = null;
    
    // Pass 1: Ancestry Check (STRICTLY RED PARENTS ONLY)
    let d = 0;
    while (current && current !== document.body && d < 5) {
        // DEBUG: Check what we are looking at
    // Logs removed
        
        if (current.nodeType === 1) {
             // STRICT: Only consider parent if it is VISUALLY RED
             if (isStyleRed(current)) {
                groupId = current.getAttribute('data-comment-id') || current.id;
                if (groupId) {
                    console.log(`[DEBUG] Found Group ID: ${groupId}`);
                    break;
                }
             } else {
                 // Stop identifying if we hit a non-red container (like original-content)
                 // This prevents "Leaking" into black text
                 if (current.classList.contains('original-content') || !current.classList.contains('revision-span')) {
                     console.log("[DEBUG] Hit non-red boundary, stopping ancestry check.");
                     break; 
                 }
             }
        }
        current = current.parentNode;
        d++;
    }

    // Pass 2: Sibling Check (If no parent ID, maybe we just typed next to one)
    if (!groupId && target.nodeType === 1) {
        const prev = target.previousElementSibling;
        if (prev && (prev.classList.contains('revision-span') || prev.style.color === 'red')) {
            groupId = prev.getAttribute('data-comment-id') || prev.id;
        }
    }

    // LINKING LOGIC
    // Apply or Create ID
    if (!groupId) {
        // Truly new session
        groupId = "rev-" + Date.now();
        // STRICT: Only tag SPANS that are ALREADY RED (created by handleEditorInput)
        if (target.nodeType === 1 && target.tagName === 'SPAN' && isStyleRed(target)) {
             target.id = groupId; // Primary ID
             target.setAttribute('data-comment-id', groupId);
             target.classList.add('revision-span');
        }
    } else {
        // Link to existing session
        // STRICT: Only tag RED SPANS.
        if (target.nodeType === 1 && target.tagName === 'SPAN' && isStyleRed(target) && !target.getAttribute('data-comment-id')) {
            target.setAttribute('data-comment-id', groupId);
            target.classList.add('revision-span');
        }
    }

    if (groupId) {
        setTimeout(() => {
            // AGGREGATE TEXT from all spans in this group
            const groupParams = document.querySelectorAll(`[data-comment-id="${groupId}"], #${groupId}`);
            let fullText = "";
            let uniqueNodes = new Set();

            groupParams.forEach(node => {
                // STRICT FILTER: Only include if explicitly VISUALLY RED
                // We REMOVED the class check because "Zombie Spans" (Black text with class) were leaking in.
                // Now we only trust correct inline styles.
                // UPDATED: Use Robust Helper to catch 'rgb(255, 0, 0)' vs 'rgb(255,0,0)'
                const isActuallyRed = isStyleRed(node);
                
                if (!isActuallyRed) return; // Skip non-red text

                if (!uniqueNodes.has(node)) {
                    fullText += node.textContent;
                    uniqueNodes.add(node);
                }
            });
            
            // Fallback if query failed but we have target
            if (!fullText && target) fullText = target.textContent;

            updateDraftCard(groupId, fullText.replace(/\u200B/g, ''));
        }, 0);
    }
}

// Global Attachment ensures it never fails
document.addEventListener('input', (e) => {
    handleRealtimeInput(e);
});

// --- ORIGINAL SPAN CREATION LOGIC (Restored) ---
function handleEditorInput(e) {
    if (!isEditing) return;
    
    // DEBUG: Trace execution
    // (Logs removed)

    // CRITICAL FIX: Ignore Deletions (Backspace/Delete)
    // Let browser handle deletion natively. Do not try to create spans.
    if (e.inputType && e.inputType.startsWith('delete')) {
        return;
    }

    try {
        const selection = window.getSelection();
        if (!selection || !selection.rangeCount) return;
        
        let anchorNode = selection.anchorNode;
        if (!anchorNode) return;
        
        if (!anchorNode) return;

        // Check if already inside revision
        let el = anchorNode;
        let isInsideRevision = false;
        let revisionSpan = null;
        
        // Helper to normalize color check
        const isRedColor = (c) => {
            if (!c) return false;
            const s = c.toLowerCase().replace(/\s/g, ''); // Remove spaces
            // Add Tailwind Red-500 (rgb(239, 68, 68))
            return s === 'red' || s === 'rgb(255,0,0)' || s === '#ef4444' || s === '#ff0000' || s === 'rgb(239,68,68)';
        };

        let depth = 0;
        while (el && el !== document.body && depth < 20) {
            // DEBUG: Log traversal
            // console.log(`[DEBUG] Traversing: Tag=${el.tagName} Class=${el.className} Color=${el.style.color}`);

            // STRICTER CHECK: Must have class AND be visually RED
            if (el.nodeType === 1 && el.classList && el.classList.contains('revision-span')) {
                if (isRedColor(el.style.color)) {
                    isInsideRevision = true;
                    revisionSpan = el;
                    break;
                } else {
                     // mismatch log removed
                }
            }
            el = el.parentNode;
            depth++;
        }

        // Check complete

        if (!isInsideRevision) {
             // Creating new span logic
            // Create New Red Span
            e.preventDefault(); // CRITICAL: Stop browser from inserting black text outside
            
            const char = e.data || "";
            const revId = "rev-" + Date.now();
            
            const span = document.createElement("span");
            span.className = "revision-span draft";
            span.id = revId;
            span.setAttribute('data-comment-id', revId); // Enable Grouping
            span.style.color = "red"; 
            span.textContent = char; // Insert the character directly
            
            const range = selection.getRangeAt(0);
            range.insertNode(span);
            
            // Move Caret AFTER the new char
            range.setStart(span, 1);
            range.setEnd(span, 1);
            selection.removeAllRanges();
            selection.addRange(range);
            
            // Update Card Immediately with the char
            // updateDraftCard(revId, char); // Let realtime handle aggregation or call with char?
            // Better to just let realtime handle it, but for instant feedback:
            updateDraftCard(revId, char);
        } else {
            // Update existing - RELY ON MUTATION OBSERVER ONLY
            // Prevent stale updates from beforeinput event
        }
    } catch (err) {
        console.error("Auto-Rev Error:", err);
    }
}

function updateDraftCard(revId, text) {
    const list = document.getElementById('comment-list');
    const sidebar = document.getElementById('comment-sidebar');
    if (!list || !sidebar) return;
    
    sidebar.style.display = 'block';
    
    let card = document.getElementById(`card-${revId}`);
    
    if (!card) {
        card = document.createElement('div');
        card.id = `card-${revId}`;
        card.className = 'comment-card draft';
        
        // Base Style
        card.style.marginBottom = '15px';
        card.style.padding = '12px';
        card.style.borderRadius = '8px';
        card.style.transition = 'all 0.2s';
        
        const time = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}).replace('.', ':');
        
        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                 <div style="display:flex; align-items:center; gap:8px;">
                     <div style="width:24px; height:24px; background:#fecaca; color:#dc2626; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:bold;">!</div>
                     <div style="font-size:12px; font-weight:700; color:#dc2626;">Draft Revision</div>
                 </div>
                 <div style="font-size:10px; color:#999;">${time}</div>
            </div>
            <div id="text-${revId}" style="font-size:13px; color:#333; line-height:1.4; margin-bottom:10px; border-left:2px solid #fecaca; padding-left:8px;">${text||'...'}</div>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                 <button onclick="cancelRevision('${revId}')" style="background:white; border:1px solid #ddd; color:#666; padding:4px 8px; border-radius:4px; font-size:11px; cursor:pointer;">Cancel</button>
                 <button onclick="commitRevision('${revId}')" style="background:#ef4444; border:none; color:white; padding:4px 12px; border-radius:4px; font-size:11px; font-weight:600; cursor:pointer;">SAVE</button>
            </div>
        `;
        list.prepend(card);
    } else {
        const t = document.getElementById(`text-${revId}`);
        console.log(`[DEBUG] Updating EXISTING Card ${revId} -> element found? ${!!t}`);
        if(t) t.textContent = text || '...';
    }
}

function commitRevision(revId) {
    const span = document.getElementById(revId);
    const card = document.getElementById(`card-${revId}`);
    
    if (span && card) {
        span.classList.remove('draft');
        card.classList.remove('draft');
        card.style.border = '1px solid #e2e8f0';
        card.style.background = 'white';
        card.style.opacity = '1';
        
        const text = span.innerText;
        const timeObj = new Date();
        const timeStr = timeObj.toLocaleString('id-ID', { day:'numeric', month:'short', hour: '2-digit', minute:'2-digit' }).replace('.', ':');
        
        span.setAttribute('data-comment-text', text);
        span.setAttribute('data-comment-user', CURRENT_USER_NAME);
        span.setAttribute('data-comment-time', timeStr);
        
        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; color:#ef4444; font-weight:bold; border:1px solid #fecaca;">R</div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#dc2626;">Revision (Saved)</div>
                        <div style="font-size:11px; color:#94a3b8;">${timeStr}</div>
                    </div>
                </div>
            </div>
            <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:12px; font-size:14px; color:#334155; line-height:1.6;">${text}</div>
        `;
        
        // Click to scroll
        card.onclick = () => {
             span.scrollIntoView({behavior: "smooth", block: "center"});
             span.classList.remove('blink-highlight');
             void span.offsetWidth; // Reflow
             span.classList.add('blink-highlight');
        };
        hasUnsavedChanges = true;
    }
}

function cancelRevision(revId) {
    const span = document.getElementById(revId);
    const card = document.getElementById(`card-${revId}`);
    showCustomConfirm("Batalkan Revisi?", "Buang draft revisi ini?", () => {
        if(span) span.remove();
        if(card) card.remove();
    });
}

// --- SIDEBAR RENDER ---
function renderSideComments() {
    const list = document.getElementById('comment-list');
    const sidebar = document.getElementById('comment-sidebar');
    if (!list) return;

    list.innerHTML = '';
    
    // FETCH BOTH COMMENTS AND REVISIONS
    // SCOPED to Editor Content Only (Fixes "Cancel Save" appearing as card)
    let container = document.querySelector('.media-pane[style*="display: block"]') || document.querySelector('.media-pane') || document.getElementById('unified-file-editor');
    
    // If no specific pane visible, try all (Multi-sheet edge case)
    let commentsRaw = [];
    if (container) {
         commentsRaw = Array.from(container.querySelectorAll('.inline-comment, .revision-span:not(.draft), span[style*="#ef4444"], span[style*="color:red"], span[style*="color: red"], span[style*="rgb(255, 0, 0)"]'));
    } else {
         // Fallback if container logic fails (unlikely)
         commentsRaw = Array.from(document.querySelectorAll('.media-pane .inline-comment, .media-pane .revision-span:not(.draft), #unified-file-editor .inline-comment, #unified-file-editor .revision-span:not(.draft)'));
    }
    const uniqueComments = [];
    const seenIds = new Set();
    
    commentsRaw.forEach(span => {
        // FILTER: Ignore UI Elements (Modals, Popovers)
        if (span.closest('#comment-modal')) return;
        if (span.offsetParent === null) return; // Ignore Hidden Elements
        
        // HEURISTIC: Ignore if text looks like the Modal UI
        const checkText = span.textContent || "";
        if (checkText.includes("Add Comment") && checkText.includes("Cancel")) return;

        let id = span.getAttribute('data-comment-id') || span.id; // Support both
        if (!seenIds.has(id)) {
            seenIds.add(id);
            
            // ROBUST TEXT EXTRACTION
            let rawText = span.getAttribute('data-comment-text');
            if (!rawText) {
                // Try innerText first, then textContent
                rawText = span.innerText || span.textContent || "";
                rawText = rawText.replace(/\u200B/g, '');
            }
            
            uniqueComments.push({
                id: id,
                text: rawText,
                user: span.getAttribute('data-comment-user') || 'Reviewer',
                time: span.getAttribute('data-comment-time') || '',
                timestamp: parseInt(id.replace(/^(c|rev-)/, '')) || 0,
                element: span
            });
        }
    });

    uniqueComments.sort((a, b) => b.timestamp - a.timestamp);
    
    if (uniqueComments.length === 0) {
        if(sidebar) sidebar.style.display = 'none';
        return;
    }
    
    if(sidebar) sidebar.style.display = 'block';

    uniqueComments.forEach(c => {
        const card = document.createElement('div');
        card.className = 'comment-card';
        card.style.marginBottom = '15px';
        card.style.background = 'white';
        card.style.borderRadius = '12px';
        card.style.padding = '16px';
        card.style.boxShadow = '0 4px 6px rgba(0,0,0,0.02)';
        card.style.border = '1px solid #e2e8f0';
        
        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; background:#eff6ff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; color:#3b82f6; font-weight:bold; border:1px solid #dbeafe;">${c.user.charAt(0).toUpperCase()}</div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#1e293b;">${c.user}</div>
                        <div style="font-size:11px; color:#94a3b8;">${c.time}</div>
                    </div>
                </div>
                <button class="btn-delete-comment" onclick="removeComment('${c.id}')" title="Delete" style="background:none; border:none; cursor:pointer; color:#ef4444;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
            </div>
            <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:12px; font-size:14px; color:#334155; line-height:1.6;">${c.text}</div>
        `;
        
        // Interaction
        card.onclick = () => {
             // 1. Switch Tab if needed
             const parentSheet = c.element.closest('.sheet-pane');
             if (parentSheet && parentSheet.style.display === 'none') {
                 // Try finding button
                 let btn = document.querySelector(`button[onclick*="'${parentSheet.id}'"]`);
                 if(btn) btn.click();
                 // Fallback for Free Input tabs
                 if(!btn && parentSheet.id.startsWith('tab-')) {
                     const idx = parentSheet.id.replace('tab-', '');
                     const btn2 = document.getElementById(`tab-btn-review-${idx}`);
                     if(btn2) btn2.click();
                 }
             }

             // 2. Scroll
             setTimeout(() => {
                 c.element.scrollIntoView({behavior: "smooth", block: "center"});
                 c.element.classList.remove('blink-highlight');
                 void c.element.offsetWidth; 
                 c.element.classList.add('blink-highlight');
                 
                 // Highlight Card
                 document.querySelectorAll('.comment-card').forEach(x => { x.style.borderColor = '#e2e8f0'; x.style.backgroundColor = 'white'; });
                 card.style.borderColor = '#ef4444';
                 card.style.backgroundColor = '#fef2f2';
             }, 100);
        };
        
        list.appendChild(card);
    });
}

// --- INIT ---
document.addEventListener('DOMContentLoaded', () => {
    
    // Auto color logic
    if (IS_FILE_UPLOAD) {
        
        // SELF-HEAL: Repair Broken Tables from previous invalid saves
        // Unwraps inline-table corruption: tr > span.inline-comment > td
        const corruptSpans = document.querySelectorAll('tr > span.inline-comment, tbody > span.inline-comment, table > span.inline-comment');
        if (corruptSpans.length > 0) {
            console.warn("Found corruption in table structure! Auto-repairing...");
            corruptSpans.forEach(span => {
                const parent = span.parentNode;
                while (span.firstChild) parent.insertBefore(span.firstChild, span);
                parent.removeChild(span);
            });
        }
        
        const editableElements = document.querySelectorAll('.excel-preview td, .word-preview p');
        editableElements.forEach(el => {
            if (el.innerHTML.trim() !== '' && !el.querySelector('span.original-content')) {
                const originalText = el.innerHTML;
                el.innerHTML = `<span class="original-content" style="color:#333;">${originalText}</span>`;
            }
        });
        
        const applyModeColor = () => {
             if (!isEditing) return;
             const sel = window.getSelection();
             if (sel.rangeCount > 0 && !sel.isCollapsed) return;
             document.execCommand('styleWithCSS', false, true);
             document.execCommand('foreColor', false, '#ef4444');
        };

        const editor = document.getElementById('unified-file-editor');
        if (editor) {
            editor.addEventListener('focus', applyModeColor);
            editor.addEventListener('click', applyModeColor);
            editor.addEventListener('keyup', applyModeColor);
        }
        
        renderSideComments();
        updateSheetTabBadges();
        enableEditMode();
    } else {
        // FREE INPUT MODE
        // Track changes on contentEditable divs (not textarea anymore)
        document.querySelectorAll('.free-input-editor').forEach(editor => {
            editor.addEventListener('input', () => { 
                hasUnsavedChanges = true; 
            });
        });
        
        // Render existing comments (if any from previous review cycle)
        renderSideComments();
        updateFreeInputTabBadges();
    }

    // Unsaved Warning
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) { e.preventDefault(); e.returnValue = ''; }
    });
    
    const forms = document.querySelectorAll('form');
    forms.forEach(f => {
        f.addEventListener('submit', () => { hasUnsavedChanges = false; });
    });
    
    // Upload Handlers（Completed）
    ['file_legal', 'file_cx', 'file_syariah', 'file_lpp'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', function() { handleReviewDocUpload(this, id.replace('file_','')); });
    });
});


// --- UTILITIES ---
function performUndo() { document.execCommand('undo'); setTimeout(renderSideComments, 50); }
function performRedo() { document.execCommand('redo'); setTimeout(renderSideComments, 50); }

function setMode(mode) {
    currentColorMode = mode;
    const btnBlack = document.getElementById('btn-black');
    const btnRed = document.getElementById('btn-red');
    if(btnBlack && btnRed) {
        if (mode === 'BLACK') {
            btnBlack.classList.add('active-black');
            btnRed.classList.remove('active-red');
            document.execCommand('styleWithCSS', false, true);
            document.execCommand('foreColor', false, '#333333');
        } else {
            btnRed.classList.add('active-red');
            btnBlack.classList.remove('active-black');
            document.execCommand('styleWithCSS', false, true);
            document.execCommand('foreColor', false, '#ef4444');
        }
    }
}

function openTab(evt, tabId) {
    // Hide all tab content
    document.querySelectorAll(".review-tab-content").forEach(el => {
        el.style.display = "none";
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll(".btn-sheet").forEach(el => {
        el.classList.remove('active');
    });
    
    // Show selected tab content
    const targetTab = document.getElementById(tabId);
    if (targetTab) {
        targetTab.style.display = "block";
        
        // Focus the editor in this tab (for Free Input mode)
        const editor = targetTab.querySelector('.free-input-editor');
        if (editor) {
            setTimeout(() => editor.focus(), 100);
        }
    }
    
    // Add active class to clicked button
    if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add('active');
    }
}

function updateSheetTabBadges() {
    document.querySelectorAll('.tab-badge-dot').forEach(el => el.remove());
    document.querySelectorAll('.sheet-pane, .media-pane').forEach(pane => {
        // ROBUST SELECTOR: Check for class AND various color formats (Hex, Name, RGB)
        const hasComments = pane.querySelector('.inline-comment, .revision-span, span[style*="#ef4444"], span[style*="color:red"], span[style*="color: red"], span[style*="rgb(255, 0, 0)"]');
        if (hasComments) {
            const paneId = pane.id;
            let btn = null;
            
            // Strategy 1: Find by onclick (Button or Div)
            btn = document.querySelector(`button[onclick*="'${paneId}'"], div[onclick*="'${paneId}'"]`);
            
            // Strategy 2: Specific Legacy ID Pattern (tab-media-0 -> tab-media-btn-0)
            if (!btn && paneId.startsWith('tab-media-')) {
                const legacyId = paneId.replace('tab-media-', 'tab-media-btn-');
                btn = document.getElementById(legacyId);
            }

            // Strategy 3: Free Input Pattern (tab-0 -> tab-btn-review-0)
            if (!btn && paneId.startsWith('tab-')) {
                const idx = paneId.replace('tab-', '');
                btn = document.getElementById(`tab-btn-review-${idx}`);
            }

            if (btn && !btn.querySelector('.tab-badge-dot')) {
                const dot = document.createElement('span');
                dot.className = 'tab-badge-dot';
                dot.style.cssText = "display:inline-flex;justify-content:center;align-items:center;width:16px;height:16px;background:#ef4444;color:white;font-size:10px;font-weight:bold;border-radius:50%;margin-left:6px;vertical-align:middle;";
                dot.innerText = '!';
                btn.appendChild(dot);
            }
        }
    });
}

function updateFreeInputTabBadges() {
    document.querySelectorAll('.tab-badge-dot').forEach(el => el.remove());
    
    document.querySelectorAll('.review-tab-content').forEach(pane => {
        const hasComments = pane.querySelector('.inline-comment');
        if (hasComments) {
            const paneId = pane.id; // e.g., "tab-0"
            const idx = paneId.replace('tab-', '');
            const btn = document.getElementById(`tab-btn-review-${idx}`);
            
            if (btn && !btn.querySelector('.tab-badge-dot')) {
                const dot = document.createElement('span');
                dot.className = 'tab-badge-dot';
                dot.style.cssText = "display:inline-flex; width:16px; height:16px; background:#ef4444; color:white; font-size:10px; font-weight:bold; border-radius:50%; margin-left:6px; align-items:center; justify-content:center;";
                dot.innerText = '!';
                btn.appendChild(dot);
            }
        }
    });
}




function toggleRemarks() {
    const val = document.getElementById('decision').value;
    document.getElementById('remarksGroup').style.display = (val === 'REVISE' || val === 'REJECT') ? 'block' : 'none';
}

function saveDraft() { executeAction('saveDraft'); }
function submitDecision() {
    const decision = document.getElementById('decision').value;
    
    // VALIDATION: Legal & CX Required for Approval (ONLY FOR PROCEDURE ROLE)
    if (decision === 'APPROVE' && CURRENT_USER_ROLE === 'PROCEDURE') {
        const hasLegal = REVIEW_EVIDENCE['LEGAL'] && REVIEW_EVIDENCE['LEGAL'].length > 0;
        const hasCX = REVIEW_EVIDENCE['CX'] && REVIEW_EVIDENCE['CX'].length > 0;
        
        if (!hasLegal || !hasCX) {
            let msg = 'Dokumen evidence berikut WAJIB diupload sebelum Approval:<br>';
            if (!hasLegal) msg += '- <b>Legal</b><br>';
            if (!hasCX) msg += '- <b>CX</b><br>';
            
            showModal('Hold On!', msg, 'error');
            return; // STOP
        }
    }

    const action = decision === 'APPROVE' ? 'approve' : (decision === 'REVISE' ? 'revise' : 'reject');
    executeAction(action);
}

function executeAction(action) {
    const form = document.getElementById('approvalForm');
    const formData = new FormData(form);
    const updatedContent = {};
    if (IS_FILE_UPLOAD) {
        // ADAPTIVE SAVE LOGIC:
        // 1. Check if we have split panes (New Multi-Sheet System)
        const panes = document.querySelectorAll('.media-pane');
        
        if (panes.length > 0 && SERVER_CONTENT.length > 0) {
            // MULTI-SHEET MODE: Map each pane to its Server ID
            SERVER_CONTENT.forEach((row, index) => {
                // Try to find the specific pane for this row
                // We use index correlation or ID pattern
                const specificPane = document.getElementById(`tab-media-${index}`);
                
                if (specificPane) {
                    updatedContent[row.id] = specificPane.innerHTML;
                } else {
                    // Fallback: If specific pane missing (Rare), use whole editor but warn
                    console.warn(`Pane for index ${index} not found. Keeping original.`);
                    // Ideally we should not overwrite if we can't find it to be safe
                }
            });
        } else {
            // SINGLE SHEET / LEGACY MODE:
            // No panes found, assume the whole editor is the content.
            const html = document.getElementById('unified-file-editor').innerHTML;
            SERVER_CONTENT.forEach(row => updatedContent[row.id] = html);
        }
    } else {
        // Free Input: Save HTML content (includes comment tags)
        document.querySelectorAll('.free-input-editor').forEach(editor => {
            const id = editor.getAttribute('data-id');
            updatedContent[id] = editor.innerHTML; // Changed from .value to .innerHTML
        });
    }

    const data = {};
    formData.forEach((value, key) => data[key] = value);
    data['updated_content'] = updatedContent;
    
    fetch('index.php?controller=request&action=' + action, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(json => {
        console.log('Backend response:', json); // Debug
        
        if (json.success || json.status === 'success') {
            // Clear unsaved changes flag
            hasUnsavedChanges = false;
            window.onbeforeunload = null;
            
            if (action === 'saveDraft') {
                // For draft, show message and stay on page
                alert('Draft berhasil disimpan!');
            } else {
                // For approve/revise/reject, redirect immediately
                alert('Decision berhasil disubmit!');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 500);
            }
        } else {
            alert('Gagal: ' + (json.error || json.message || 'Terjadi kesalahan'));
        }
    })
    .catch(err => {
        console.error('Submit error:', err);
        alert('Error: Terjadi kesalahan sistem.');
    });
}

// ===== MODAL FUNCTIONS (Sync with edit.php) =====
function showModal(title, message, type = 'success') {
    const modal = document.getElementById('successModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    const modalBtn = document.getElementById('modalBtn');
    const modalIcon = document.getElementById('modalIcon');
    
    modalTitle.textContent = title;
    modalMessage.innerHTML = message;
    
    if (type === 'error') {
        modalIcon.style.background = '#fee2e2';
        modalIcon.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        modalBtn.style.background = '#dc2626';
        modalBtn.style.display = 'inline-block';
    } else {
        modalIcon.style.background = '#dcfce7';
        modalIcon.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        modalBtn.style.background = 'var(--primary-red)';
        modalBtn.style.display = 'none';
    }
    
    modal.classList.add('show');
}

function showSuccess(title, message, reload = false) {
    showModal(title, message, 'success');
    
    // CRITICAL: Prevent "Unsaved Changes" popup on reload
    hasUnsavedChanges = false; 
    window.onbeforeunload = null; 

    if (reload) {
        setTimeout(() => window.location.reload(), 2000);
    } else {
        setTimeout(() => window.location.href = 'index.php', 2000);
    }
}

function closeModal() {
    document.getElementById('successModal').classList.remove('show');
}

function showCustomConfirm(title, message, onConfirm, onCancel) {
    const modal = document.getElementById('confirmModal');
    const confirmTitle = document.getElementById('confirmTitle');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmOkBtn = document.getElementById('confirmOkBtn');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    
    confirmTitle.textContent = title;
    confirmMessage.innerHTML = `<p style="margin:0;">${message}</p>`;
    
    const newOkBtn = confirmOkBtn.cloneNode(true);
    const newCancelBtn = confirmCancelBtn.cloneNode(true);
    confirmOkBtn.parentNode.replaceChild(newOkBtn, confirmOkBtn);
    confirmCancelBtn.parentNode.replaceChild(newCancelBtn, confirmCancelBtn);
    
    newOkBtn.addEventListener('click', () => {
        modal.classList.remove('show');
        if (onConfirm) onConfirm();
    });
    
    newCancelBtn.addEventListener('click', () => {
        modal.classList.remove('show');
        if (onCancel) onCancel();
    });
    
    modal.classList.add('show');
}

async function handleReviewDocUpload(fileInput, docType) {
    // Normalize to uppercase immediately
    docType = docType.toUpperCase();
    
    const file = fileInput.files[0];
    if (!file) return;
    
    // Show loading indicator without clearing existing files
    const container = document.getElementById('status_' + docType.toLowerCase());
    const loadingDiv = document.createElement('div');
    loadingDiv.style.color = 'orange';
    loadingDiv.style.fontSize = '10px';
    loadingDiv.style.marginBottom = '4px';
    loadingDiv.innerHTML = '⏳ Uploading ' + file.name + '...';
    loadingDiv.id = 'temp-loading-' + Date.now();
    if (container) container.appendChild(loadingDiv);
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('doc_type', docType);
    formData.append('request_id', document.querySelector('input[name="request_id"]').value);
    
    try {
        const res = await fetch('?controller=request&action=uploadReviewDoc', { method: 'POST', body: formData });
        const json = await res.json();
        
        // Remove loading indicator
        if (loadingDiv && loadingDiv.parentElement) {
            loadingDiv.remove();
        }
        
        if (json.success) {
            // Append to List
            if (!REVIEW_EVIDENCE[docType]) REVIEW_EVIDENCE[docType] = [];
            REVIEW_EVIDENCE[docType].push({ id: json.id, filename: file.name });
            
            // Render UI
            if (container) {
                const div = document.createElement('div');
                div.style.marginBottom = '2px';
                div.style.display = 'flex';
                div.style.alignItems = 'center';
                div.style.justifyContent = 'space-between';
                div.innerHTML = `
                    <span style="color:green">✅ ${file.name}</span>
                    <button type="button" onclick="deleteReviewDoc('${docType}', '${json.id}', this)" style="background:none; border:none; color:red; cursor:pointer; font-size:10px; margin-left:4px;" title="Delete">❌</button>
                `;
                container.appendChild(div);
            }
            // Clear input for next upload
            fileInput.value = ''; 
            
            // Show success notification
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'File "' + file.name + '" berhasil di-upload',
                timer: 2000,
                showConfirmButton: false,
                position: 'center'
            });
        } else {
            Swal.fire('Error', json.error, 'error');
        }
    } catch (e) { 
        // Remove loading indicator on error too
        if (loadingDiv && loadingDiv.parentElement) {
            loadingDiv.remove();
        }
        Swal.fire('Error', 'Upload failed: ' + e.message, 'error'); 
    }
}

async function deleteReviewDoc(docType, fileId, btnElement) {
    if (!confirm('Are you sure you want to remove this file?')) return;
    
    // Prevent form submission if event is passed (though type=button should enough)
    if(event) event.preventDefault();

    try {
        const res = await fetch('?controller=request&action=deleteReviewDoc', { 
            method: 'POST', 
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ file_id: fileId }) 
        });
        const json = await res.json();
        
        if (json.success) {
            // Remove from UI
            btnElement.parentElement.remove();
            
            // Remove from Data
            if (REVIEW_EVIDENCE[docType]) {
                REVIEW_EVIDENCE[docType] = REVIEW_EVIDENCE[docType].filter(f => f.id != fileId);
            }
        } else {
             Swal.fire('Error', json.error, 'error');
        }
    } catch (e) { Swal.fire('Error', 'Delete failed', 'error'); }
}

// Global Tab Helper
function changeSheet(sheetId) {
    document.querySelectorAll('.sheet-pane').forEach(pane => pane.style.display = 'none');
    document.querySelectorAll('.btn-sheet').forEach(btn => btn.classList.remove('active'));
    const selectedSheet = document.getElementById(sheetId);
    if (selectedSheet) selectedSheet.style.display = 'block';
    if (event && event.target) event.target.classList.add('active');
}

function renderSideComments() {
    const list = document.getElementById('comment-list');
    if (!list) return;

    // Switch to Timeline View (Newest First) as requested
    list.innerHTML = ''; // Clear previous content
    list.style.position = 'static'; 
    list.style.height = 'auto';
    
    // Get comments and unique them
    // Get comments and unique them
    // OLD: const commentsRaw = Array.from(document.querySelectorAll('.inline-comment'));
    // NEW: Include saved revisions
    const commentsRaw = Array.from(document.querySelectorAll('.inline-comment, .revision-span:not(.draft)'));
    
    const uniqueComments = [];
    const seenIds = new Set();
    
    // Process in reverse DOM order initially or just filter then sort?
    // Let's filter unique first
    commentsRaw.forEach(span => {
        // ROBUST ID: Comments use attribute, Revisions use ID property
        const id = span.getAttribute('data-comment-id') || span.id;
        
        if (id && !seenIds.has(id)) {
            seenIds.add(id);
            
            // ROBUST TEXT:
            let rawText = span.getAttribute('data-comment-text');
            if (!rawText) {
                rawText = span.innerText || span.textContent || "";
                // Safety check before replace
                if (typeof rawText === 'string') {
                    rawText = rawText.replace(/\u200B/g, '');
                } else {
                    rawText = "";
                }
            }
            
            let timestamp = 0;
            if (id) {
                 try {
                     timestamp = parseInt(id.replace(/^(c|rev-)/, '')) || 0;
                 } catch(e) { timestamp = 0; }
            }

            uniqueComments.push({
                id: id,
                text: rawText, 
                user: span.getAttribute('data-comment-user') || 'Reviewer',
                time: span.getAttribute('data-comment-time') || '',
                timestamp: timestamp,
                element: span
            });
        }
    });

    // Sort by Timestamp Descending (Newest First)
    uniqueComments.sort((a, b) => b.timestamp - a.timestamp);
    
    if (uniqueComments.length === 0) {
        document.getElementById('comment-sidebar').style.display = 'none';
        return;
    }
    
    document.getElementById('comment-sidebar').style.display = 'block';

    uniqueComments.forEach(c => {
        const card = document.createElement('div');
        card.className = 'comment-card';
        card.setAttribute('data-for', c.id);
        
        // Static styling for list view
        card.style.position = 'relative'; 
        card.style.marginBottom = '15px';
        card.style.background = 'white';
        card.style.border = '1px solid #e2e8f0';
        card.style.borderRadius = '12px';
        card.style.padding = '16px';
        card.style.boxShadow = '0 4px 6px rgba(0,0,0,0.02)';
        card.style.transition = 'transform 0.2s, box-shadow 0.2s';
        
        // Enhanced Inner HTML (Bubble Style)
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
                <button class="btn-delete-comment" onclick="removeComment('${c.id}')" title="Delete Comment" style="background:none; border:none; cursor:pointer; padding:6px; color:#ef4444; opacity:0.6; transition:opacity 0.2s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                </button>
            </div>
            
            <!-- Comment Body Bubble -->
            <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:12px; font-size:14px; color:#334155; line-height:1.6;">
                ${c.text}
            </div>
        `;
        
        // Listeners
        card.addEventListener('mouseenter', () => {
             card.style.transform = 'translateY(-2px)';
             card.style.boxShadow = '0 10px 15px rgba(0,0,0,0.05)';
        });
        card.addEventListener('mouseleave', () => {
             card.style.transform = 'translateY(0)';
             card.style.boxShadow = '0 4px 6px rgba(0,0,0,0.02)';
        });
        
        card.addEventListener('click', () => {
             // 1. Cross-Tab Navigation
             const parentSheet = c.element.closest('.sheet-pane, .media-pane');
             if (parentSheet && parentSheet.style.display === 'none') {
                 // Determine Tab System (File Upload vs Free Input)
                 const sheetId = parentSheet.id;
                 
                 // Strategy 1: Find by onclick (Button or Div)
                 let tabBtn = document.querySelector(`button[onclick*="'${sheetId}'"], div[onclick*="'${sheetId}'"]`);
                 
                 // Strategy 2: Specific Legacy ID Pattern (tab-media-0 -> tab-media-btn-0)
                 if (!tabBtn && sheetId.startsWith('tab-media-')) {
                     const legacyId = sheetId.replace('tab-media-', 'tab-media-btn-');
                     tabBtn = document.getElementById(legacyId);
                 }
                 
                 // Strategy 3: Free Input Pattern
                 if (!tabBtn && sheetId.startsWith('tab-')) {
                     const idx = sheetId.replace('tab-', '');
                     tabBtn = document.getElementById(`tab-btn-review-${idx}`);
                 }

                 if (tabBtn) tabBtn.click();
             }

             // 2. Scroll Editor to Span
             // Small timeout to allow tab switch rendering
             setTimeout(() => {
                 c.element.scrollIntoView({behavior: "smooth", block: "center"});
                 
                 // 3. Flash / Blink Effect
                 c.element.classList.remove('blink-highlight');
                 void c.element.offsetWidth; // Trigger reflow
                 c.element.classList.add('blink-highlight');

                 // Highlight Card
                 document.querySelectorAll('.comment-card').forEach(x => {
                     x.style.borderColor = '#e2e8f0';
                     x.style.backgroundColor = 'white';
                     x.style.transform = 'scale(1)';
                 });
                 card.style.borderColor = '#ef4444'; // Red Border
                 card.style.backgroundColor = '#fef2f2'; // Light Red Background
                 card.style.transform = 'scale(1.02)'; // Slight pop
             }, 100);
        });
        
        
        list.appendChild(card);
        
        // Reverse Helper: Click Span -> Scroll Sidebar to Card
        c.element.onclick = (e) => {
             e.stopPropagation();
             card.scrollIntoView({behavior: "smooth", block: "center"});
             
             // Card Blink Effect (Green flash then settle to Active Red)
             card.style.transition = 'none';
             card.style.backgroundColor = '#ecfdf5'; // Light green flash
             setTimeout(() => {
                 card.style.transition = 'all 0.3s ease';
                 // Settle to Active State
                 document.querySelectorAll('.comment-card').forEach(x => {
                     x.style.borderColor = '#e2e8f0'; 
                     x.style.backgroundColor = 'white';
                     x.style.transform = 'scale(1)';
                 });
                 
                 card.style.borderColor = '#ef4444';
                 card.style.backgroundColor = '#fef2f2';
                 card.style.transform = 'scale(1.02)';
             }, 100);
        };
    });
}

// ==========================================
// ROBUST LIVE TYPING ENGINE (Full Intercept)
// ==========================================

function handleBeforeInput(e) {
    // DEBUG LOG
    // console.log("🔥 BEFORE INPUT FIRED:", e.inputType, e.data);

    if (!isEditing) return;

    // A. HANDLE INSERTION (Typing)
    if (e.inputType === 'insertText' && e.data) {
        e.preventDefault(); // STOP BROWSER. WE TAKE CONTROL.
        
        const selection = window.getSelection();
        if (!selection.rangeCount) return;
        
        let el = selection.anchorNode;
        // Normalize
        if (el.nodeType === 3) el = el.parentNode; 
        
        // 1. FIND TARGET SPAN (Inside or Previous)
        let targetSpan = null;
        
        // 1a. Check inside
        let walker = el;
        while (walker && walker !== document.body) {
            if (walker.nodeType === 1 && walker.classList.contains('revision-span')) {
                targetSpan = walker;
                break;
            }
            walker = walker.parentNode;
        }
        
        // 1b. Check Previous (Merge/Magnet)
        if (!targetSpan) {
             let anchor = selection.anchorNode;
             if (anchor.nodeType === 3 && anchor.previousSibling && 
                 anchor.previousSibling.nodeType === 1 && 
                 anchor.previousSibling.classList.contains('revision-span')) {
                 targetSpan = anchor.previousSibling;
             }
             else if (anchor.nodeType === 1) {
                  // Element context: check if selection is right after a span
                  // Simplified: look at last child or previous sibling logic
                  // This is complex, but often the Magnet isn't needed if we maintain focus well.
             }
        }

        // 2. INSERT TEXT
        if (targetSpan) {
            // APPEND TO EXISTING
            // Handle Space carefully? Normal space is fine in textContent.
            targetSpan.textContent += e.data;
            
            // Move Cursor to END
            const range = document.createRange();
            range.selectNodeContents(targetSpan);
            range.collapse(false);
            selection.removeAllRanges();
            selection.addRange(range);
            
            // CRITICAL FIX: UPDATE SIDEBAR IMMEDIATELY
            const cleanText = targetSpan.innerText.replace(/\u200B/g, '');
            updateDraftCard(targetSpan.id, cleanText);
            
        } else {
            // CREATE NEW
            const revId = "rev-" + Date.now();
            const span = document.createElement("span");
            span.className = "revision-span draft";
            span.id = revId;
            span.style.color = "red";
            
            span.textContent = e.data; 
            
            const range = selection.getRangeAt(0);
            if (!range.collapsed) range.deleteContents();
            
            range.insertNode(span);
            
            // Move Cursor INSIDE
            range.selectNodeContents(span);
            range.collapse(false);
            selection.removeAllRanges();
            selection.addRange(range);
            
            // CRITICAL FIX: UPDATE SIDEBAR FOR NEW SPAN
            updateDraftCard(revId, e.data);
        }
    } // Close insertText block
    
    // B. HANDLE DELETION
    if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward') {
        setTimeout(() => {
            document.querySelectorAll('.revision-span').forEach(s => {
                let txt = s.innerText || s.textContent || "";
                if (txt.replace(/\u200B/g, '').length === 0) {
                    s.remove();
                } else {
                    // Update card on deletion too
                    updateDraftCard(s.id, txt);
                }
            });
        }, 0);
    }
}

function updateDraftCard(revId, text) {
    const list = document.getElementById('comment-list');
    const sidebar = document.getElementById('comment-sidebar');
    if (!list || !sidebar) return;
    
    sidebar.style.display = 'block';
    
    let card = document.getElementById(`card-${revId}`);
    
    if (!card) {
        card = document.createElement('div');
        card.id = `card-${revId}`;
        card.className = 'comment-card draft';
        
        card.style.marginBottom = '15px';
        card.style.padding = '12px';
        card.style.borderRadius = '8px';
        card.style.position = 'relative';
        card.style.transition = 'all 0.2s';
        
        // Init Cache
        card.setAttribute('data-stored-text', text || "");
        
        const time = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}).replace('.', ':');
        
        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                 <div style="display:flex; align-items:center; gap:8px;">
                     <div style="width:24px; height:24px; background:#fecaca; color:#dc2626; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:bold;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                     </div>
                     <div style="font-size:12px; font-weight:700; color:#dc2626;">Draft Revision</div>
                 </div>
                 <div style="font-size:10px; color:#999;">${time}</div>
            </div>
            
            <div id="text-${revId}" style="font-size:13px; color:#333; line-height:1.4; margin-bottom:10px; border-left:2px solid #fecaca; padding-left:8px;">
                ${text || '...'}
            </div>
            
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                 <button onclick="cancelRevision('${revId}')" style="background:white; border:1px solid #ddd; color:#666; padding:4px 8px; border-radius:4px; font-size:11px; cursor:pointer;">Cancel</button>
                 <button onclick="commitRevision('${revId}')" style="background:#ef4444; border:none; color:white; padding:4px 12px; border-radius:4px; font-size:11px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> SAVE
                 </button>
            </div>
        `;
        list.prepend(card);
    } else {
        const textDiv = document.getElementById(`text-${revId}`);
        if (textDiv) textDiv.innerText = text || '...';
        
        if (card) card.setAttribute('data-stored-text', text);
    }
    
    // UPDATE BADGES ON TYPING
    if (typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
}

function commitRevision(revId) {
    const span = document.getElementById(revId);
    const card = document.getElementById(`card-${revId}`);
    
    if (span && card) {
        span.classList.remove('draft');
        card.classList.remove('draft');
        card.style.border = '1px solid #e2e8f0';
        card.style.background = 'white';
        card.style.opacity = '1';
        
        card.style.opacity = '1';
        
        // PRIORITY 1: READ FROM CACHED DATA ATTRIBUTE (Most Reliable)
        let text = card.getAttribute('data-stored-text');
        
        // PRIORITY 2: FORCE READ FROM SPAN
        if (!text) {
             text = span.innerText || span.textContent || "";
             text = text.replace(/\u200B/g, '').trim(); 
        }

        // PRIORITY 3: BACKFILL FROM SIDEBAR DOM
        if (!text) {
            const sidebarText = document.getElementById(`text-${revId}`);
            if (sidebarText) {
                text = sidebarText.innerText.trim();
            }
        }

        // Final Safety
        if (!text) text = "(No text content)";
        const timeObj = new Date();
        const timeStr = timeObj.toLocaleString('id-ID', { day:'numeric', month:'short', hour: '2-digit', minute:'2-digit' }).replace('.', ':');
        
        span.setAttribute('data-comment-text', text);
        span.setAttribute('data-comment-user', CURRENT_USER_NAME);
        span.setAttribute('data-comment-time', timeStr);
        
        card.innerHTML = `
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; background:#fef2f2; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; color:#ef4444; font-weight:bold; border:1px solid #fecaca;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#dc2626;">Revision (Saved)</div>
                        <div style="font-size:11px; color:#94a3b8;">${timeStr}</div>
                    </div>
                </div>
                <button onclick="removeComment('${revId}')" style="background:none; border:none; cursor:pointer; color:#94a3b8;" title="Delete Revision">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
            
            <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:12px; font-size:14px; color:#334155; line-height:1.6;">
                ${text}
            </div>
        `;
        
        card.addEventListener('click', () => {
             // 1. Cross-Tab Navigation (Robust)
             const parentSheet = span.closest('.sheet-pane, .media-pane');
             if (parentSheet && parentSheet.style.display === 'none') {
                 const sheetId = parentSheet.id;
                 
                 // Strategy 1: Find by onclick (Button or Div)
                 let tabBtn = document.querySelector(`button[onclick*="'${sheetId}'"], div[onclick*="'${sheetId}'"]`);
                 
                 // Strategy 2: Specific Legacy ID Pattern (tab-media-0 -> tab-media-btn-0)
                 if (!tabBtn && sheetId.startsWith('tab-media-')) {
                     const legacyId = sheetId.replace('tab-media-', 'tab-media-btn-');
                     tabBtn = document.getElementById(legacyId);
                 }
                 
                 // Strategy 3: Free Input Pattern
                 if (!tabBtn && sheetId.startsWith('tab-')) {
                     const idx = sheetId.replace('tab-', '');
                     tabBtn = document.getElementById(`tab-btn-review-${idx}`);
                 }

                 if (tabBtn) tabBtn.click();
             }

             // 2. Scroll Editor to Span with Timeout for Tab Switch
             setTimeout(() => {
                 span.scrollIntoView({behavior: "smooth", block: "center"});
                 
                 // 3. Visual Feedback
                 span.classList.remove('blink-highlight');
                 void span.offsetWidth;
                 span.classList.add('blink-highlight');
                 
                 // Highlight Card Active State
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
        
        hasUnsavedChanges = true;
        
        // UPDATE BADGES ON SAVE
        if (typeof updateSheetTabBadges === 'function') updateSheetTabBadges();
    }
}

function cancelRevision(revId) {
    const span = document.getElementById(revId);
    const card = document.getElementById(`card-${revId}`);
    
    if (confirm("Discard this revision?")) {
        if (span) span.remove(); 
        if (card) card.remove();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Helper to apply color (Global Scope for both modes)
    const applyModeColor = () => {
         if (!isEditing) return;
         
         const sel = window.getSelection();
         if (sel.rangeCount > 0 && !sel.isCollapsed) return; // Don't color selection

         // Always Force Red since Black button was removed
         document.execCommand('styleWithCSS', false, true);
         document.execCommand('foreColor', false, '#ef4444');
    };

    if (IS_FILE_UPLOAD) {
        // AUTOMATIC COLOR LOGIC BASED ON MODE
        const editableElements = document.querySelectorAll('.excel-preview td, .word-preview p');
        editableElements.forEach(el => {
            // Wrap existing content if not wrapped
            if (el.innerHTML.trim() !== '' && !el.querySelector('span.original-content')) {
                const originalText = el.innerHTML;
                el.innerHTML = `<span class="original-content" style="color:#333;">${originalText}</span>`;
            }
        });
        
        // Setup Editor Listeners (Separate from cell loop)
        const editor = document.getElementById('unified-file-editor');
        if (editor) {
            // Ensure listeners are attached for dynamic coloring
            editor.addEventListener('focus', applyModeColor);
            editor.addEventListener('click', applyModeColor);
            editor.addEventListener('keyup', applyModeColor);
            editor.addEventListener('keyup', applyModeColor);
        }
        
    // Initial Render
        renderSideComments();
        updateSheetTabBadges();
        
        // AUTO-ENABLE EDIT MODE
        enableEditMode();
    } else {
        // FREE INPUT MODE
        checkReviewTabBadges();
        
        // Track changes for Free Input Mode AND Apply Color
        document.querySelectorAll('.free-input-editor').forEach(textarea => {
            // Attach Auto-Red Logic
            textarea.addEventListener('focus', applyModeColor);
            textarea.addEventListener('click', applyModeColor);
            textarea.addEventListener('keyup', applyModeColor);
            textarea.addEventListener('input', () => {
                hasUnsavedChanges = true;
                // applyModeColor(); // Optional: Enforce red on input too
            });
        });
        
        // AUTO-ENABLE EDIT MODE FOR FREE INPUT TOO
        isEditing = true;
        
        // EXPLICITLY SHOW ENABLE EDIT BUTTON FOR FREE INPUT MODE
        const ec = document.getElementById('edit-controls');
        if(ec) ec.style.display = 'block';
    }

    // Unsaved Changes Warning
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = ''; // Chrome requires returnValue to be set
        }
    });

    // Clear flag on form submit
    const forms = document.querySelectorAll('form');
    forms.forEach(f => {
        f.addEventListener('submit', () => {
            hasUnsavedChanges = false;
        });
    });
});


// Custom Alert Helper
function showCustomAlert(title, message) {
    // Remove existing
    const existing = document.getElementById('custom-alert-overlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'custom-alert-overlay';
    overlay.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 9999;
        display: flex; align-items: center; justify-content: center;
        backdrop-filter: blur(2px); animation: fadeIn 0.2s;
    `;
    
    overlay.innerHTML = `
        <div style="background:white; padding:25px; border-radius:12px; width:400px; max-width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2); text-align:center; transform:scale(0.9); animation:popIn 0.3s forwards;">
            <div style="width:50px; height:50px; background:#fef2f2; border-radius:50%; color:#dc2626; display:flex; align-items:center; justify-content:center; margin:0 auto 15px auto;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <h3 style="margin:0 0 10px 0; color:#1e293b; font-size:18px;">${title}</h3>
            <p style="margin:0 0 20px 0; color:#64748b; font-size:14px; line-height:1.5;">${message}</p>
            <button onclick="document.getElementById('custom-alert-overlay').remove()" style="background:#dc2626; color:white; border:none; padding:10px 24px; border-radius:6px; font-weight:600; cursor:pointer; width:100%; transition:background 0.2s;">Mengerti</button>
        </div>
        <style>
            @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
            @keyframes popIn { from { transform:scale(0.9); opacity:0; } to { transform:scale(1); opacity:1; } }
        </style>
    `;
    
    document.body.appendChild(overlay);
}

// PRINT FUNCTION FOR FINAL TICKET SCRIPT
// EXPOSE TO GLOBAL SCOPE
</script>

</script>

<script>
// PRINT FUNCTION FOR FINAL TICKET SCRIPT
function printTicketScript() {
    console.log("Print button clicked");
    try {
        // 1. Populate Metadata
        const safeSetText = (id, val) => {
            const el = document.getElementById(id);
            if(el) el.textContent = val;
        };
        const safeDisplay = (id, val) => {
            const el = document.getElementById(id);
            if(el) el.style.display = val;
        }

        safeSetText('p-script-id', <?php echo json_encode($request['script_number']); ?>);
        safeSetText('p-generated-date', new Date().toLocaleString('id-ID'));
        
        safeSetText('p-script-num', <?php echo json_encode($request['script_number']); ?>);
        safeSetText('p-ticket-id', <?php echo json_encode($request['ticket_id']); ?>);
        safeSetText('p-title', <?php echo json_encode($request['title']); ?>);
        safeSetText('p-product', <?php echo json_encode($request['produk']); ?>);
        safeSetText('p-jenis', <?php echo json_encode($request['jenis']); ?>);
        safeSetText('p-kategori', <?php echo json_encode($request['kategori']); ?>);
        safeSetText('p-media', <?php echo json_encode($request['media']); ?>);
        safeSetText('p-created', <?php 
            $date = $request['created_at']; 
            if ($date instanceof DateTime) { echo json_encode($date->format('d M Y, H:i')); }
            else { echo json_encode(date('d M Y, H:i', strtotime($date))); }
        ?>);
        
        safeSetText('p-maker-name', <?php echo json_encode($request['created_by_name'] ?? "Maker"); ?>);
        safeSetText('p-maker-date', <?php 
            $date = $request['created_at']; 
            if ($date instanceof DateTime) { echo json_encode($date->format('d M Y, H:i')); }
            else { echo json_encode(date('d M Y, H:i', strtotime($date))); }
        ?>);

        // --- DYNAMIC TIMELINE POPULATION ---
        <?php 
        // Sanitize Timeline Dates (Convert DateTime objects to strings)
        $cleanTimeline = array_map(function($item) {
            if (isset($item['created_at']) && $item['created_at'] instanceof DateTime) {
                $item['created_at'] = $item['created_at']->format('Y-m-d H:i:s');
            }
            return $item;
        }, $timeline ?? []);
        ?>
        const timelineData = <?php echo json_encode($cleanTimeline); ?>;
        
        // Find Maker (First item or Role=Maker)
        const makerItem = timelineData.find(t => t.user_role === 'Maker');
        if (makerItem) {
             safeSetText('p-maker-name', makerItem.user_id);
             if (makerItem.group_name) safeSetText('p-maker-role', '(' + makerItem.group_name + ')');
        }

        // Find SPV approval (Robust Check)
        const spvItem = timelineData.find(t => t.user_role === 'SPV' && t.action.toUpperCase().includes('APPROVE'));
        if (spvItem) {
             safeSetText('p-spv-name', spvItem.user_id); // Show User ID/Name
             if(spvItem.group_name) safeSetText('p-spv-role', '(' + spvItem.group_name + ')'); // Use Group Name
             safeSetText('p-spv-date', new Date(spvItem.created_at).toLocaleString('id-ID'));
             safeSetText('p-spv-status', 'Approved');
        }

        // Find PIC approval (Robust Check)
        const picItem = timelineData.find(t => t.user_role === 'PIC' && t.action.toUpperCase().includes('APPROVE'));
        if (picItem) {
             safeSetText('p-pic-name', picItem.user_id); // Show User ID/Name
             if(picItem.group_name) safeSetText('p-pic-role', '(' + picItem.group_name + ')'); // Use Group Name
             safeSetText('p-pic-date', new Date(picItem.created_at).toLocaleString('id-ID'));
             safeSetText('p-pic-status', 'Approved');
        }
        // -----------------------------------

        // 3. Populate Approval Columns (Review Evidence)
        if (typeof REVIEW_EVIDENCE !== 'undefined') {
            const setApproval = (type, elementId) => {
                if (REVIEW_EVIDENCE[type] && REVIEW_EVIDENCE[type].length > 0) {
                    const el = document.getElementById(elementId);
                    if(el) {
                        let fileListHtml = '';
                        REVIEW_EVIDENCE[type].forEach(f => {
                            fileListHtml += `<div style="font-weight:bold; font-size:10px; margin-bottom:2px;">${f.filename}</div>`;
                        });
                        
                        el.innerHTML = `
                            ${fileListHtml}
                            <div style="font-size:9px; font-style:italic; margin-top:3px;">(Signed)</div>
                        `;
                    }
                }
            };

            setApproval('LEGAL', 'p-sign-legal');
            setApproval('CX', 'p-sign-cx');
            setApproval('SYARIAH', 'p-sign-syariah');
            setApproval('LPP', 'p-sign-lpp');
        }

        // 4. Populate Content
        const container = document.getElementById('p-content-container');
        if (container) {
            container.innerHTML = ''; // Clear previous

            if (IS_FILE_UPLOAD) {
                // File Upload Mode: COMPACT Filename display
                const box = document.createElement('div');
                box.className = 'print-content-box';
                // Compact styling
                box.style.textAlign = 'left'; 
                box.style.padding = '8px 12px';
                box.style.minHeight = 'auto'; // Let it shrink
                
                // Use REAL_FILENAME injected from PHP
                const fileName = typeof REAL_FILENAME !== 'undefined' ? REAL_FILENAME : 'Document';
                const fileExt = fileName.split('.').pop().toUpperCase();
                
                box.innerHTML = `
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-weight:bold; font-style:italic;">Attached File:</span>
                        <span style="border-bottom:1px solid #000;">${fileName}</span>
                         <span style="font-size:10px; border:1px solid #000; padding:1px 4px; border-radius:2px;">${fileExt}</span>
                    </div>
                `;
                container.appendChild(box);
            } else {
                // Free Input Mode: Loop through tabs/content
                if (Array.isArray(SERVER_CONTENT)) {
                    SERVER_CONTENT.forEach(item => {
                        const wrapper = document.createElement('div');
                        wrapper.style.marginBottom = '15px';
                        
                        const title = document.createElement('div');
                        title.style.fontWeight = 'bold';
                        title.style.marginBottom = '5px';
                        title.style.borderBottom = '1px dashed #ccc';
                        title.textContent = `Media: ${item.media}`;
                        
                        const contentBox = document.createElement('div');
                        contentBox.className = 'print-content-box';
                        contentBox.innerHTML = item.content; // Render HTML content
                        
                        wrapper.appendChild(title);
                        wrapper.appendChild(contentBox);
                        container.appendChild(wrapper);
                    });
                }
            }
        }

        // 3. Print
        window.print();
    } catch (err) {
        console.error("Print Error:", err);
        alert("Error creating print layout: " + err.message);
    }
}
window.printTicketScript = printTicketScript;
</script>

<?php require_once 'app/views/layouts/sidebar.php'; ?>
<?php require_once 'app/views/layouts/footer.php'; ?>
