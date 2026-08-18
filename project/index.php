<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
include 'includes/header.php';

// Fetch total complaints
$result = $conn->query("SELECT COUNT(*) AS total FROM complaints");
$row = $result->fetch_assoc();
$total_complaints = $row['total'];

// Fetch resolved complaints
$result = $conn->query("
    SELECT COUNT(*) AS resolved
    FROM complaints
    WHERE status_id = (
        SELECT id
        FROM complaint_status
        WHERE name = 'Resolved'
        LIMIT 1
    )
");

$row = $result->fetch_assoc();
$resolved_complaints = $row['resolved'];
?>

<div class="card" style="text-align: center; margin-top: 50px;">
    <h2>Welcome to the Civic Reporting System</h2>
    <p>A transparent and efficient way to report and track local community issues.</p>

    <div style="display: flex; justify-content: space-around; margin-top: 30px; flex-wrap: wrap;">

        <div class="card" style="flex: 1; margin: 10px; min-width: 220px;">
            <h3>Total Reports</h3>
            <p style="font-size: 2em; color: #e8491d;">
                <?php echo $total_complaints; ?>
            </p>
        </div>

        <div class="card" style="flex: 1; margin: 10px; min-width: 220px;">
            <h3>Issues Resolved</h3>
            <p style="font-size: 2em; color: #28a745;">
                <?php echo $resolved_complaints; ?>
            </p>
        </div>

    </div>

    <?php if (!isLoggedIn()): ?>
        <div style="margin-top: 30px;">
            <a href="auth/register.php" class="btn">
                Get Started - Register Now
            </a>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>