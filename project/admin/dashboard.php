<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Admin']);

// Total Users
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
$row = $result->fetch_assoc();
$total_users = $row['total'];

// Total Complaints
$result = $conn->query("SELECT COUNT(*) AS total FROM complaints");
$row = $result->fetch_assoc();
$total_complaints = $row['total'];

// Resolved Complaints
$result = $conn->query("
    SELECT COUNT(*) AS resolved
    FROM complaints
    WHERE status_id = (
        SELECT id
        FROM complaint_status
        WHERE name='Resolved'
        LIMIT 1
    )
");
$row = $result->fetch_assoc();
$resolved = $row['resolved'];

// Audit Logs
$result = $conn->query("SELECT COUNT(*) AS total FROM audit_logs");
$row = $result->fetch_assoc();
$total_logs = $row['total'];

include '../includes/header.php';
?>

<div style="margin-top: 30px;">
    <h2>Admin Dashboard</h2>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:30px;">

        <div class="card">
            <h3>Total Users</h3>
            <p style="font-size:2em;color:#007bff;">
                <?php echo $total_users; ?>
            </p>
        </div>

        <div class="card">
            <h3>Total Complaints</h3>
            <p style="font-size:2em;color:#e8491d;">
                <?php echo $total_complaints; ?>
            </p>
        </div>

        <div class="card">
            <h3>Resolved</h3>
            <p style="font-size:2em;color:#28a745;">
                <?php echo $resolved; ?>
            </p>
        </div>

        <div class="card">
            <h3>Audit Logs</h3>
            <p style="font-size:2em;color:#6c757d;">
                <?php echo $total_logs; ?>
            </p>
        </div>

    </div>

    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;">

        <a href="manage_users.php" class="btn" style="text-align:center;padding:20px;">Manage Users</a>

        <a href="manage_categories.php" class="btn" style="text-align:center;padding:20px;">Manage Categories</a>

        <a href="manage_assignments.php" class="btn" style="text-align:center;padding:20px;">Manage Assignments</a>

        <a href="view_complaints.php" class="btn" style="text-align:center;padding:20px;">View All Complaints</a>

        <a href="view_audit_logs.php" class="btn" style="text-align:center;padding:20px;">Audit Logs</a>

        <a href="dept_dashboard.php" class="btn" style="text-align:center;padding:20px;">Department View</a>

    </div>

</div>

<?php include '../includes/footer.php'; ?>