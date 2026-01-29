<?php 
/**
 * Version Timeline Component for Audit Trail
 * Displays "Single View" with Version Navigation
 */

// Check if we have versions array (new format)
if (isset($content['versions']) && !empty($content['versions'])):
    $totalVersions = count($content['versions']);
    $activeIndex = $totalVersions - 1; // Default to Latest
?>
    <!-- Version Navigation Bar -->
    <div style="margin-bottom:15px; background:#f8fafc; padding:10px; border-radius:8px; border:1px solid #e2e8f0; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <span style="font-size:12px; font-weight:600; color:#64748b;">Select Version:</span>
        <div style="display:flex; gap:5px; flex-wrap:wrap;">
            <?php foreach ($content['versions'] as $idx => $version): 
                $vNum = $version['version_number'] ?? ($idx + 1);
                $isLatest = ($idx === $totalVersions - 1);
                $isActive = ($idx === $activeIndex);
                
                // Color Logic
                $btnClass = $isActive ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50';
                $border = $isActive ? 'border:1px solid #2563eb' : 'border:1px solid #cbd5e1';
                $bg = $isActive ? 'background:#2563eb; color:white' : 'background:white; color:#475569';
                if ($isLatest && !$isActive) {
                    $border = 'border:1px solid #2563eb';
                    $bg = 'background:#eff6ff; color:#1e40af';
                }
            ?>
            <button 
                onclick="switchVersion(<?= $idx ?>)"
                id="ver-btn-<?= $idx ?>"
                style="padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.2s; <?= $border ?>; <?= $bg ?>;">
                v<?= $vNum ?>
                <?php if($isLatest) echo '<span style="font-size:10px; opacity:0.8; margin-left:3px;">(Latest)</span>'; ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Version Content Panes -->
    <div class="version-viewer" style="position:relative; min-height:400px;">
        <?php foreach ($content['versions'] as $idx => $version): 
            $isLatest = ($idx === $totalVersions - 1);
            $display = $isLatest ? 'block' : 'none';
            
            $workflowStage = $version['workflow_stage'] ?? 'UNKNOWN';
            $createdBy = $version['created_by'] ?? 'System';
            $formattedDate = $version['formatted_date'] ?? date('Y-m-d H:i:s');
        ?>
        
        <div id="ver-pane-<?= $idx ?>" class="version-pane" style="display:<?= $display ?>;">
            
            <!-- Metadata Header for this Version -->
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px 8px 0 0; padding:12px 15px; display:flex; justify-content:space-between; align-items:center; border-bottom:none;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <span style="font-size:14px; font-weight:700; color:#1e293b;">Version <?php echo $version['version_number'] ?? ($idx + 1); ?></span>
                    <span style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; text-transform:uppercase;">
                        <?php echo htmlspecialchars($workflowStage); ?>
                    </span>
                </div>
                <div style="text-align:right; font-size:11px; color:#64748b;">
                    <div><i class="bi-person-fill"></i> <?php echo htmlspecialchars($createdBy); ?></div>
                    <div><i class="bi-clock"></i> <?php echo htmlspecialchars($formattedDate); ?></div>
                </div>
            </div>

            <!-- Content Area -->
            <div style="background:white; border:1px solid #e2e8f0; border-radius:0 0 8px 8px; overflow:hidden;">
               <div class="version-content-wrapper" style="padding:20px; overflow-x:auto;">
                   <?php echo $version['content']; ?>
               </div>
            </div>

        </div>
        <?php endforeach; ?>
    </div>

    <script>
    function switchVersion(idx) {
        // Hide all panes
        document.querySelectorAll('.version-pane').forEach(el => el.style.display = 'none');
        // Show selected
        document.getElementById('ver-pane-' + idx).style.display = 'block';

        // Update buttons
        document.querySelectorAll('button[id^="ver-btn-"]').forEach(btn => {
            btn.style.background = 'white';
            btn.style.color = '#475569';
            btn.style.borderColor = '#cbd5e1';
            // Keeping Latest border blueish
            if (btn.innerText.includes('(Latest)')) {
                btn.style.background = '#eff6ff';
                btn.style.color = '#1e40af';
                btn.style.borderColor = '#2563eb';
            }
        });

        // Highlight Active Button
        const activeBtn = document.getElementById('ver-btn-' + idx);
        if(activeBtn) {
            activeBtn.style.background = '#2563eb';
            activeBtn.style.color = 'white';
            activeBtn.style.borderColor = '#2563eb';
        }
        
        // Re-render comments if sidecar exists
        if (typeof renderSideComments === 'function') setTimeout(renderSideComments, 50);
    }
    </script>

<?php 
// Fallback to single preview if versions array doesn't exist (backward compatibility)
elseif (isset($content['html_preview']) && !empty($content['html_preview'])): 
?>
     <div style="background:#fff; border:1px solid #eee; border-radius:8px; width:100%; max-width:100%; overflow:hidden;">
        <div style="background:#f9fafb; padding:10px 15px; border-bottom:1px solid #eee; font-weight:600; color:#374151; font-size:13px; display:flex; justify-content:space-between; border-radius: 8px 8px 0 0;">
            <span>Stored Preview</span>
            <span style="font-size:11px; color:#6b7280; font-weight:normal;">Single version mode</span>
        </div>
        
        <div class="split-container" style="width:100%; max-width:100%; overflow:hidden;">
            <div id="audit-editor-container" style="width:100%; max-width:100%; padding:20px; min-height:400px; overflow-x:auto; font-family:'Inter', sans-serif; border-radius: 0 0 8px 8px; box-sizing:border-box;">
                <?php echo $content['html_preview']; ?>
            </div>
        </div>
    </div>

<?php 
// Final fallback to raw file parsing
elseif (isset($content['path']) && file_exists($content['path'])):
    if (!class_exists('App\\Helpers\\FileHandler')) {
        require_once 'app/helpers/FileHandler.php';
    }
    
    $ext = pathinfo($content['filename'], PATHINFO_EXTENSION);
    echo '<div style="background:#fff; border:1px solid #eee; border-radius:8px; width:100%; max-width:100%; overflow:hidden;">';
    echo '<div style="background:#f9fafb; padding:10px 15px; border-bottom:1px solid #eee; font-weight:600; color:#374151; font-size:13px; border-radius: 8px 8px 0 0;">File Preview (Raw)</div>';
    echo '<div style="padding:0; overflow-x:auto; width:100%; max-width:100%; display:block; font-family:\'Inter\', system-ui, -apple-system, sans-serif; border-radius: 0 0 8px 8px; box-sizing:border-box;">'; 
    $parsed = App\Helpers\FileHandler::parseFile($content['path'], $ext);
    echo is_array($parsed) ? $parsed['preview_html'] : $parsed;
    echo '</div></div>';
else:
    echo '<div style="color:#9ca3af; font-style:italic; text-align:center; padding:40px;">(No Preview Available)</div>';
endif;
?>
