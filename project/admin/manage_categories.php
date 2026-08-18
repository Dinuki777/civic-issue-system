<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Admin']);

$error = '';
$success = '';

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    // Create Category
    if ($_POST['action'] == 'create_category') {

        $name = sanitize($_POST['name']);

        if (empty($name)) {

            $error = 'Category name is required.';

        } else {

            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);

            if ($stmt->execute()) {

                $success = 'Category created successfully!';

                logAudit(
                    $conn,
                    $_SESSION['user_id'],
                    'Category Created',
                    'category',
                    $stmt->insert_id,
                    "Name: $name"
                );

            } else {

                $error = 'Failed to create category.';
            }

            $stmt->close();
        }
    }

    // Delete Category
    if ($_POST['action'] == 'delete_category') {

        $category_id = (int)$_POST['category_id'];

        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);

        if ($stmt->execute()) {

            $success = 'Category deleted successfully!';

            logAudit(
                $conn,
                $_SESSION['user_id'],
                'Category Deleted',
                'category',
                $category_id,
                ''
            );

        } else {

            $error = 'Failed to delete category.';
        }

        $stmt->close();
    }
}

// Fetch all categories
$categories = [];

$result = $conn->query("SELECT id, name FROM categories ORDER BY name");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

include '../includes/header.php';
?>

<div style="margin-top:30px;">

    <h2>Manage Categories</h2>

    <a href="dashboard.php" class="btn" style="margin-bottom:20px;">Back to Dashboard</a>

    <?php if ($error): ?>
        <div style="background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:15px;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="card">

        <h3>Create New Category</h3>

        <form method="POST">

            <input type="hidden" name="action" value="create_category">

            <div class="form-group">
                <label>Category Name</label>
                <input type="text" name="name" required>
            </div>

            <button type="submit" class="btn">
                Create Category
            </button>

        </form>

    </div>

    <h3 style="margin-top:30px;">All Categories</h3>

    <table>

        <thead>
            <tr>
                <th>Category Name</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($categories as $category): ?>

            <tr>

                <td><?php echo htmlspecialchars($category['name']); ?></td>

                <td>

                    <form method="POST" style="display:inline;">

                        <input type="hidden" name="action" value="delete_category">

                        <input type="hidden" name="category_id"
                               value="<?php echo $category['id']; ?>">

                        <button
                            type="submit"
                            class="btn"
                            style="background:#dc3545;padding:5px 10px;font-size:12px;"
                            onclick="return confirm('Are you sure you want to delete this category?')">

                            Delete

                        </button>

                    </form>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php include '../includes/footer.php'; ?>