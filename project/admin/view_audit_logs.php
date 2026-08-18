<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Admin']);


// Fetch audit logs

$logs = [];

$result = $conn->query("
    SELECT 
        al.id,
        al.action,
        al.entity_type,
        al.entity_id,
        al.details,
        al.ip_address,
        al.created_at,
        u.username

    FROM audit_logs al

    LEFT JOIN users u
    ON al.user_id = u.id

    ORDER BY al.created_at DESC

    LIMIT 100
");


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $logs[] = $row;

    }

}


include '../includes/header.php';
?>

<div style="margin-top:30px;">

<h2>Audit Logs</h2>


<a href="dashboard.php" class="btn" style="margin-bottom:20px;">
Back to Dashboard
</a>



<table>

<thead>

<tr>

<th>Action</th>
<th>Entity Type</th>
<th>User</th>
<th>IP Address</th>
<th>Details</th>
<th>Timestamp</th>

</tr>

</thead>


<tbody>


<?php foreach($logs as $log): ?>


<tr>


<td>
<?php echo htmlspecialchars($log['action']); ?>
</td>


<td>
<?php echo htmlspecialchars($log['entity_type']); ?>
</td>


<td>
<?php echo $log['username'] ? htmlspecialchars($log['username']) : 'System'; ?>
</td>


<td>
<?php echo htmlspecialchars($log['ip_address']); ?>
</td>


<td>
<?php echo htmlspecialchars($log['details']); ?>
</td>


<td>

<?php

echo date(
    'M d, Y H:i',
    strtotime($log['created_at'])
);

?>

</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


<?php include '../includes/footer.php'; ?>