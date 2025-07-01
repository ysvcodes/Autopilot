<?php
require_once __DIR__ . '/database_connection/connection.php';
$agency_counts = [];
try {
    $stmt = $pdo->query('SELECT aa.agency_name, COUNT(au.user_id) as user_count FROM agency_admins aa LEFT JOIN agency_users au ON aa.agency_id = au.agency_id WHERE aa.role = "adminagency" GROUP BY aa.agency_id, aa.agency_name');
    while ($row = $stmt->fetch()) {
        $agency_counts[] = $row;
    }
} catch (Exception $e) {
    $agency_counts = [];
}
?>
<table id="agencies-user-count-table" style="width:100%;border-collapse:collapse;">
  <thead>
    <tr style="color:#888;font-size:0.98em;text-align:left;">
      <th style="padding:6px 0;">Agency Name</th>
      <th style="padding:6px 0;">User Count</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($agency_counts as $agency): ?>
      <tr style="border-top:1px solid #e3e8f0;">
        <td style="padding:7px 0;font-weight:700;">
          <?= !empty($agency['agency_name']) ? htmlspecialchars($agency['agency_name']) : '' ?>
        </td>
        <td style="padding:7px 0;">
          <?= isset($agency['user_count']) ? htmlspecialchars($agency['user_count']) : '0' ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($agency_counts)): ?>
      <tr><td style="padding:7px 0;color:#888;">No agencies found.</td><td style="padding:7px 0;color:#888;">N/A</td></tr>
    <?php endif; ?>
  </tbody>
</table> 