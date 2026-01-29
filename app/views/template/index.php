<?php require_once 'app/views/layouts/header.php'; ?>
<?php require_once 'app/views/layouts/sidebar.php'; ?>

<div class="main">
    <div class="header-box" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="color:var(--primary-red); margin:0;">Template Library</h2>
            <p style="color:var(--text-secondary);">Download template standar untuk script.</p>
        </div>
        <?php if ($isProcedure): ?>
        <div>
            <button onclick="document.getElementById('uploadModal').style.display='block'" 
                    style="background:var(--primary-red); color:white; border:none; padding:10px 20px; border-radius:4px; font-weight:bold; cursor:pointer;">
                + Upload Template
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div style="background:#dcfce7; color:#166534; padding:10px; border-radius:4px; margin-bottom:20px;">
            Success: <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:10px; border-radius:4px; margin-bottom:20px;">
            Error: <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Template List -->
    <div class="card">
        <?php require_once 'app/views/layouts/filter_bar.php'; ?>
        <table id="dataTable" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f9fafb; text-align:left;">
                    <th style="padding:15px; border-bottom:2px solid #eee;">Title</th>
                    <th style="padding:15px; border-bottom:2px solid #eee;">Filename</th>
                    <th style="padding:15px; border-bottom:2px solid #eee;">Uploaded By</th>
                    <th style="padding:15px; border-bottom:2px solid #eee;">Date</th>
                    <th style="padding:15px; border-bottom:2px solid #eee; text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)): ?>
                    <tr><td colspan="5" style="padding:20px; text-align:center; color:#888;">No templates found.</td></tr>
                <?php else: ?>
                    <?php foreach ($templates as $t): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:15px; font-weight:bold; color:#374151;">
                            <?php echo htmlspecialchars($t['title']); ?>
                        </td>
                        <td style="padding:15px; color:#666;">
                            <?php echo htmlspecialchars($t['filename']); ?>
                        </td>
                        <td style="padding:15px; color:#666;">
                            <?php echo htmlspecialchars($t['uploaded_by']); ?>
                        </td>
                        <td style="padding:15px; color:#666;">
                            <?php echo date('d M Y', strtotime($t['created_at']->format('Y-m-d'))); ?>
                        </td>
                        <td style="padding:15px; text-align:right;">
                            <a href="<?php echo htmlspecialchars($t['filepath']); ?>" download 
                               style="text-decoration:none; color:#3b82f6; font-weight:bold; margin-right:15px;">
                                Download
                            </a>
                            <?php if ($isProcedure): ?>
                            <a href="?controller=template&action=delete&id=<?php echo $t['id']; ?>" 
                               onclick="return confirm('Are you sure you want to delete this template?');"
                               style="text-decoration:none; color:#dc2626; font-weight:bold;">
                                Delete
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Upload Modal (Procedure Only) -->
<?php if ($isProcedure): ?>
<div id="uploadModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div style="background:white; width:500px; margin:100px auto; padding:20px; border-radius:8px;">
        <h3>Upload New Template</h3>
        <form action="?controller=template&action=upload" method="POST" enctype="multipart/form-data">
            <div style="margin-bottom:15px;">
                <label>Template Title</label>
                <input type="text" name="title" required style="width:100%; padding:8px; margin-top:5px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>File (Excel/Word)</label>
                <input type="file" name="template_file" required style="width:100%; padding:8px; margin-top:5px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="document.getElementById('uploadModal').style.display='none'" 
                        style="padding:8px 16px; border:none; background:#eee; cursor:pointer; margin-right:10px; border-radius:4px;">Cancel</button>
                <button type="submit" style="padding:8px 16px; border:none; background:var(--primary-red); color:white; cursor:pointer; border-radius:4px;">Upload</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once 'app/views/layouts/footer.php'; ?>
