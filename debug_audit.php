<?php
require_once 'config/database.php';

$config = require __DIR__ . '/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>Debug: script_audit_trail Table</h2>";

$sql = "SELECT * FROM script_audit_trail ORDER BY created_at DESC";
$stmt = sqlsrv_query($conn, $sql);

if (!$stmt) {
    die("Query failed: " . print_r(sqlsrv_errors(), true));
}

echo "<table border='1' cellpadding='5' style='border-collapse:collapse; font-size:12px;'>";
echo "<tr style='background:#eee;'>
        <th>ID</th>
        <th>Request ID</th>
        <th>Script Number</th>
        <th>Action</th>
        <th>User Role</th>
        <th>User ID</th>
        <th>Details</th>
        <th>Created At</th>
      </tr>";

$count = 0;
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $count++;
    echo "<tr>";
    echo "<td>" . $row['audit_id'] . "</td>";
    echo "<td>" . $row['request_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['script_number']) . "</td>";
    echo "<td><strong>" . htmlspecialchars($row['action']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['user_role']) . "</td>";
    echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['details']) . "</td>";
    echo "<td>" . ($row['created_at'] ? $row['created_at']->format('Y-m-d H:i:s') : 'N/A') . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p><strong>Total Records: $count</strong></p>";

if ($count === 0) {
    echo "<p style='color:red;'>⚠️ Tabel masih kosong. Belum ada aktivitas yang tercatat.</p>";
    echo "<p>Silakan lakukan aktivitas berikut untuk test:</p>";
    echo "<ol>";
    echo "<li>Login sebagai <code>maker01</code> (pass: 123)</li>";
    echo "<li>Buat Request baru di menu 'Create New Request'</li>";
    echo "<li>Submit Request</li>";
    echo "<li>Refresh halaman ini untuk lihat log baru</li>";
    echo "</ol>";
}

sqlsrv_close($conn);
?>
