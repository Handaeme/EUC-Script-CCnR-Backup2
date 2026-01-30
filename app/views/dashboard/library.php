<?php
require_once 'app/views/layouts/header.php';
require_once 'app/views/layouts/sidebar.php';
?>

<div class="main">
    <div class="header-box" style="margin-bottom: 20px;">
        <h2 style="color:var(--primary-red); margin:0;">Script Library</h2>
        <p style="color:var(--text-secondary);">Browse all finalized and published scripts.</p>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <div style="display:flex; align-items:center; gap:15px;">
                <h4 style="margin:0;">Published Documents</h4>
                <a href="?controller=dashboard&action=exportLibrary&start_date=<?php echo htmlspecialchars($startDate ?? ''); ?>&end_date=<?php echo htmlspecialchars($endDate ?? ''); ?>" 
                   style="background:#10b981; color:white; text-decoration:none; padding:6px 12px; border-radius:6px; font-weight:bold; font-size:12px; display:flex; align-items:center; gap:5px;">
                   <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                   Export Excel
                </a>
            </div>
            
            <!-- View Mode Toggles -->
            <div style="background:#f1f5f9; padding:4px; border-radius:8px; display:flex; gap:4px;">
                <button onclick="setViewMode('list')" id="btn-list" style="border:none; background:white; padding:6px 10px; border-radius:6px; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.1); color:#374151;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                </button>
                <button onclick="setViewMode('grid')" id="btn-grid" style="border:none; background:transparent; padding:6px 10px; border-radius:6px; cursor:pointer; color:#64748b;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </button>
            </div>
        </div>
        
        <?php require_once 'app/views/layouts/filter_bar.php'; ?>
        
        <?php if (empty($libraryItems)): ?>
            <div style="text-align:center; padding:30px; color:#888;">
                <p>No finalized scripts in the library yet.</p>
            </div>
        <?php else: ?>
            
            <!-- LIST VIEW (TABLE) -->
            <div id="view-list" style="display:block;">
                <table id="dataTable" class="table" style="width:100%; border-collapse:collapse; font-size:14px;">
                    <thead>
                        <tr style="background:#f9fafb; text-align:left;">
                            <th style="padding:12px; border-bottom:2px solid #eee; width:100px;">Action</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; white-space:nowrap;">Ticket ID</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; min-width:150px;">Script Number</th>
                            <th style="padding:12px; border-bottom:2px solid #eee;">Media</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; width:40%;">Content Script</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; white-space:nowrap;">Created Date</th>
                            <th style="padding:12px; border-bottom:2px solid #eee; white-space:nowrap; cursor:pointer;" onclick="window.location.href='?controller=dashboard&action=library&sort_published=<?php echo ($sortPublished ?? 'DESC') === 'DESC' ? 'ASC' : 'DESC'; ?>&start_date=<?php echo htmlspecialchars($startDate ?? ''); ?>&end_date=<?php echo htmlspecialchars($endDate ?? ''); ?>'">
                                <div style="display:flex; align-items:center; gap:5px;">
                                    Published Date
                                    <?php if (($sortPublished ?? 'DESC') === 'DESC'): ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
                                    <?php else: ?>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                                    <?php endif; ?>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($libraryItems as $item): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:12px;">
                                <a href="?controller=request&action=viewLibrary&id=<?php echo $item['request_id']; ?>" 
                                   style="background:#3b82f6; color:white; text-decoration:none; padding:6px 12px; border-radius:4px; font-size:12px;">
                                    View Detail
                                </a>
                            </td>
                            <td style="padding:12px; white-space:nowrap;">
                                <div style="font-weight:600; color:var(--primary-red);"><?php 
                                    $tId = $item['ticket_id'] ?? 'Pending';
                                    echo is_numeric($tId) ? sprintf("SC-%04d", $tId) : $tId; 
                                ?></div>
                            </td>
                            <td style="padding:12px; white-space:nowrap;">
                                <?php echo htmlspecialchars($item['script_number']); ?>
                            </td>
                            <td style="padding:12px; white-space:nowrap;"><?php echo htmlspecialchars($item['media']); ?></td>
                            <td style="padding:12px;">
                                <?php 
                                    if (($item['mode'] ?? '') === 'FILE_UPLOAD') {
                                        // Show Filename
                                        echo '<div style="display:flex; align-items:center; gap:5px; color:#374151; word-break:break-all;">';
                                        echo htmlspecialchars($item['filename'] ?? 'Attached File');
                                        echo '</div>';
                                    } else {
                                        // Show Snippet (Free Input)
                                        $snippet = strip_tags($item['content_preview'] ?? '');
                                        if (empty($snippet)) {
                                            $snippet = $item['title'] ?? '';
                                        }
                                        echo !empty($snippet) 
                                            ? htmlspecialchars(substr($snippet, 0, 80)) . (strlen($snippet) > 80 ? '...' : '')
                                            : '<span style="color:#999; font-style:italic;">No content</span>';
                                    }
                                ?>
                            </td>
                            <td style="padding:12px; white-space:nowrap; color: #888;">
                                 <?php 
                                     if (isset($item['request_created_at']) && $item['request_created_at'] instanceof DateTime) {
                                         echo $item['request_created_at']->format('d M Y');
                                     } elseif (isset($item['request_created_at'])) {
                                          echo date('d M Y', strtotime($item['request_created_at']));
                                     } else {
                                         echo '-';
                                     }
                                 ?>
                            </td>
                            <td style="padding:12px; white-space:nowrap;">
                                 <span style="font-weight:bold;"><?php echo date('d M Y', strtotime($item['created_at']->format('Y-m-d H:i:s'))); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- GRID VIEW (CARDS) -->
            <div id="view-grid" style="display:none; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
                <?php foreach ($libraryItems as $item): ?>
                <div style="background:white; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; transition:all 0.2s; box-shadow:0 1px 3px rgba(0,0,0,0.05); display:flex; flex-direction:column;">
                    <!-- Card Header -->
                    <div style="padding:15px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:start; background:#f8fafc;">
                        <div>
                            <div style="font-weight:700; color:var(--primary-red); font-size:15px;">
                                <?php 
                                    $tId = $item['ticket_id'] ?? 'Pending';
                                    echo is_numeric($tId) ? sprintf("SC-%04d", $tId) : $tId; 
                                ?>
                            </div>
                            <div style="font-size:12px; color:#64748b; margin-top:2px;">
                                <?php echo htmlspecialchars($item['script_number']); ?>
                            </div>
                            <div style="font-size:12px; color:#9ca3af; margin-top:2px;">
                                Created: <?php 
                                     if (isset($item['request_created_at']) && $item['request_created_at'] instanceof DateTime) {
                                         echo $item['request_created_at']->format('d M Y');
                                     } elseif (isset($item['request_created_at'])) {
                                          echo date('d M Y', strtotime($item['request_created_at']));
                                     } else {
                                         echo '-';
                                     }
                                ?>
                            </div>
                        </div>
                        <span style="font-size:13px; font-weight:600; color:#d32f2f;">
                            <?php echo date('d M Y', strtotime($item['created_at']->format('Y-m-d H:i:s'))); ?>
                        </span>
                    </div>

                    <!-- Card Body (Content Highlight) -->
                    <div style="padding:20px; flex-grow:1;">
                        <?php 
                            if (($item['mode'] ?? '') === 'FILE_UPLOAD') {
                                // Show Filename Big with SVG Icon
                                echo '<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:20px; display:flex; flex-direction:column; align-items:center; gap:10px; color:#166534; height:120px; justify-content:center;">';
                                echo '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';
                                echo '<span style="font-weight:600; text-align:center; word-break:break-all; font-size:13px;">' . htmlspecialchars($item['filename'] ?? 'Attached File') . '</span>';
                                echo '</div>';
                            } else {
                                // Show Content Snippet
                                $snippet = strip_tags($item['content_preview'] ?? '');
                                if (empty($snippet)) $snippet = $item['title'] ?? 'No text content available';
                                
                                echo '<div style="font-family:monospace; background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0; color:#334155; height:120px; overflow:hidden; position:relative; font-size:13px; line-height:1.6;">';
                                echo htmlspecialchars(substr($snippet, 0, 180));
                                if (strlen($snippet) > 180) {
                                    echo '... <div style="position:absolute; bottom:0; left:0; right:0; height:40px; background:linear-gradient(transparent, #f8fafc);"></div>';
                                }
                                echo '</div>';
                            }
                        ?>
                    </div>

                    <!-- Card Footer -->
                    <div style="padding:15px; border-top:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                        <span style="background:#eff6ff; color:#3b82f6; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; border:1px solid #dbeafe;">
                            <?php echo htmlspecialchars($item['media']); ?>
                        </span>
                        <a href="?controller=request&action=viewLibrary&id=<?php echo $item['request_id']; ?>" 
                           style="background:#3b82f6; color:white; text-decoration:none; padding:8px 16px; border-radius:6px; font-size:13px; font-weight:500; transition:background 0.2s;">
                            View Detail
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<script>
// View Mode Switcher Logic
function setViewMode(mode) {
    const listBtn = document.getElementById('btn-list');
    const gridBtn = document.getElementById('btn-grid');
    const listView = document.getElementById('view-list');
    const gridView = document.getElementById('view-grid');
    
    if (mode === 'grid') {
        listView.style.display = 'none';
        gridView.style.display = 'grid';
        
        gridBtn.style.background = 'white';
        gridBtn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        gridBtn.style.color = '#374151';
        
        listBtn.style.background = 'transparent';
        listBtn.style.boxShadow = 'none';
        listBtn.style.color = '#64748b';
    } else {
        listView.style.display = 'block';
        gridView.style.display = 'none';
        
        listBtn.style.background = 'white';
        listBtn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.1)';
        listBtn.style.color = '#374151';
        
        gridBtn.style.background = 'transparent';
        gridBtn.style.boxShadow = 'none';
        gridBtn.style.color = '#64748b';
    }
    
    // Save preference
    localStorage.setItem('libraryViewMode', mode);
}

// Load preference on start
document.addEventListener('DOMContentLoaded', () => {
    const savedMode = localStorage.getItem('libraryViewMode') || 'list';
    setViewMode(savedMode);
});
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>
