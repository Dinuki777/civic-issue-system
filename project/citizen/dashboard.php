<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Citizen']);

$user_id = $_SESSION['user_id'];

// Fetch user's complaints
$stmt = $conn->prepare("
    SELECT c.id, c.title, c.description, c.reference_number, c.created_at,
           cs.name AS status,
           cat.name AS category
    FROM complaints c
    JOIN complaint_status cs ON c.status_id = cs.id
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$complaints = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Fetch unread notifications
$stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE user_id = ? AND is_read = 0");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$unread_count = $row['unread'];

$stmt->close();

include '../includes/header.php';
?>

<div style="margin-top: 30px;">
    <h2>Citizen Dashboard</h2>
    
    <div style="background: #d1ecf1; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <p><strong>Welcome, <?php echo $_SESSION['username']; ?>!</strong></p>
        <p>You have <strong><?php echo $unread_count; ?></strong> unread notifications.</p>
        <a href="notifications.php" class="btn">View Notifications</a>
    </div>
    
    <div style="margin-bottom: 30px;">
        <a href="submit_complaint.php" class="btn">Report New Issue</a>
    </div>
    
    <h3>Your Complaints</h3>
    
    <?php if (count($complaints) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Reference #</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $complaint): ?>
                    <tr>
                        <td><?php echo $complaint['reference_number']; ?></td>
                        <td><?php echo $complaint['title']; ?></td>
                        <td><?php echo $complaint['category']; ?></td>
                        <td class="status-<?php echo strtolower(str_replace(' ', '-', $complaint['status'])); ?>">
                            <?php echo $complaint['status']; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                        <td><a href="view_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 12px;">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="card">
            <p>You haven't submitted any complaints yet. <a href="submit_complaint.php">Submit your first complaint</a></p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
