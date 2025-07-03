<?php
require_once __DIR__ . '/database_connection/connection.php';
$agency_counts = [];
try {
    $stmt = $pdo->query('SELECT aa.agency_id, aa.agency_name, COUNT(au.user_id) as user_count FROM agency_admins aa LEFT JOIN agency_users au ON aa.agency_id = au.agency_id WHERE aa.role = "adminagency" GROUP BY aa.agency_id, aa.agency_name');
    while ($row = $stmt->fetch()) {
        $agency_counts[] = $row;
    }
} catch (Exception $e) {
    $agency_counts = [];
}
// Fetch automations for each agency
$agency_automations = [];
try {
    $stmt = $pdo->query('SELECT agency_id, name FROM automations');
    while ($row = $stmt->fetch()) {
        $agency_automations[$row['agency_id']][] = $row['name'];
    }
} catch (Exception $e) {
    $agency_automations = [];
}
?>
<table id="agencies-user-count-table" style="width:100%;border-collapse:collapse;">
  <thead>
    <tr style="color:#888;font-size:0.98em;text-align:left;">
      <th style="padding:6px 0;">Agency Name</th>
      <th style="padding:6px 0;text-align:center;">User Count</th>
      <th style="padding:6px 0;">Automations</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($agency_counts as $agency): ?>
      <tr style="border-top:1px solid #e3e8f0;">
        <td style="padding:7px 0;font-weight:700;">
          <?= !empty($agency['agency_name']) ? htmlspecialchars($agency['agency_name']) : '' ?>
        </td>
        <td style="padding:7px 0;text-align:center;">
          <?= isset($agency['user_count']) ? htmlspecialchars($agency['user_count']) : '0' ?>
        </td>
        <td style="padding:7px 0;">
          <?php
            $auto = $agency_automations[$agency['agency_id']] ?? [];
            echo $auto ? htmlspecialchars(implode(', ', $auto)) : '-';
          ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($agency_counts)): ?>
      <tr><td style="padding:7px 0;color:#888;">No agencies found.</td><td style="padding:7px 0;color:#888;">N/A</td><td style="padding:7px 0;color:#888;">N/A</td></tr>
    <?php endif; ?>
  </tbody>
</table> 