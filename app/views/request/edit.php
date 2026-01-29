<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
?>

<div class="main">
    <style>
    /* CSS for Excel Preview Tabs */
    .sheet-tabs-nav { 
        display: flex; 
        overflow-x: auto; 
        border-bottom: 1px solid #ccc; 
        background: #f1f1f1; 
        scrollbar-width: none; 
        -ms-overflow-style: none;
        /* CRITICAL: Force clickability even inside contenteditable */
        pointer-events: auto !important;
        user-select: none !important;
        -webkit-user-select: none !important;
    }
    .sheet-tabs-nav::-webkit-scrollbar { display: none; }
    
    .btn-sheet { 
        border: 1px solid #ccc; 
        border-bottom: none; 
        background: #e0e0e0; 
        padding: 8px 16px; 
        cursor: pointer !important; 
        font-size: 13px; 
        margin-right: 2px;
        /* CRITICAL: Force clickability */
        pointer-events: auto !important;
        user-select: none !important;
        -webkit-user-select: none !important;
    }
    .btn-sheet.active { background: #fff; font-weight: bold; border-top: 2px solid var(--primary-red); }
    .sheet-pane { padding: 15px; background: #fff; border: 1px solid #ccc; border-top: none; overflow: auto; max-height: 400px; }
    
    .form-group { margin-bottom: 12px; }
    .form-label { font-weight: bold; display: block; margin-bottom: 5px; font-size: 14px; }
    
    /* Plain Text Editor Styles */
    #shared-editor {
        width: 100%; height: 300px; padding: 15px; border: 1px solid #ccc; border-radius: 4px;
        font-family: 'Inter', system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.6;
        resize: vertical; outline: none; transition: border-color 0.2s;
        background: #fff; color: #333; box-sizing: border-box;
    }
    #shared-editor:focus { border-color: var(--primary-red); box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1); }

    /* Inline Comment CSS */
    .inline-comment { background-color: #fef08a; border-bottom: 2px solid #eab308; cursor: pointer; transition: background 0.2s; }
    .inline-comment:hover, .inline-comment.active { background-color: #fde047; }
    .comment-card { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.2s; border-left: 3px solid transparent; margin-bottom: 10px; }
    .comment-card.active { border-color: #eab308; border-left-color: #eab308; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .comment-header { font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 4px; }
    .comment-body { color: #334155; line-height: 1.4; }
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

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h2 style="color:var(--primary-red); margin:0;">Revise Script Request</h2>
        <?php if(isset($request['has_draft']) && $request['has_draft'] == 1): ?>
            <span style="background:#fef08a; color:#854d0e; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:bold; border:1px solid #fde047; display:flex; align-items:center; gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                DRAFT SAVED
            </span>
        <?php endif; ?>
    </div>
    
    <!-- REJECTION NOTE -->
    <?php if (!empty($rejectionNote)): ?>
    <div style="background:#fff5f5; border:1px solid #feb2b2; color:#c53030; padding:12px; border-radius:8px; margin-bottom:15px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
        <div style="display:flex; align-items:center; margin-bottom:6px;">
            <span style="font-size:16px; margin-right:8px;">⚠️</span>
            <strong style="font-size:14px;">Revision Note:</strong>
        </div>
        <div style="padding-left:24px; line-height:1.4; font-size:13px;">
            <?php echo nl2br(htmlspecialchars($rejectionNote)); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card" style="background:white; padding:20px; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
        <input type="hidden" id="request_id" value="<?php echo $request['id']; ?>">
        <input type="hidden" id="script_number" value="<?php echo $request['script_number']; ?>">
        
        <!-- Metadata Form -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
            
            <!-- JENIS -->
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <label class="form-label">Jenis</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="jenis" value="Konvensional" onchange="filterProduk()" <?php echo (strpos($request['jenis'], 'Konvensional')!==false)?'checked':''; ?>> Konvensional</label>
                    <label style="margin-left:15px;"><input type="checkbox" name="jenis" value="Syariah" onchange="filterProduk()" <?php echo (strpos($request['jenis'], 'Syariah')!==false)?'checked':''; ?>> Syariah</label>
                </div>
            </div>

            <!-- KATEGORI -->
            <?php 
                $kats = array_map('trim', explode(',', $request['kategori'])); 
            ?>
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <label class="form-label">Kategori</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="kategori" value="Pre Due" <?php echo in_array('Pre Due', $kats)?'checked':''; ?>> Pre Due</label>
                    <label style="margin-left:10px;"><input type="checkbox" name="kategori" value="Past Due" <?php echo in_array('Past Due', $kats)?'checked':''; ?>> Past Due</label>
                    <label style="margin-left:10px;"><input type="checkbox" name="kategori" value="Program Offer" <?php echo in_array('Program Offer', $kats)?'checked':''; ?>> Program Offer</label>
                </div>
            </div>

            <!-- PRODUK -->
             <?php 
                $prodVals = array_map('trim', explode(',', (string)$request['produk'])); 
                $otherProd = '';
                foreach ($prodVals as $p) {
                    if (strpos($p, 'Others:') !== false) $otherProd = trim(explode(':', $p)[1]);
                }
            ?>
            <div id="produk-container" style="grid-column: span 2; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <label class="form-label">Produk</label>
                
                <div id="produk-konv" style="display:<?php echo (strpos($request['jenis'], 'Konvensional')!==false)?'block':'none'; ?>; padding:10px; background:white; border-left:4px solid var(--primary-red); margin-bottom:8px; border-radius:4px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <strong style="display:block;margin-bottom:6px;font-size:11px;color:#d32f2f;letter-spacing:1px;">KONVENSIONAL</strong>
                    <label><input type="checkbox" name="produk" value="Kartu Kredit" <?php echo in_array('Kartu Kredit', $prodVals)?'checked':''; ?>> Kartu Kredit</label>
                    <label><input type="checkbox" name="produk" value="Extra Dana" <?php echo in_array('Extra Dana', $prodVals)?'checked':''; ?>> Extra Dana</label>
                    <label><input type="checkbox" name="produk" value="KPR" <?php echo in_array('KPR', $prodVals)?'checked':''; ?>> KPR</label>
                    <label><input type="checkbox" name="produk" value="Others" onchange="toggleInput('prod_konv_other', this.checked)" <?php echo ($otherProd)?'checked':''; ?>> Others</label>
                    <input type="text" id="prod_konv_other" class="form-control" style="display:<?php echo ($otherProd)?'block':'none'; ?>; margin-top:10px; width:250px; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Other product..." value="<?php echo htmlspecialchars($otherProd); ?>">
                </div>

                <div id="produk-syariah" style="display:<?php echo (strpos($request['jenis'], 'Syariah')!==false)?'block':'none'; ?>; padding:10px; background:white; border-left:4px solid #16a34a; margin-bottom:8px; border-radius:4px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <strong style="display:block;margin-bottom:6px;font-size:11px;color:#16a34a;letter-spacing:1px;">SYARIAH</strong>
                    <label><input type="checkbox" name="produk" value="Kartu Syariah" <?php echo in_array('Kartu Syariah', $prodVals)?'checked':''; ?>> Kartu Syariah</label>
                    <label><input type="checkbox" name="produk" value="Extra Dana iB" <?php echo in_array('Extra Dana iB', $prodVals)?'checked':''; ?>> Extra Dana iB</label>
                    <label><input type="checkbox" name="produk" value="KPR iB" <?php echo in_array('KPR iB', $prodVals)?'checked':''; ?>> KPR iB</label>
                </div>
            </div>

            <!-- TUJUAN (using title column) -->
            <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; grid-column: span 2;">
                <label class="form-label">Judul Script / Tujuan</label>
                <textarea id="title" class="form-control" rows="2" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-family:'Inter', sans-serif; resize:vertical;" placeholder="Jelaskan tujuan dari script ini..."><?php echo htmlspecialchars($request['title'] ?? ''); ?></textarea>
            </div>

            <!-- MEDIA -->
             <?php $medVals = array_map('trim', explode(',', (string)$request['media'])); ?>
            <div style="grid-column: span 2; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <label class="form-label">Media</label>
                <div class="checkbox-group" id="media-list">
                    <label><input type="checkbox" name="media" value="WhatsApp" onchange="updateFreeInputTabs()" <?php echo in_array('WhatsApp', $medVals)?'checked':''; ?>> WhatsApp</label>
                    <label><input type="checkbox" name="media" value="SMS" onchange="updateFreeInputTabs()" <?php echo in_array('SMS', $medVals)?'checked':''; ?>> SMS</label>
                    <label><input type="checkbox" name="media" value="Email" onchange="updateFreeInputTabs()" <?php echo in_array('Email', $medVals)?'checked':''; ?>> Email</label>
                    <label><input type="checkbox" name="media" value="Robocoll" onchange="updateFreeInputTabs()" <?php echo in_array('Robocoll', $medVals)?'checked':''; ?>> Robocoll</label>
                    <label><input type="checkbox" name="media" value="Surat" onchange="updateFreeInputTabs()" <?php echo in_array('Surat', $medVals)?'checked':''; ?>> Surat</label>
                    <label><input type="checkbox" name="media" value="VB" onchange="updateFreeInputTabs()" <?php echo in_array('VB', $medVals)?'checked':''; ?>> VB</label>
                    <label><input type="checkbox" name="media" value="Chatbot" onchange="updateFreeInputTabs()" <?php echo in_array('Chatbot', $medVals)?'checked':''; ?>> Chatbot</label>
                </div>
            </div>
        </div>

        <!-- MODE TABS -->
        <!-- MODE TABS (LOCKED) -->
        <div class="tabs">
            <div class="tab-item <?php echo ($request['mode']=='FILE_UPLOAD')?'active':'disabled'; ?>" 
                 style="<?php echo ($request['mode']!='FILE_UPLOAD') ? 'opacity:0.5; cursor:not-allowed; background:#f1f5f9; color:#94a3b8;' : ''; ?>">
                 File Upload
            </div>
            <div class="tab-item <?php echo ($request['mode']!='FILE_UPLOAD')?'active':'disabled'; ?>" 
                 style="<?php echo ($request['mode']=='FILE_UPLOAD') ? 'opacity:0.5; cursor:not-allowed; background:#f1f5f9; color:#94a3b8;' : ''; ?>">
                 Free Input
            </div>
        </div>
        <input type="hidden" id="input_mode" value="<?php echo $request['mode']; ?>">

        <!-- VIEW: UPLOAD (DROP ZONE ONLY) -->
        <div id="upload-panel" style="display:<?php echo ($request['mode']=='FILE_UPLOAD')?'block':'none'; ?>;">
            <div class="upload-area" id="drop-zone" onclick="document.getElementById('fileInput').click()">
                <div style="font-size:40px; margin-bottom:10px;">📄</div>
                <h4 style="margin:0;">Upload New Version (Optional)</h4>
                <p style="color:#666; font-size:14px; margin:5px 0;">Drag & drop or click to replace current file</p>
                <input type="file" id="fileInput" hidden onchange="handleFileSelect(this)" accept=".xls,.xlsx,.doc,.docx">
            </div>
            <div id="upload-status" style="margin-top:10px; color:#666; font-style:italic;"></div>
        </div>

        <!-- UNIFIED WORKSPACE (SHARED SIDEBAR) -->
        <div style="margin-top:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                <label class="form-label" style="margin-bottom:0;">Live Preview & Fixer</label>
                <div style="font-size:11px; color:#64748b;">
                    * Langsung ketik di bawah untuk revisi. Gunakan tombol warna jika perlu.
                </div>
            </div>

            <div class="split-container" style="display:flex; gap:12px; align-items:stretch; min-height:400px; max-height:800px;">
                
                <!-- LEFT COLUMN: EDITORS (SWITCHABLE) -->
                <div style="flex:1; display:flex; flex-direction:column; min-width:0;">
                    
                    <!-- 1. FILE UPLOAD EDITOR -->
                    <div id="editor-container" style="border:2px solid #3b82f6; border-radius:8px; background:white; display:<?php echo ($request['mode']=='FILE_UPLOAD')?'flex':'none'; ?>; flex-direction:column; height:600px;">
                        <!-- EMBEDDED TOOLBAR -->
                        <div id="internal-toolbar" style="background:#f1f5f9; padding:8px 12px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:flex-end; align-items:center; gap:10px;">
                             <div style="display:flex; align-items:center; gap:2px; margin-right:auto;">
                                <span style="font-size:11px; font-weight:bold; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Mode:</span>
                                <span style="font-size:11px; color:#3b82f6; font-weight:bold; background:#eff6ff; padding:2px 6px; border-radius:4px;">File Upload Preview</span>
                             </div>
                             <div id="color-tools" style="display:flex; align-items:center; gap:6px;">
                                 <button type="button" onclick="performUndo()" style="background:white; color:#475569; border:1px solid #cbd5e1; border-radius:4px; padding:4px 8px; cursor:pointer;" title="Undo (Ctrl+Z)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"></path><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"></path></svg>
                                 </button>
                                 <button type="button" onclick="performRedo()" style="background:white; color:#475569; border:1px solid #cbd5e1; border-radius:4px; padding:4px 8px; cursor:pointer;" title="Redo (Ctrl+Y)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"></path><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 3.7"></path></svg>
                                 </button>
                             </div>
                        </div>

                        <!-- SCROLLABLE CONTENT AREA -->
                        <div id="editor-content" contenteditable="true" style="flex:1; padding:15px; overflow:auto; outline:none; font-family:'Inter', sans-serif; font-size:13px; line-height:1.6;" onkeyup="applyMakerColor()" onclick="applyMakerColor()">
                        <?php 
                            if ($request['mode'] === 'FILE_UPLOAD' && !empty($content)) {
                                // DETECT IF CONTENT ALREADY HAS TABS (Legacy or Pre-built)
                                $hasPrebuiltTabs = (strpos($content[0]['content'], 'sheet-tabs-nav') !== false);
                                
                                if ($hasPrebuiltTabs) {
                                    // Case A: Content is already a full container
                                    echo $content[0]['content'];
                                } 
                                elseif (count($content) > 1) {
                                    // Case B: Multi-Sheet Split System
                                    // Render Tabs
                                    echo '<div class="sheet-tabs-nav">';
                                    foreach ($content as $idx => $row) {
                                        $active = ($idx === 0) ? 'active' : '';
                                        $media = htmlspecialchars($row['media'] ?? 'Part ' . ($idx+1));
                                        // Use changeSheet() helper defined in JS
                                        echo "<div id='btn-tab-media-$idx' class='btn-sheet btn-media-tab $active' onclick=\"changeSheet('tab-media-$idx')\">$media</div>";
                                    }
                                    echo '</div>';
                                    
                                    // Render Panes
                                    foreach ($content as $idx => $row) {
                                        $display = ($idx === 0) ? 'block' : 'none';
                                        echo "<div id='tab-media-$idx' class='media-pane sheet-pane' style='display:$display'>";
                                        echo $row['content']; 
                                        echo "</div>";
                                    }
                                } 
                                else {
                                    // Case C: Single raw sheet
                                    echo $content[0]['content']; 
                                }
                            } else {
                                echo '<p style="color:#999; font-style:italic;">No file preview available.</p>';
                            }
                        ?>
                        </div>
                    </div>

                    <!-- 2. FREE INPUT EDITOR -->
                    <div id="manual-panel" style="display:<?php echo ($request['mode']!='FILE_UPLOAD')?'block':'none'; ?>;">
                         <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <label class="form-label" style="margin:0;">Isi Script (Per Media)</label>
                            <div style="font-size:12px; color:#666;">
                                Editing: <span id="active-media-label" style="font-weight:bold; color:var(--primary-red);">None Selected</span>
                            </div>
                        </div>

                        <?php 
                        $mediaTypes = ['WhatsApp', 'SMS', 'Email', 'Robocoll', 'Surat', 'VB', 'Chatbot', 'Others'];
                        ?>
                        
                        <div id="static-tabs-nav" class="sheet-tabs-nav">
                            <?php foreach($mediaTypes as $media): ?>
                                <div id="tab-btn-<?= $media ?>" class="btn-sheet" onclick="activateSharedTab('<?= $media ?>')" style="display:none;">
                                    <?= $media ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div id="shared-editor-container" class="sheet-pane" style="padding:0; height:auto; border:2px solid #3b82f6; border-top:none; border-bottom-left-radius:8px; border-bottom-right-radius:8px;">
                            <!-- TOOLBAR for Free Input (Optional, for consistency) -->
                            <div style="background:#f1f5f9; padding:6px 12px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                                 <div style="display:flex; align-items:center; gap:6px;">
                                     <button type="button" onclick="performUndo()" style="background:white; border:1px solid #cbd5e1; padding:2px 6px; border-radius:4px; cursor:pointer;" title="Undo">↩</button>
                                     <button type="button" onclick="performRedo()" style="background:white; border:1px solid #cbd5e1; padding:2px 6px; border-radius:4px; cursor:pointer;" title="Redo">↪</button>
                                 </div>
                            </div>
                            <div id="shared-editor" contenteditable="true" oninput="syncToStorage()" onkeyup="applyMakerColor()" onclick="applyMakerColor()" style="min-height:500px; padding:15px; outline:none; font-family:'Inter', sans-serif; font-size:13px; line-height:1.6;"></div>
                        </div>
                        
                        <!-- Hidden Storage -->
                        <?php foreach($mediaTypes as $media): ?>
                            <?php 
                                $existingText = '';
                                if (!empty($content)) {
                                    foreach($content as $row) {
                                        $rowSheetName = $row['sheet_name'] ?? $row['media'] ?? '';
                                        if (strtoupper($rowSheetName) === strtoupper($media)) {
                                            $existingText = $row['content'];
                                            break;
                                        }
                                    }
                                }
                            ?>
                            <textarea id="storage-<?= $media ?>" style="display:none;"><?php echo htmlspecialchars($existingText); ?></textarea>
                        <?php endforeach; ?>
                    </div>

                </div>

                <!-- RIGHT COLUMN: SHARED SIDEBAR -->
                <div id="comment-sidebar" style="width:300px; flex-shrink:0; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; overflow-y:auto; display:none; height:600px;">
                    <div style="font-size:12px; font-weight:bold; color:#64748b; margin-bottom:10px; text-transform:uppercase; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">Supervisor Notes</div>
                    <div id="comment-list"></div>
                </div>

            </div>
        </div>

        <!-- RE-SUBMIT SECTION -->
         <div style="margin-top:20px; padding:12px; background:#fff; border-radius:8px; border:1px solid #e2e8f0; border-top:3px solid var(--primary-red); shadow:0 1px 2px rgba(0,0,0,0.05);">
            <h4 style="margin:0 0 10px 0; color:#1e293b; font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-red);"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                Resubmit Revision
            </h4>

            <div class="form-group" style="margin-bottom:15px;">
                <label class="form-label" style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Catatan Perbaikan <span style="color:red">*</span></label>
                <textarea id="maker_note" class="form-control" rows="2" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; background-color:#f8fafc; font-family:inherit; font-size:13px; resize:none;" placeholder="Jelaskan apa saja yang telah diperbaiki..."><?php echo isset($draftNote)?htmlspecialchars($draftNote):''; ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label class="form-label" style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Pilih Supervisor (For Re-Approval) <span style="color:red">*</span></label>
                <select id="selected_spv" class="form-select" style="width:100%; max-width:400px; padding:10px; border:1px solid #cbd5e1; border-radius:8px; background-color:#f8fafc; font-weight:600; font-size:13px; color:#334155; cursor:pointer;">
                    <option value="">-- Pilih SPV --</option>
                    <?php foreach ($spvList as $spv) : ?>
                        <option value="<?php echo htmlspecialchars($spv['userid']); ?>" <?php echo ($request['selected_spv'] == $spv['userid'])?'selected':''; ?>>
                            <?php echo htmlspecialchars($spv['fullname']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <p style="font-size:11px; color:#94a3b8; margin-top:10px; font-style:italic;">Pastikan semua perbaikan sudah sesuai dengan catatan supervisor.</p>
        </div>

        <!-- ACTION BUTTONS -->
        <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px; padding-top:20px; border-top:1px solid #e2e8f0;">
            <a href="index.php" class="btn-cancel" style="padding:10px 24px; text-decoration:none; border:1px solid #cbd5e1; border-radius:6px; color:#64748b; background:white; font-weight:600; font-size:14px; display:inline-flex; align-items:center; transition:all 0.2s;">Cancel</a>
            <button class="btn btn-secondary" onclick="submitUpdate(true)" style="background:white; color:#64748b; border:1px solid #cbd5e1; padding:10px 24px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer; transition:all 0.2s;">Save Draft</button>
            <button class="btn btn-primary" onclick="submitUpdate(false)" style="background:var(--primary-red); color:white; border:none; padding:10px 24px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer; box-shadow:0 2px 4px rgba(211,47,47,0.3); transition:all 0.2s;">Resubmit Request</button>
        </div>
    </div>
</div>

<!-- Modals replaced by SweetAlert2 -->

<script>
    // ===== CRITICAL: DEFINE GLOBAL FUNCTIONS FIRST =====
    
    // Global Tab Switcher for Excel Preview (File Upload Mode)
    // Must be defined early for onclick handlers
    window.changeSheet = function(sheetId) {
        // PREVENT HANG: Suspend observer during tab switch
        isInternalChange = true;
        
        // Hide standard panes AND legacy media panes
        document.querySelectorAll('.sheet-pane, .media-pane').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.btn-sheet').forEach(el => el.classList.remove('active'));
        
        const target = document.getElementById(sheetId);
        if (target) target.style.display = 'block';
        
        // Use ID selector (more robust), with fallback
        let btn = document.getElementById('btn-' + sheetId);
        if (!btn) {
            // Fallback for old HTML without IDs or Legacy style
            btn = document.querySelector(`button[onclick*="'${sheetId}'"]`);
            if (!btn) btn = document.getElementById(sheetId.replace('tab-media-', 'tab-media-btn-')); // Legacy fallback
        }
        if (btn) btn.classList.add('active');
        
        // Re-enable observer after short delay
        setTimeout(() => isInternalChange = false, 100);
    };
    
    // ===== VARIABLES =====
    let selectedFile = null;
    let currentActiveMedia = null;
    const SERVER_CONTENT = <?php echo json_encode($content); ?>;
    let lastPreviewHtml = (SERVER_CONTENT.length > 0) ? SERVER_CONTENT[0].content : '';

    // Helper to fix Excel Tabs in ContentEditable
    function makeTabsNonEditable() {
        const editor = document.getElementById('editor-content');
        if (!editor) return;
        
        // Find common tab containers (adjust selector based on your HTML generator)
        // Usually PHPExcel/PhpSpreadsheet uses specific classes or inline styles
        // Or if it matches '.sheet-tabs-nav' logic or buttons
        const potentialTabs = editor.querySelectorAll('.sheet-tabs-nav, .nav-tabs, ul[role="tablist"], div[style*="border-bottom"] button');
        
        potentialTabs.forEach(el => {
            el.contentEditable = "false";
            // Determine parent wrapper if needed
            if(el.parentElement) el.parentElement.contentEditable = "false";
        });
        
        // Specific fix for the 'btn-sheet' class if injected
        // Don't set contentEditable=false on buttons individually, as parent .sheet-tabs-nav handles it.
        // And setting it on button might block click in some browsers.
        const sheetBtns = editor.querySelectorAll('.btn-sheet');
        sheetBtns.forEach(btn => {
            btn.style.cursor = "pointer"; // Force pointer
        });
    }

    // Call this after any new content load (e.g. file upload)
    function afterContentLoad() {
        makeTabsNonEditable();
        renderSideComments();
        updateSheetTabBadges();
    }

    function updateTabBadges() {
        // MATCHING STYLE WITH FILE UPLOAD MODE
        const mediaTypes = ['WhatsApp', 'SMS', 'Email', 'Robocoll', 'Surat', 'VB', 'Chatbot', 'Others'];
        mediaTypes.forEach(media => {
            const storage = document.getElementById('storage-' + media);
            const btn = document.getElementById('tab-btn-' + media);
            if (storage && btn) {
                // ROBUST CHECK: Parse HTML to find elements
                // Create a temp dummy element to parse the string
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = storage.value;
                
                // Check if any element has the required classes
                const hasComments = tempDiv.querySelector('.inline-comment, .revision-span');

                if (hasComments) {
                    // Add badge if not exists
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
                        dot.title = "Has Unresolved Revisions";
                        btn.appendChild(dot);
                    }
                } else {
                     // Remove badge if resolved
                     const dot = btn.querySelector('.tab-badge-dot');
                     if(dot) dot.remove();
                }
            }
        });
    }

    // --- ADVANCED UNDO / REDO HISTORY ENGINE (Content + Actions) ---
    const historyStack = { undo: [], redo: [] };
    let isInternalChange = false; // Flag to prevent observer loops
    let debounceTimer = null;

    // INIT OBSERVER ON LOAD
    // Helper to get active editor
    function getCurrentEditor() {
         const uploadMode = document.getElementById('upload-panel').style.display !== 'none';
         return uploadMode ? document.getElementById('editor-content') : document.getElementById('shared-editor');
    }

    // INIT OBSERVER ON LOAD (Target BODY or handle dynamic switching)
    document.addEventListener('DOMContentLoaded', () => {
        // Observer needs to attach to BOTH editors
        ['editor-content', 'shared-editor'].forEach(id => {
            const editor = document.getElementById(id);
            if (editor) {
                // Initial State
                if (id === 'editor-content') {
                     historyStack.undo.push({ action: 'content-change', html: editor.innerHTML });
                }

                const observer = new MutationObserver((mutations) => {
                    if (isInternalChange) return;
                    // Debounce
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        // Push State of CURRENTLY ACTIVE EDITOR
                        const activeEd = getCurrentEditor();
                        if (activeEd) {
                            pushSnapshot(activeEd.innerHTML);
                            // FIX: Auto-update sidebar when content changes (Deleted text -> remove card)
                            renderSideComments();
                            updateSheetTabBadges();
                        }
                    }, 500);
                });
                
                observer.observe(editor, { childList: true, subtree: true, characterData: true, attributes: true });
            }
        });
    });

    function pushSnapshot(html) {
        // Optimize: Don't push if same as last
        const last = historyStack.undo[historyStack.undo.length - 1];
        if (last && last.action === 'content-change' && last.html === html) return;
        
        historyStack.undo.push({ action: 'content-change', html: html });
        historyStack.redo = []; 
        updateUndoRedoUI();
        // REMOVED: markUnsaved() - will be called by explicit actions only
    }

    function pushHistory(action, data) {
        historyStack.undo.push({ action, data });
        historyStack.redo = []; 
        updateUndoRedoUI();
    }

    function performUndo() {
        if (historyStack.undo.length <= 1) return; // Keep initial state

        const lastAction = historyStack.undo.pop();
        historyStack.redo.push(lastAction);
        
        // Peek previous state to restore
        const prevState = historyStack.undo[historyStack.undo.length - 1];
        
        if (lastAction.action === 'content-change') {
            applyContentRestoration(prevState.html);
        } else if (lastAction.action === 'resolve') {
            // Special Logic for Resolve: Revert DOM + Attribute
            applyContentRestoration(lastAction.data.parentHTML);
        }
        
        renderSideComments(); 
        updateSheetTabBadges();
        updateUndoRedoUI();
    }

    function performRedo() {
        const nextAction = historyStack.redo.pop();
        if (!nextAction) return;

        historyStack.undo.push(nextAction);

        if (nextAction.action === 'content-change') {
            applyContentRestoration(nextAction.html);
        } else if (nextAction.action === 'resolve') {
            // Re-Apply Resolve (Unwrap logic tricky here because DOM changed)
            // Better to rely on the Snapshot stored in "parentHTML" if we unified logic?
            // Actually, for consistency, "Resolve" should ALSO trigger a Mutation. 
            // BUT to keep "Resolve" atomic with attributes, we handle it explicitly.
            // Simplified: If 'resolve' data contained the AFTER html, we could just restore that.
            // For now, let's just trigger the unwrap logic again if element exists.
             const spanId = nextAction.data.spanId;
             const span = document.querySelector(`[data-comment-id="${spanId}"], #${spanId}`);
             if (span) {
                const parent = span.parentNode;
                while (span.firstChild) parent.insertBefore(span.firstChild, span);
                parent.removeChild(span);
             }
        }

        renderSideComments();
        updateSheetTabBadges();
        updateUndoRedoUI();
    }

    function applyContentRestoration(html) {
        const editor = getCurrentEditor(); // Use Active Editor
        if (editor) {
            isInternalChange = true;
            editor.innerHTML = html || "";
            setTimeout(() => isInternalChange = false, 50);
        }
    }

    function updateUndoRedoUI() {
        // Optional: Toggle buttons visibility
    }

    // Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
            e.preventDefault();
            performUndo();
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'y') {
            e.preventDefault();
            performRedo();
        }
    });

    // Modified Resolve Logic with History
    // === RESOLVE COMMENT LOGIC ===
    function resolveComment(id, event) {
        event.stopPropagation(); 
        
        // 1. INSTANT DISABLE BUTTON (Prevent multiple clicks)
        const btn = event.target.closest('.btn-resolve');
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            btn.innerHTML = '<span style="font-size:10px;">Processing...</span>';
        }
        
        // 2. Find and modify the span
        // 2. Find and modify the span
        let span = document.getElementById(id);
        if(!span) span = document.querySelector(`.inline-comment[data-comment-id='${id}'], .revision-span[id='${id}']`);
        
        if (!span) {
            console.warn('Comment span not found:', id);
            // GRACEFUL HANDLING: If span is missing, it means user deleted the text.
            // Treat as "Resolved/Deleted" update.
            if(btn) {
                btn.innerHTML = 'Scan Update...';
            }
            // Force refresh sidebar to remove the stale card
            setTimeout(() => {
                renderSideComments();
                updateSheetTabBadges();
            }, 500);
            return;
        }

        // Visual Feedback: Remove highlight but keep text
        span.classList.remove('inline-comment');
        span.classList.remove('revision-span');
        span.style.backgroundColor = ''; // Clear yellow
        
        // Add resolved class for tracking
        span.classList.add('resolved-comment');
        span.style.textDecoration = "none"; // Ensure no strikethrough

        // Update Undo History
        pushSnapshot(getCurrentEditor().innerHTML);

        // Update UI (with debounce)
        renderSideComments();
        updateSheetTabBadges();
        if (typeof updateTabBadges === 'function') updateTabBadges(); // FIX: Update Free Input Badges
        markUnsaved(); // Mark dirty
    }

    // Render control flags
    let isRendering = false;
    let rafId = null;

    function renderSideComments() {
        // Cancel any pending RAF
        if (rafId) {
            cancelAnimationFrame(rafId);
            console.log('[SIDEBAR] Cancelled pending RAF');
        }
        
        // Schedule render on next animation frame (sync with browser paint)
        rafId = requestAnimationFrame(() => {
            rafId = null;
            actualRenderSideComments();
        });
    }

    function actualRenderSideComments() {
        // Prevent duplicate rendering
        if (isRendering) {
            console.log('[SIDEBAR] Already rendering, skipping...');
            return;
        }
        
        console.log('[SIDEBAR] Starting render...');
        isRendering = true;
        
        try {
            const list = document.getElementById('comment-list');
            const sidebar = document.getElementById('comment-sidebar');
            if(!list || !sidebar) {
                console.warn('[SIDEBAR] List or sidebar element not found');
                return;
            }
            
            // CRITICAL: Clear all existing cards first
            list.innerHTML = '';
            console.log('[SIDEBAR] Cleared existing cards');
            
            // 1. Unresolved Comments
            // FIX: Include legacy red-colored spans
            const selector = '.inline-comment, .revision-span, ' + 
                             'span[style*="color: rgb(239, 68, 68)"], span[style*="color:#ef4444"], span[style*="color: #ef4444"], span[style*="color:red"]';
            
            // SCOPED SEARCH: Only look inside editors to avoid picking up UI elements (like form asterisks)
            const roots = [document.getElementById('editor-content'), document.getElementById('shared-editor')];
            let rawSpans = [];

            roots.forEach(root => {
                if (root) {
                    const found = root.querySelectorAll(selector);
                    rawSpans = [...rawSpans, ...Array.from(found)];
                }
            });

            const activeComments = rawSpans.map((span, index) => {
                 // AUTO-ASSIGN ID for Legacy items
                 if (!span.id && !span.getAttribute('data-comment-id')) {
                     span.id = 'legacy-rev-' + index + '-' + Date.now();
                     span.classList.add('revision-span'); // Normalize logic
                 }

                 return {
                     id: span.getAttribute('data-comment-id') || span.id,
                     element: span,
                     text: span.getAttribute('data-comment-text') || span.innerText,
                     user: span.getAttribute('data-comment-user') || 'Reviewer',
                     time: span.getAttribute('data-comment-time') || '',
                     isRevision: true, // Legacy are always revisions
                     isResolved: false
                 };
            });

            // 2. Resolved Comments (Tracked by class 'resolved-comment' or History)
            const resolvedComments = Array.from(document.querySelectorAll('.resolved-comment')).map(span => ({
                 id: span.getAttribute('data-comment-id') || span.id,
                 element: span,
                 text: span.getAttribute('data-comment-text') || span.getAttribute('title') || "Resolved Item",
                 user: span.getAttribute('data-comment-user') || 'Reviewer',
                 time: span.getAttribute('data-comment-time') || '',
                 isRevision: false,
                 isResolved: true
            }));

            const allItems = [...activeComments, ...resolvedComments];
            console.log('[SIDEBAR] Found items:', { active: activeComments.length, resolved: resolvedComments.length, total: allItems.length });

            // CRITICAL: Deduplicate by ID using Set
            const uniqueItems = [];
            const seenIds = new Set();
            
            allItems.forEach(item => {
                // Only add if we haven't seen this ID before
                if (item.id && !seenIds.has(item.id)) {
                    seenIds.add(item.id);
                    uniqueItems.push(item);
                } else if (item.id) {
                    console.warn('[SIDEBAR] Skipping duplicate comment ID:', item.id);
                }
            });
            
            console.log('[SIDEBAR] After deduplication:', { unique: uniqueItems.length, duplicates: allItems.length - uniqueItems.length });

            if (uniqueItems.length === 0) {
                sidebar.style.display = 'none';
                console.log('[SIDEBAR] No items, hiding sidebar');
                return;
            }
            
            sidebar.style.display = 'block';

            // Render Cards (using uniqueItems instead of allItems)
            uniqueItems.forEach(c => {
                const card = document.createElement('div');
                card.className = 'comment-card';
                if (c.isResolved) card.classList.add('resolved');

                let iconStr = (c.user||'R').charAt(0).toUpperCase();
                let iconBg = '#eff6ff';
                let iconColor = '#3b82f6';
                let borderColor = '#dbeafe';
                let titleText = c.user;
                
                if (c.isRevision) {
                    iconBg = '#fef2f2';
                    iconColor = '#ef4444'; 
                    borderColor = '#fecaca';
                    titleText = "Revision Required";
                    iconStr = "!";
                }

                if (c.isResolved) {
                    iconBg = '#dcfce7';
                    iconColor = '#16a34a';
                    borderColor = '#bbf7d0';
                    iconStr = "✓";
                    titleText = "Resolved";
                }

                let actionButton = '';
                if (!c.isResolved) {
                    actionButton = `
                    <button onclick="resolveComment('${c.id}', event)" class="btn-resolve" style="width:100%; margin-top:10px; padding:6px 12px; background:white; border:1px solid #16a34a; color:#16a34a; border-radius:6px; font-weight:600; font-size:11px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:all 0.2s;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Mark as Done
                    </button>`;
                } else {
                    actionButton = `
                    <div style="margin-top:8px; width:100%; text-align:center; font-size:10px; color:#16a34a; font-weight:bold; padding:4px; background:#f0fdf4; border-radius:4px;">
                        ✅ Resolved
                    </div>`;
                }

                card.innerHTML = `
                    <div style="background:white; border:1px solid ${c.isResolved ? '#bbf7d0' : '#e2e8f0'}; border-radius:10px; padding:12px; margin-bottom:10px; box-shadow:0 1px 2px rgba(0,0,0,0.02); transition:opacity 0.2s; opacity:${c.isResolved ? '0.6' : '1'};">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                            <div style="width:24px; height:24px; background:${iconBg}; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; color:${iconColor}; font-weight:bold; border:1px solid ${borderColor}; flex-shrink:0;">
                                ${iconStr}
                            </div>
                            <div>
                                <div style="font-size:12px; font-weight:700; color:#334155;">${titleText}</div>
                                <div style="font-size:10px; color:#94a3b8;">${c.time}</div>
                            </div>
                        </div>
                        <div style="font-size:12px; color:#334155; line-height:1.4; word-wrap:break-word;">
                            ${c.text}
                        </div>
                        ${actionButton}
                    </div>
                `;

                card.onclick = (e) => {
                    if (e.target.closest('button')) return; 

                    // 1. Cross-Tab Navigation (Robust)
                    const parentSheet = c.element.closest('.sheet-pane, .media-pane');
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
                            tabBtn = document.getElementById(`tab-btn-${idx}`);
                        }
       
                        if (tabBtn) tabBtn.click();
                    }

                    // 2. Scroll Editor to Span with Timeout
                    setTimeout(() => {
                        c.element.scrollIntoView({behavior: "smooth", block: "center"});
                        c.element.classList.remove('blink-highlight');
                        void c.element.offsetWidth; // Reflow
                        c.element.classList.add('blink-highlight');
                        // Highlight Card
                        document.querySelectorAll('.revision-card').forEach(x => { // Note: class might be different in edit.php? Line 808 uses generic div style
                             // edit.php doesn't use a specific class for card highlighting but inline styles. 
                             // We'll skip complex card highlighting as it wasn't requested, focused on navigation.
                             x.style.borderColor = x.dataset.resolved === 'true' ? '#bbf7d0' : '#e2e8f0';
                        });
                        card.style.borderColor = '#ef4444'; 
                    }, 100);
                };

                list.appendChild(card);
            });
            
            console.log('[SIDEBAR] Rendering complete, added', uniqueItems.length, 'unique cards');
            
        } catch (error) {
            console.error('[SIDEBAR] Error during rendering:', error);
        } finally {
            // CRITICAL: Reset flag in finally block to ensure it's always reset
            isRendering = false;
            console.log('[SIDEBAR] Render flag reset');
        }
    }

    function filterProduk() {
        const types = Array.from(document.querySelectorAll('input[name="jenis"]:checked')).map(c => c.value);
        const konv = document.getElementById('produk-konv');
        const syr = document.getElementById('produk-syariah');
        if(konv) konv.style.display = types.includes('Konvensional') ? 'block' : 'none';
        if(syr) syr.style.display = types.includes('Syariah') ? 'block' : 'none';
    }

    function toggleInput(id, checked) {
        const el = document.getElementById(id);
        if(el) el.style.display = checked ? 'block' : 'none';
    }

    function switchMode(mode) {
        document.getElementById('input_mode').value = (mode === 'upload') ? 'FILE_UPLOAD' : 'FREE_INPUT';
        document.querySelectorAll('.tab-item').forEach(el => el.classList.remove('active'));
        event.target.classList.add('active');
        
        document.getElementById('upload-panel').style.display = (mode === 'upload') ? 'block' : 'none';
        document.getElementById('manual-panel').style.display = (mode === 'manual') ? 'block' : 'none';
        
        if (mode === 'manual') {
            updateFreeInputTabs();
            // Clear history or reset stack potentially?
            // For now just ensure new editor is focused
        }
    }



    function updateSheetTabBadges() {
        // 1. Clear existing badges
        document.querySelectorAll('.tab-badge-dot').forEach(el => el.remove());
        
        // 2. Scan Sheets for Comments
        document.querySelectorAll('.sheet-pane, .media-pane').forEach(pane => {
            // ROBUST SELECTOR: Check for class AND various color formats (Hex, Name, RGB)
            const hasComments = pane.querySelector('.inline-comment, .revision-span, span[style*="#ef4444"], span[style*="color:red"], span[style*="color: red"], span[style*="rgb(255, 0, 0)"]');
            
            if (hasComments) {
                const sheetId = pane.id;
                let btn = document.getElementById('btn-' + sheetId);
                
                // Fallback for Free Input Tabs
                if (!btn && sheetId.startsWith('tab-')) {
                    const idx = sheetId.replace('tab-', '');
                    btn = document.getElementById(`tab-btn-${idx}`);
                }
                
                // Fallback for Legacy File Upload Tabs (tab-media-0 -> tab-media-btn-0)
                if (!btn && sheetId.startsWith('tab-media-')) {
                     const legacyId = sheetId.replace('tab-media-', 'tab-media-btn-');
                     btn = document.getElementById(legacyId);
                }

                // Generic Fallback based on onclick
                if (!btn) {
                     btn = document.querySelector(`button[onclick*="'${sheetId}'"], div[onclick*="'${sheetId}'"]`);
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

    function updateFreeInputTabs() {
        const medias = Array.from(document.querySelectorAll('input[name="media"]:checked')).map(c => c.value);
        document.querySelectorAll('.btn-sheet').forEach(el => el.style.display = 'none');

        if (medias.length === 0) {
            document.getElementById('active-media-label').innerText = "None Selected";
            document.getElementById('shared-editor').value = "";
            currentActiveMedia = null;
            return;
        }

        medias.forEach(media => {
            const btn = document.getElementById('tab-btn-' + media);
            if (btn) btn.style.display = 'inline-block';
        });

        if (!currentActiveMedia || !medias.includes(currentActiveMedia)) {
            activateSharedTab(medias[0]);
        }
    }

    function activateSharedTab(media) {
        syncToStorage();
        currentActiveMedia = media;
        document.getElementById('active-media-label').innerText = media;
        
        document.querySelectorAll('.btn-sheet').forEach(b => b.classList.remove('active'));
        const btn = document.getElementById('tab-btn-' + media);
        if (btn) btn.classList.add('active');

        const storageValue = document.getElementById('storage-' + media).value;
        const editor = document.getElementById('shared-editor');
        editor.innerHTML = storageValue; // Use innerHTML
        editor.focus();
        
        // CRITICAL: Trigger Sidebar & Badges Update
        if (typeof renderSideComments === 'function') renderSideComments();
        if (typeof updateTabBadges === 'function') updateTabBadges();
        
        // Update History for new tab content
        pushSnapshot(editor.innerHTML);
    }

    function syncToStorage() {
        if (currentActiveMedia) {
            const content = document.getElementById('shared-editor').innerHTML; // Use innerHTML
            document.getElementById('storage-' + currentActiveMedia).value = content;
        }
    }

    async function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            selectedFile = input.files[0];
            const status = document.getElementById('upload-status');
            const preview = document.getElementById('editor-content'); // TARGET NEW INNER DIV
            
            status.innerText = "Processing file...";
            const formData = new FormData();
            formData.append('file', selectedFile);
            
            try {
                const res = await fetch('index.php?controller=request&action=upload', { method:'POST', body:formData });
                const data = await res.json();
                if (data.success) {
                    status.innerHTML = "✔ " + selectedFile.name + " ready.";
                    preview.innerHTML = data.preview;
                    lastPreviewHtml = data.preview;
                    
                    // SELF-HEAL: Repair Broken Tables on new upload
                    const corruptSpans = document.querySelectorAll('tr > span.inline-comment, tbody > span.inline-comment, table > span.inline-comment');
                    if (corruptSpans.length > 0) {
                        corruptSpans.forEach(span => {
                            const parent = span.parentNode;
                            while (span.firstChild) parent.insertBefore(span.firstChild, span);
                            parent.removeChild(span);
                        });
                    }

                    // Render comments and badges for newly uploaded file
                    renderSideComments();
                    updateSheetTabBadges();
                } else {
                    status.innerHTML = "❌ " + data.message;
                    lastPreviewHtml = '';
                }
            } catch(e) {
                status.innerText = "❌ Error uploading file.";
            }
        }
    }

    // --- MAKER QUICK EDIT LOGIC ---
    let isMakerEditing = true; // ALWAYS ON
    let currentMakerColor = 'black';

    function applyMakerColor() {
        if (!isMakerEditing) return;
        
        // Use execCommand for simple text color toggling - FORCE BLACK
        document.execCommand('styleWithCSS', false, true);
        document.execCommand('foreColor', false, '#333333');
    }

    async function submitUpdate(isDraft = false) {
        // VALIDATION: Soft Warning for Unresolved Comments (Skip for Drafts)
        if (!isDraft) {
            const unresolvedCount = document.querySelectorAll('.inline-comment, .revision-span').length;
            
            if (unresolvedCount > 0) {
                const plural = unresolvedCount > 1 ? 'catatan revisi' : 'catatan revisi';
                const message = `Anda masih memiliki <strong>${unresolvedCount} ${plural}</strong> yang belum ditandai selesai.<br><br>Yakin ingin mengirim ulang sekarang?`;
                
                // Show custom confirmation modal
                showCustomConfirm('⚠️ Perhatian', message, () => {
                    // User clicked OK - proceed with submission
                    proceedWithSubmit(isDraft);
                }, () => {
                    // User clicked Cancel - do nothing
                });
                return; // Stop here, callback will handle submission
            }
        }

        // If no warning needed, proceed directly
        proceedWithSubmit(isDraft);
    }

    async function proceedWithSubmit(isDraft) {
        syncToStorage();
        
        const title = "<?php echo addslashes($request['title']); ?>"; 
        const requestId = document.getElementById('request_id').value;
        const ticketId = document.getElementById('script_number').value;
        const jenis = Array.from(document.querySelectorAll('input[name="jenis"]:checked')).map(c => c.value).join(',');
        const spv = document.getElementById('selected_spv').value;
        const note = document.getElementById('maker_note').value.trim();
        const inputMode = document.getElementById('input_mode').value;

        // ===== COMPREHENSIVE VALIDATION =====
        if (!isDraft) {
            // Validasi 1: Jenis
            if (!jenis) {
                showModal('Validasi Gagal', 'Mohon pilih minimal satu Jenis (Konvensional/Syariah)!', 'error');
                return;
            }

            // Validasi 2: Kategori
            const kategoriCheck = Array.from(document.querySelectorAll('input[name="kategori"]:checked'));
            if (kategoriCheck.length === 0) {
                showModal('Validasi Gagal', 'Mohon pilih minimal satu Kategori!', 'error');
                return;
            }

            // Validasi 3: Produk
            const produkCheck = Array.from(document.querySelectorAll('input[name="produk"]:checked'));
            if (produkCheck.length === 0) {
                showModal('Validasi Gagal', 'Mohon pilih minimal satu Produk!', 'error');
                return;
            }

            // Validasi 4: Media
            const mediaCheck = Array.from(document.querySelectorAll('input[name="media"]:checked'));
            if (mediaCheck.length === 0) {
                showModal('Validasi Gagal', 'Mohon pilih minimal satu Media!', 'error');
                return;
            }

            // Validasi 5: SPV
            if (!spv) {
                showModal('Validasi Gagal', 'Mohon pilih Supervisor untuk approval!', 'error');
                return;
            }

            // Validasi 6: Catatan Perbaikan
            if (!note) {
                showModal('Validasi Gagal', 'Mohon isi Catatan Perbaikan untuk menjelaskan apa yang telah diperbaiki!', 'error');
                return;
            }

            // Validasi 7: Content (File Upload or Free Input)
            if (inputMode === 'FILE_UPLOAD') {
                const editorContent = document.getElementById('editor-content');
                if (!editorContent || !editorContent.innerHTML.trim() || editorContent.innerHTML.trim() === '<p style="color:#999; font-style:italic;">No file preview available.</p>') {
                    showModal('Validasi Gagal', 'Konten script masih kosong. Mohon upload file atau isi konten!', 'error');
                    return;
                }
            } else {
                // Free Input Mode - check if at least one media has content
                let hasContent = false;
                mediaCheck.forEach(m => {
                    const storage = document.getElementById('storage-' + m.value);
                    if (storage && storage.value.trim()) hasContent = true;
                });
                if (!hasContent) {
                    showModal('Validasi Gagal', 'Mohon isi script content untuk minimal satu media yang dipilih!', 'error');
                    return;
                }
            }
        }

        // Collect Produk
        let selectedProduk = Array.from(document.querySelectorAll('input[name="produk"]:checked'))
            .filter(c => c.value !== 'Others')
            .map(c => c.value);
        const otherKonv = document.getElementById('prod_konv_other');
        if (otherKonv && otherKonv.style.display !== 'none' && otherKonv.value.trim()) selectedProduk.push(otherKonv.value.trim());

        // Collect Kategori & Media
        const kategori = Array.from(document.querySelectorAll('input[name="kategori"]:checked')).map(c => c.value).join(',');
        const selMedNodes = Array.from(document.querySelectorAll('input[name="media"]:checked'));
        const mediaNames = selMedNodes.map(c => c.value).join(',');

        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('script_number', ticketId);
        formData.append('jenis', jenis);
        formData.append('produk', selectedProduk.join(','));
        formData.append('kategori', kategori);
        formData.append('media', mediaNames);
        formData.append('title', document.getElementById('title')?.value || '');
        formData.append('input_mode', document.getElementById('input_mode').value);
        formData.append('selected_spv', spv);
        formData.append('maker_note', note);
        
        // DRAFT FLAG
        if (isDraft) formData.append('is_draft', '1');

        if (inputMode === 'FILE_UPLOAD') {
            if (selectedFile) formData.append('script_file', selectedFile);
            
            // CAPTURE CONTENT: PARSE SHEETS MANUALLY (Robust match for Reviewer-style tabs)
            const editorRoot = document.getElementById('editor-content');
            
            // --- SANITIZATION: CLEAN DISHES BEFORE RESUBMIT ---
            // Remove "Zombie Spans" (Black text with revision class) & Reset IDs
            try {
                const zombies = editorRoot.querySelectorAll('.revision-span');
                zombies.forEach(z => {
                   // Check strictly for RED (Robust)
                   const s = z.style.color ? z.style.color.toLowerCase().replace(/\s/g, '') : '';
                   const isRed = s === 'red' || s === 'rgb(255,0,0)' || s === '#ef4444' || s === '#ff0000' || s === 'rgb(239,68,68)';
                   
                   if (!isRed) {
                       // ZOMBIE DETECTED: UNWRAP (Remove span, keep text)
                       const parent = z.parentNode;
                       while (z.firstChild) parent.insertBefore(z.firstChild, z);
                       parent.removeChild(z);
                   } else {
                       // VALID RED TEXT: RESET ID
                       // Ensure fresh session for Reviewer
                       z.removeAttribute('data-comment-id');
                       z.removeAttribute('id');
                   }
                });
            } catch(e) {
                console.warn("Sanitization warning:", e);
            }
            // --- END SANITIZATION ---

            // Support both .sheet-pane (Legacy) and .media-pane (New)
            const sheets = editorRoot.querySelectorAll('.sheet-pane, .media-pane');
            
            if (sheets.length > 0) {
                // Multi-sheet structure found
                let shData = [];
                sheets.forEach(sh => {
                    const shId = sh.id;
                    let shName = "Sheet"; 
                    
                    // Strategy 1: Find button by onclick (Reviewer Style)
                    // Pattern: changeSheet('tab-media-0') or similar
                    let btn = editorRoot.querySelector(`div[onclick*="'${shId}'"], button[onclick*="'${shId}'"]`);
                    
                    // Strategy 2: ID Correlation (tab-media-0 -> btn-tab-media-0)
                    if (!btn && shId.startsWith('tab-media-')) {
                        btn = document.getElementById('btn-' + shId);
                    }

                    if (btn) {
                        shName = btn.innerText;
                    } else {
                        shName = sh.getAttribute('data-name') || shName;
                    }
                    
                    shData.push({
                        sheet_name: shName.trim(),
                        content: sh.innerHTML // Save inner content only
                    });
                });
                formData.append('script_content', JSON.stringify(shData));
            } else {
                // Fallback: No sheets found (Legacy Single View or non-Excel)
                // Just take innerHTML but try to avoid taking the Editor Wrapper if possible
                // For Word doc, it's usually just .word-preview class inside
                const currentHtml = editorRoot.innerHTML;
                formData.append('script_content', currentHtml);
            }
        } else {
            let contentData = [];
            selMedNodes.forEach(c => {
                const text = document.getElementById('storage-' + c.value).value;
                contentData.push({ sheet_name: c.value, content: text });
            });
            formData.append('script_content', JSON.stringify(contentData));
        }

        

        const confirmTitle = isDraft ? "Simpan Draft" : "Konfirmasi Pengiriman";
        const confirmMsg = isDraft 
            ? "Simpan perubahan sementara (Draft)?<br><span style='font-size:12px; color:#64748b;'>Status data tidak akan berubah.</span>" 
            : "Yakin ingin mengirim ulang revisi ini ke Supervisor?";

        showCustomConfirm(confirmTitle, confirmMsg, async () => {
            try {
                const res = await fetch('?controller=request&action=update', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success' || data.success) {
                    if (isDraft) {
                        showSuccess('Draft Tersimpan', 'Perubahan Anda telah disimpan sebagai draft.', true);
                    } else {
                        showSuccess('Berhasil Dikirim!', 'Revisi script berhasil dikirim ulang ke Supervisor untuk approval.', false);
                    }
                } else {
                    showModal('Gagal', data.message || data.error || 'Terjadi kesalahan saat menyimpan.', 'error');
                }
            } catch(e) {
                showModal('Error', 'Terjadi kesalahan sistem. Mohon coba lagi.', 'error');
            }
        });
    }

    function reloadPage(reload) {
         if (reload) {
            setTimeout(() => window.location.reload(), 2000);
        } else {
            setTimeout(() => window.location.href = 'index.php', 2000);
        }
    }

    // ===== SWEETALERT2 IMPLEMENTATION =====

    function showModal(title, message, type = 'success') {
        Swal.fire({
            title: title,
            text: message, 
            icon: type,
            confirmButtonText: 'OK',
            confirmButtonColor: 'var(--primary-red)'
        });
    }

    function showSuccess(title, message, reload = false) {
        Swal.fire({
            title: title,
            html: message,
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: 'var(--primary-red)'
        }).then((result) => {
             if (result.isConfirmed || result.isDismissed) {
                 hasUnsavedChanges = false; 
                 window.onbeforeunload = null; 
                 reloadPage(reload);
             }
        });
    }

    function closeModal() {
        // No-op for SweetAlert (handled internally)
    }

    function showCustomConfirm(title, message, onConfirm, onCancel) {
        Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                if (onConfirm) onConfirm();
            } else {
                if (onCancel) onCancel();
            }
        });
    }

    // --- UNSAVED CHANGES PROTECTION ---
    let hasUnsavedChanges = false;

    function markUnsaved() {
        hasUnsavedChanges = true;
    }

    // Attach to history actions
    function pushSnapshot(html) {
        const last = historyStack.undo[historyStack.undo.length - 1];
        if (last && last.action === 'content-change' && last.html === html) return;
        
        historyStack.undo.push({ action: 'content-change', html: html });
        historyStack.redo = []; 
        updateUndoRedoUI();
        markUnsaved(); // Mark dirty
    }

    function performUndo() {
        if (historyStack.undo.length <= 1) return; 

        const lastAction = historyStack.undo.pop();
        historyStack.redo.push(lastAction);
        
        const prevState = historyStack.undo[historyStack.undo.length - 1];
        
        if (lastAction.action === 'content-change') {
            // SET FLAG to prevent mutation observer from triggering
            isInternalChange = true;
            applyContentRestoration(prevState.html);
            // Small delay before re-enabling observer
            setTimeout(() => { isInternalChange = false; }, 50);
        }
        
        renderSideComments(); 
        updateSheetTabBadges();
        updateUndoRedoUI();
        markUnsaved(); 
    }

    function performRedo() {
        const nextAction = historyStack.redo.pop();
        if (!nextAction) return;

        historyStack.undo.push(nextAction);

        if (nextAction.action === 'content-change') {
            // SET FLAG to prevent mutation observer from triggering
            isInternalChange = true;
            applyContentRestoration(nextAction.html);
            // Small delay before re-enabling observer
            setTimeout(() => { isInternalChange = false; }, 50);
        }

        renderSideComments();
        updateSheetTabBadges();
        updateUndoRedoUI();
        markUnsaved(); 
    }

    // Attach to Resolve
    // Note: resolveComment already modifies DOM which triggers observer -> pushSnapshot -> markUnsaved
    // So explicit markUnsaved in resolveComment might be redundant but safe.

    // BROWSER WARNING
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            // Standard way to trigger browser confirmation dialog
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });

    // Clear flag on valid submit
    window.addEventListener('submit', () => { hasUnsavedChanges = false; });

    // ==========================================
    // COMMENT & HIGHLIGHT LOGIC (Ported from Review)
    // ==========================================
    
    let savedRange = null;

    function addComment() {
        // Toggle red/black buttons if needed or just use default
        
        const selection = window.getSelection();
        if (selection.rangeCount === 0 || selection.toString().length === 0) {
            showCustomAlert("⚠️ Tidak Ada Teks Dipilih", "Mohon blok/sorot teks terlebih dahulu baru klik tombol 'Comment'.");
            return;
        }

        savedRange = selection.getRangeAt(0);

        // Show Modal (Simple Prompt for now, or Custom Modal)
        // Using simple prompt for MVP consistency with Review, but Review uses a modal div "comment-modal"
        // Since edit.php doesn't have the modal HTML, let's inject it dynamically or use Prompt
        
        // BETTER: Inject Modal HTML if missing
        if (!document.getElementById('comment-modal')) {
            const modalHtml = `
            <div id="comment-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
                <div style="background:white; padding:20px; border-radius:8px; width:400px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
                    <h3 style="margin-top:0;">Add Comment</h3>
                    <textarea id="comment-input" style="width:100%; height:100px; padding:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px;" placeholder="Type your comment..."></textarea>
                    <div style="text-align:right; gap:10px; display:flex; justify-content:flex-end;">
                        <button onclick="closeCommentModal()" style="padding:8px 16px; background:#ccc; border:none; border-radius:4px; cursor:pointer;">Cancel</button>
                        <button onclick="submitComment()" style="padding:8px 16px; background:var(--primary-red); color:white; border:none; border-radius:4px; cursor:pointer;">Save</button>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        document.getElementById('comment-modal').style.display = 'flex';
        document.getElementById('comment-input').value = '';
        document.getElementById('comment-input').focus();
    }

    function closeCommentModal() {
        const modal = document.getElementById('comment-modal');
        if(modal) modal.style.display = 'none';
        savedRange = null;
    }

    function submitComment() {
        const inp = document.getElementById('comment-input');
        const text = inp.value.trim();
        if (!text) { alert("Komentar tidak boleh kosong."); return; }
        
        if (!savedRange) { 
            closeCommentModal(); return; 
        }

        // SAFETY CHECK: Prevent Multi-Cell Selection
        const clone = savedRange.cloneContents();
        if (clone.querySelector('td, th, tr, tbody, table')) {
            showCustomAlert("⚠️ Aksi Dibatasi", "Tidak dapat memberi komentar lintas kolom tabel.<br>Mohon blok teks di dalam satu kolom saja agar susunan tabel tetap rapi.");
            closeCommentModal();
            return;
        }

        // Generate ID
        const commentId = 'c' + Date.now();
        const CURRENT_USER_NAME = "Maker"; // Simplification
        
        const timeObj = new Date();
        const commentTime = timeObj.toLocaleString('id-ID', { day:'numeric', month:'short', hour: '2-digit', minute:'2-digit' }).replace('.', ':');

        try {
            // MANUAL DOM WRAPPING
            const wrapper = document.createElement('span');
            wrapper.className = 'inline-comment';
            wrapper.setAttribute('data-comment-id', commentId);
            wrapper.setAttribute('data-comment-text', text);
            wrapper.setAttribute('data-comment-user', CURRENT_USER_NAME);
            wrapper.setAttribute('data-comment-time', commentTime);
            wrapper.title = text;
            wrapper.style.backgroundColor = 'yellow'; // Fallback
            
            const fragment = savedRange.extractContents();
            wrapper.appendChild(fragment);
            savedRange.insertNode(wrapper);
            
            window.getSelection().removeAllRanges();
            
            // Render Sidebar
            renderSideComments();
            updateSheetTabBadges();
            
            hasUnsavedChanges = true;
            closeCommentModal();
        } catch (e) {
            console.error(e);
            alert("Gagal menyimpan komentar. Pastikan blok teks valid.");
        }
    }

    // Custom Alert Helper
    function showCustomAlert(title, message) {
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

    function showCustomConfirm(title, message, onConfirm, onCancel) {
        const existing = document.getElementById('custom-confirm-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.id = 'custom-confirm-overlay';
        overlay.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4); z-index: 9999;
            display: flex; align-items: center; justify-content: center;
        `;
        
        overlay.innerHTML = `
            <div style="background:white; padding:24px; border-radius:8px; width:400px; max-width:90%; box-shadow:0 4px 12px rgba(0,0,0,0.15); text-align:center;">
                <h3 style="margin:0 0 16px 0; color:#1e293b; font-size:16px; font-weight:600;">${title}</h3>
                <p style="margin:0 0 20px 0; color:#64748b; font-size:14px; line-height:1.5;">${message}</p>
                <div style="display:flex; gap:8px; justify-content:center;">
                    <button id="confirm-cancel-btn" style="background:white; color:#64748b; border:1px solid #cbd5e1; padding:8px 16px; border-radius:4px; font-size:13px; font-weight:500; cursor:pointer;">
                        Batal
                    </button>
                    <button id="confirm-ok-btn" style="background:#dc2626; color:white; border:none; padding:8px 16px; border-radius:4px; font-size:13px; font-weight:500; cursor:pointer;">
                        Ya, Kirim
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);

        document.getElementById('confirm-cancel-btn').onclick = () => {
            overlay.remove();
            if (onCancel) onCancel();
        };
        
        document.getElementById('confirm-ok-btn').onclick = () => {
            overlay.remove();
            if (onConfirm) onConfirm();
        };
    }

    // INIT ON LOAD
    document.addEventListener('DOMContentLoaded', () => {
        console.log("Initializing Editor...");

        // 1. Track Inputs for Unsaved Changes
        const tracked = ['shared-editor', 'maker_note'];
        tracked.forEach(id => {
            const trackedEl = document.getElementById(id);
            if (trackedEl) trackedEl.addEventListener('input', () => markUnsaved());
        });

        // 2. GLOBAL EVENT DELEGATION for Tab Buttons (critical fix for contenteditable blocking clicks)
        // Attach to document body to catch clicks before ANY other handler
        document.body.addEventListener('click', (e) => {
            // Check if click target is a sheet tab button
            const btn = e.target.closest('.btn-sheet');
            if (btn) {
                // Extract sheetId from onclick attribute
                const onclickAttr = btn.getAttribute('onclick');
                
                if (onclickAttr) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    console.log('Tab button clicked:', btn);

                    // CASE 1: File Upload / Legacy (changeSheet)
                    const matchChange = onclickAttr.match(/changeSheet\('([^']+)'\)/);
                    if (matchChange && matchChange[1]) {
                        console.log('Switching to sheet (Upload):', matchChange[1]);
                        window.changeSheet(matchChange[1]);
                        return false;
                    }
                    
                    // CASE 2: Free Input (activateSharedTab)
                    const matchActivate = onclickAttr.match(/activateSharedTab\('([^']+)'\)/);
                    if (matchActivate && matchActivate[1]) {
                        console.log('Switching to sheet (Free Input):', matchActivate[1]);
                        window.activateSharedTab(matchActivate[1]); // Ensure this function is globally accessible
                        return false;
                    }

                    // CASE 3: Legacy File Upload (openMediaTab) - Handle as Free Input or Custom
                    // The user snippet showed: openMediaTab(event, 'tab-media-0')
                    // This seems to be an old function name for the same logic?
                    // Let's assume it maps to changeSheet OR activateSharedTab depending on context.
                    // Given the user context "legacy file upload", let's try mapping to changeSheet logic first if it target IDs.
                    // Actually, if it's "Robo_CC" etc, it might be the Free Input style?
                    // Wait, the user said "gak bisa diklik yang file upload".
                    // Let's just execute the original function name if it exists globally?
                    // Or map it to changeSheet if the ID targets a sheet pane.
                    const matchOpen = onclickAttr.match(/openMediaTab\([^,]+,\s*'([^']+)'\)/);
                    if (matchOpen && matchOpen[1]) {
                         console.log('Switching to sheet (Legacy Reference):', matchOpen[1]);
                         // Legacy code usually toggles display:none. 
                         // To be safe, let's call changeSheet which handles display toggling.
                         // But we need to be sure the ID passed is the DIV id.
                         // In the snippet: onclick="openMediaTab(event, 'tab-media-0')"
                         // This implies the target DIV id is 'tab-media-0'.
                         window.changeSheet(matchOpen[1]);
                         return false;
                    }
                }
            }
        }, true); // Use capture phase - CRITICAL!

        // 3. Initial Render Logic (Delayed slightly to ensure content paint)
        setTimeout(() => {
            if (document.getElementById('manual-panel').style.display === 'block') {
                updateFreeInputTabs();
                updateTabBadges();
            }
            
            if (document.getElementById('upload-panel').style.display === 'block') {
                renderSideComments();
                updateSheetTabBadges();
                makeTabsNonEditable(); 
            }
        }, 100); 
    });
</script>
<?php require_once 'app/views/layouts/footer.php'; ?>
