<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';

$isFileUpload = ($request['mode'] === 'FILE_UPLOAD');
?>

<style>
/* Excel Sheet Tabs CSS */
.sheet-tabs-nav {
    display: flex;
    flex-wrap: wrap;
    border-bottom: 1px solid #ccc;
    background: #f1f1f1;
}
.btn-sheet {
    border: 1px solid #ccc;
    border-bottom: none;
    background: #e0e0e0;
    padding: 8px 16px;
    cursor: pointer;
    font-size: 13px;
    margin-right: 2px;
}
.btn-sheet.active {
    background: #fff;
    font-weight: bold;
    border-top: 2px solid green;
}
.sheet-pane {
    padding: 15px;
    background: #fff;
    border: 1px solid #ccc;
    border-top: none;
    overflow: auto;
}

/* READ-ONLY MODE STYLES */
.library-content-readonly {
    user-select: text; /* Allow text selection for copying */
    pointer-events: auto;
}

.library-content-readonly * {
    cursor: default !important;
}

/* IMPORTANT: Allow tab buttons to be clickable */
.library-content-readonly .btn-sheet,
.library-content-readonly .btn-media-tab {
    pointer-events: auto !important;
    cursor: pointer !important;
}

/* Remove any contenteditable from tables/content */
.library-content-readonly [contenteditable],
.library-content-readonly table,
.library-content-readonly .sheet-pane > * {
    -moz-user-modify: read-only !important;
    -webkit-user-modify: read-only !important;
    pointer-events: none !important;
}

.read-only-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}
</style>

<!-- Load SheetJS Local -->
<script src="public/assets/js/xlsx.full.min.js"></script>

<script>
// Sheet navigation for Excel preview (compatible with FileHandler output)
function changeSheet(sheetId) {
    const panes = document.querySelectorAll('.sheet-pane');
    const buttons = document.querySelectorAll('.btn-sheet');
    
    panes.forEach((pane) => {
        if (pane.id === sheetId) {
            pane.style.display = 'block';
        } else {
            pane.style.display = 'none';
        }
    });
    
    buttons.forEach((btn) => {
        // Check if button's onclick matches this sheet
        const onclickAttr = btn.getAttribute('onclick');
        if (onclickAttr && onclickAttr.includes(`'${sheetId}'`)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

// Media Tab Navigation (for Free Input Mode)
function switchMediaTab(index) {
    // Hide all panes
    document.querySelectorAll('.media-tab-pane').forEach(el => el.style.display = 'none');
    // Deactivate all buttons
    document.querySelectorAll('.btn-media-tab').forEach(el => {
        el.style.background = '#e5e7eb';
        el.style.color = '#374151';
        el.style.borderColor = 'transparent';
    });
    
    // Show target pane
    document.getElementById('media-pane-' + index).style.display = 'block';
    // Activate target button
    const btn = document.getElementById('media-btn-' + index);
    btn.style.background = '#3b82f6';
    btn.style.color = 'white';
}

// DOWNLOAD FUNCTION (USING SHEETJS)
function downloadContentAsExcel() {
    if (typeof XLSX === 'undefined') {
        alert('SheetJS library not loaded. Please ensure xlsx.full.min.js is in public/assets/js/');
        return;
    }

    // FIX: Support various content structures (File Upload, Free Input, Legacy Editor Save)
    let sheets = document.querySelectorAll('.sheet-pane, .downloadable-sheet, .media-pane, .review-tab-content');
    if (sheets.length === 0) {
        sheets = document.querySelectorAll('.media-tab-pane');
    }
    
    // Fallback: If no tab panes found, check for direct tables (Multi-sheet/Legacy)
    if (sheets.length === 0) {
        const tables = document.querySelectorAll('.library-content-readonly table');
        if (tables.length > 0) {
            sheets = tables; 
        }
    }
    
    const scriptNum = '<?php echo $request["script_number"] ?? "Unknown"; ?>';
    
    if (sheets.length === 0) {
        alert('No content available to download (Table not found)');
        return;
    }
    
    // Create new workbook
    const wb = XLSX.utils.book_new();
    let hasContent = false;
    
    sheets.forEach((sheet, index) => {
        // 1. Try data-media (Free Input Mode)
        let sheetName = sheet.getAttribute('data-media');
        
        // 2. Try finding matching tab button (File Upload Mode)
        if (!sheetName && sheet.id) {
            const sheetId = sheet.id;
            const buttons = document.querySelectorAll('.btn-sheet, .sheet-tab-btn, .btn-media-tab');
            
            for (let btn of buttons) {
                const clickAttr = btn.getAttribute('onclick');
                if (clickAttr && (clickAttr.includes(`'${sheetId}'`) || clickAttr.includes(`"${sheetId}"`))) {
                    sheetName = btn.innerText.trim();
                    break;
                }
            }
            
            if (!sheetName) {
                sheetName = sheetId.replace('sheet-', '');
            }
        }

        // 3. Fallback: Check Parent Element ID (For Legacy Data wrapping)
        if (!sheetName) {
             const parent = sheet.parentElement;
             if (parent && parent.id && parent.id.includes('sheet-')) {
                 sheetName = parent.id.replace('sheet-', '');
             }
        }

        // 4. Final Fallback Name using Index
        if (!sheetName) sheetName = "Sheet " + (index + 1);
        
        // Excel sheet names max 31 chars & forbidden chars
        sheetName = sheetName.replace(/[:\\/?*[\]]/g, '');
        if (sheetName.length > 31) sheetName = sheetName.substring(0, 31);
        
        // Convert HTML table to sheet
        let table = sheet.querySelector('table');
        // Handle case where sheet IS the table (Legacy/Raw fallback)
        if (!table && sheet.tagName === 'TABLE') {
            table = sheet;
        }

        let ws;
        
        if (table) {
            ws = XLSX.utils.table_to_sheet(table);
        } else {
            // Text content fallback
            ws = XLSX.utils.aoa_to_sheet([[sheet.innerText]]);
        }
        
        // APPLY BORDERS (Requires xlsx-js-style library)
        if (ws['!ref']) {
            const range = XLSX.utils.decode_range(ws['!ref']);
            for(let R = range.s.r; R <= range.e.r; ++R) {
                for(let C = range.s.c; C <= range.e.c; ++C) {
                    const cell_ref = XLSX.utils.encode_cell({c:C, r:R});
                    if(!ws[cell_ref]) ws[cell_ref] = { t: 's', v: '' }; // Force empty cell creation for border consistency
                    
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
                        ws[cell_ref].s.fill = { fgColor: { rgb: "E0E0E0" } }; // Light Grey using Hex
                        ws[cell_ref].s.font = { bold: true, color: { rgb: "000000" } };
                        ws[cell_ref].s.alignment.horizontal = 'center';
                        ws[cell_ref].s.alignment.vertical = 'center';
                    }
                }
            }
            
            
            // Set Column Widths (Force Update)
            ws['!cols'] = [
                { wch: 25 }, // A: Process
                { wch: 10 }, // B: Row
                { wch: 25 }, // C: Node
                { wch: 100 }, // D: Script Content (Widened significantly)
                { wch: 25 }, // E: Next Action
                { wch: 25 }  // F: Error Script/Other
            ];
        }
        
        XLSX.utils.book_append_sheet(wb, ws, sheetName);
        hasContent = true;
    });
    
    if (!hasContent) {
        alert('Failed to generate Excel content');
        return;
    }
    
    // Download file
    XLSX.writeFile(wb, 'Script_' + scriptNum + '.xlsx');
}

</script>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; align-items:center; gap:15px;">
            <a href="?controller=dashboard&action=library" style="background:#f1f5f9; color:#64748b; padding:8px; border-radius:50%; display:flex; align-items:center; justify-content:center; text-decoration:none; transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#334155'" onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            </a>
            <div>
                <h2 style="color:var(--primary-red); margin:0;">Library Script Detail</h2>
                <p style="color:var(--text-secondary);">Script No: <strong><?php echo htmlspecialchars($request['script_number']); ?></strong></p>
            </div>
        </div>
        

    </div>

    <!-- 2-COLUMN LAYOUT -->
    <div style="display:flex; gap:20px; align-items:flex-start;">
        
        <!-- LEFT COLUMN: MAIN CONTENT (75%) -->
        <div style="flex:1; min-width:0;">
             
             <!-- Metadata Card -->
            <div class="card" style="margin-bottom:20px;">
                <h4 style="border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:15px; margin-top:0;">Info & Metadata</h4>
                
                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; font-size:13px;">
                    <!-- Row 1 -->
                    <div>
                        <strong style="color:#64748b;">Created Date</strong> <br>
                        <?php 
                        if (isset($request['created_at'])) {
                            echo ($request['created_at'] instanceof DateTime) ? $request['created_at']->format('d M Y') : date('d M Y', strtotime($request['created_at']));
                        } else { echo "-"; }
                        ?>
                    </div>
                    <div>
                        <strong style="color:#64748b;">Published Date</strong> <br>
                        <?php 
                        $pubDate = $request['updated_at'] ?? $request['created_at'];
                        if (isset($pubDate)) {
                            echo ($pubDate instanceof DateTime) ? $pubDate->format('d M Y') : date('d M Y', strtotime($pubDate));
                        } else { echo "-"; }
                        ?>
                    </div>
                    <div>
                        <strong style="color:#64748b;">Maker</strong> <br> 
                        <?php echo htmlspecialchars($request['created_by'] ?? '-'); ?>
                    </div>
                    <div>
                         <strong style="color:#64748b;">Version</strong> <br> 
                         <span style="background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:4px; font-weight:bold;">v<?php echo htmlspecialchars($request['version'] ?? '1.0'); ?></span>
                    </div>

                    <!-- Row 2 -->
                    <div><strong style="color:#64748b;">Jenis</strong> <br><?php echo htmlspecialchars($request['jenis']); ?></div>
                    <div><strong style="color:#64748b;">Produk</strong> <br><?php echo htmlspecialchars($request['produk']); ?></div>
                    <div><strong style="color:#64748b;">Kategori</strong> <br><?php echo htmlspecialchars($request['kategori']); ?></div>
                </div>
            </div>

             <!-- Script Content -->
            <div class="card">
                <div style="border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
                    <h4 style="margin:0;">Script Content</h4>
                    
                    <!-- Moved Download Button Here for convenience -->
                    <?php if (isset($scriptFile) && !empty($scriptFile) && !empty($scriptFile['original_filename'])): ?>
                    <button onclick="downloadContentAsExcel()" style="background:#16a34a; color:white; padding:6px 12px; border:none; border-radius:4px; cursor:pointer; font-weight:600; font-size:12px; display:flex; align-items:center; gap:6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Export Excel
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="library-content-readonly">
                <?php if ($isFileUpload): ?>
                    <!-- File Upload Mode -->
                    <?php 
                         // FIX: Normalize Stream Resources (SQL Server TEXT/NVARCHAR) to String
                         // This is critical for stripos checks to work correctly
                         foreach ($content as &$rRef) {
                             if (isset($rRef['content']) && is_resource($rRef['content'])) {
                                 $rRef['content'] = stream_get_contents($rRef['content']);
                             }
                         }
                         unset($rRef);

                         $hasPrebuiltTabs = false;
                         foreach ($content as $row) {
                             // FIX: Check for ANY sign of prebuilt tabs (legacy or new)
                             if (
                                 stripos($row['content'], 'sheet-tabs-nav') !== false || 
                                 stripos($row['content'], 'btn-sheet') !== false ||
                                 stripos($row['content'], 'btn-media-tab') !== false ||
                                 stripos($row['content'], 'media-pane') !== false
                             ) {
                                 $hasPrebuiltTabs = true;
                                 break;
                             }
                         }
                    ?>

                    <?php if ($hasPrebuiltTabs && isset($content[0])): ?>
                         <?php 
                            $readonly_content = str_replace(['contenteditable="true"', "contenteditable='true'"], '', $content[0]['content']);
                            echo $readonly_content; 
                         ?>
                    <?php else: ?>
                        <?php 
                        // NEW LOGIC: If multiple sheets, render TABS first
                        if (count($content) > 1) {
                            echo '<div class="sheet-tabs-nav">';
                            foreach ($content as $idx => $row) {
                                $active = ($idx === 0) ? 'active' : '';
                                $mediaName = htmlspecialchars($row['media'] ?? 'Sheet '.($idx+1));
                                // Clean ID for switching
                                $cleanId = 'sheet-auto-' . $idx;
                                echo "<button class='btn-sheet $active' onclick=\"changeSheet('$cleanId')\">$mediaName</button>";
                            }
                            echo '</div>';
                        }
                        ?>

                        <?php foreach ($content as $idx => $row): ?>
                            <?php 
                                $readonly_content = str_replace(['contenteditable="true"', "contenteditable='true'"], '', $row['content']);
                                
                                // Dynamic ID matching the tabs above
                                $mediaId = (count($content) > 1) ? 'sheet-auto-' . $idx : 'sheet-' . htmlspecialchars(preg_replace('/[^a-zA-Z0-9_-]/', '', $row['media'] ?? 'Sheet'));
                                
                                // Visibility logic
                                $displayStyle = (count($content) > 1 && $idx > 0) ? 'display:none;' : 'display:block;';
                                
                                // Class sheet-pane is CRITICAL for the JS toggle function
                            ?>
                            <div id="<?php echo $mediaId; ?>" class="sheet-pane downloadable-sheet" style="<?php echo $displayStyle; ?>" data-media="<?php echo htmlspecialchars($row['media'] ?? $mediaId); ?>">
                                <?php echo $readonly_content; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Free Input Mode -->
                    <?php if (!empty($content)): ?>
                        <div style="display:flex; gap:10px; border-bottom:1px solid #e5e7eb; padding-bottom:10px; margin-bottom:15px; overflow-x:auto;">
                            <?php foreach ($content as $idx => $row): ?>
                                <button 
                                    id="media-btn-<?php echo $idx; ?>"
                                    class="btn-media-tab"
                                    onclick="switchMediaTab(<?php echo $idx; ?>)"
                                    style="padding:8px 16px; border:none; border-radius:20px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.2s; 
                                           background: <?php echo $idx === 0 ? '#3b82f6' : '#e5e7eb'; ?>; 
                                           color: <?php echo $idx === 0 ? 'white' : '#374151'; ?>;">
                                    <?php echo htmlspecialchars($row['media']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <?php foreach ($content as $idx => $row): ?>
                            <div id="media-pane-<?php echo $idx; ?>" data-media="<?php echo htmlspecialchars($row['media']); ?>" class="media-tab-pane" style="display: <?php echo $idx === 0 ? 'block' : 'none'; ?>;">
                                <div style="padding:20px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px;">
                                    <?php echo $row['content']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#888; font-style:italic;">No content available.</p>
                    <?php endif; ?>
                <?php endif; ?>
                </div> 
            </div>
        </div>

        <!-- RIGHT COLUMN: SIDEBAR (25%) -->
        <div style="width:300px; flex-shrink:0;">
            
            <!-- Review Documents -->
            <?php if (!empty($reviewDocs)): ?>
                <?php 
                $groupedDocs = [];
                foreach ($reviewDocs as $doc) {
                    $type = strtoupper($doc['file_type'] ?? 'OTHER');
                    if (!isset($groupedDocs[$type])) $groupedDocs[$type] = [];
                    $groupedDocs[$type][] = $doc;
                }
                ?>
            <div class="card" style="margin-bottom:20px;">
                <h4 style="border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:15px; margin-top:0;">Documents</h4>
                
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($groupedDocs as $type => $docs): ?>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">
                        <div style="background:#f1f5f9; padding:6px 10px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:8px;">
                            <span style="font-weight:700; color:#475569; font-size:11px;"><?php echo htmlspecialchars($type); ?></span>
                            <span style="background:#cbd5e1; color:#475569; padding:0px 5px; border-radius:10px; font-size:9px; font-weight:600;"><?php echo count($docs); ?></span>
                        </div>
                        
                        <div style="padding:2px 0;">
                            <?php foreach ($docs as $doc): ?>
                            <div style="padding:6px 10px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #f1f5f9;">
                                <div style="display:flex; align-items:center; gap:6px; overflow:hidden;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                    <span style="font-size:11px; color:#334155; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:130px;" title="<?php echo htmlspecialchars($doc['original_filename']); ?>">
                                        <?php echo htmlspecialchars($doc['original_filename']); ?>
                                    </span>
                                </div>
                                <a href="<?php echo htmlspecialchars($doc['filepath']); ?>" download style="color:#3b82f6; text-decoration:none;">⬇</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Approval History -->
            <?php if (!empty($logs)): ?>
            <div class="card" style="margin-bottom:20px;">
                <h4 style="border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:15px; margin-top:0;">History</h4>
                
                <div style="max-height:300px; overflow-y:auto; font-size:11px;">
                    <table style="width:100%; border-collapse:collapse;">
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:8px 0;">
                                    <div style="font-weight:700; color:#334155;">
                                        <?php echo htmlspecialchars($log['user_role']); ?>
                                    </div>
                                    <div style="color:#64748b; margin-bottom:2px;">
                                        <?php echo htmlspecialchars($log['user_id']); ?>
                                    </div>
                                    <div style="background:#f0fdf4; color:#15803d; display:inline-block; padding:2px 4px; border-radius:4px; font-size:10px; border:1px solid #bbf7d0;">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </div>
                                    <div style="color:#94a3b8; font-size:10px; margin-top:2px;">
                                        <?php 
                                            $dt = $log['created_at']; 
                                            if ($dt instanceof DateTime) echo $dt->format('d M Y H:i'); 
                                            else echo date('d M Y H:i', strtotime($dt));
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div> <!-- End Sidebar -->

    </div> <!-- End Flex Container -->

</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
