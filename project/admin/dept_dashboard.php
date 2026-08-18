<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Department Head']);

$dept_head_id = $_SESSION['user_id'];

// Fetch all complaints (Department Head can see all)
$stmt = $pdo->query("
    SELECT c.id, c.title, c.reference_number, c.created_at, 
           cs.name as status, cat.name as category, u.username as citizen
    FROM complaints c
    JOIN complaint_status cs ON c.status_id = cs.id
    JOIN categories cat ON c.category_id = cat.id
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
");
$complaints = $stmt->fetchAll();

// Fetch statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM complaints");
$total_complaints = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as in_progress FROM complaints WHERE status_id = (SELECT id FROM complaint_status WHERE name = 'In Progress')");
$in_progress = $stmt->fetch()['in_progress'];

$stmt = $pdo->query("SELECT COUNT(*) as resolved FROM complaints WHERE status_id = (SELECT id FROM complaint_status WHERE name = 'Resolved')");
$resolved = $stmt->fetch()['resolved'];

// Fetch category breakdown
$stmt = $pdo->query("
    SELECT cat.name, COUNT(*) as count
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    GROUP BY cat.id
");
$category_stats = $stmt->fetchAll();

include '../includes/header.php';
?>

<div style="margin-top: 30px;">
    <h2>Department Head Dashboard</h2>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
        <div class="card">
            <h3>Total Complaints</h3>
            <p style="font-size: 2em; color: #e8491d;"><?php echo $total_complaints; ?></p>
        </div>
        <div class="card">
            <h3>In Progress</h3>
            <p style="font-size: 2em; color: #ffc107;"><?php echo $in_progress; ?></p>
        </div>
        <div class="card">
            <h3>Resolved</h3>
            <p style="font-size: 2em; color: #28a745;"><?php echo $resolved; ?></p>
        </div>
    </div>
    
    <div class="card">
        <h3>Complaints by Category</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($category_stats as $stat): ?>
                    <tr>
                        <td><?php echo $stat['name']; ?></td>
                        <td><?php echo $stat['count']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <h3>All Complaints</h3>
    
    <?php if (count($complaints) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Reference #</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Citizen</th>
                    <th>Submitted</th>
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
                        <td><?php echo $complaint['citizen']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($complaint['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="card">
            <p>No complaints found.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
