<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username and Password are required.";
    } else {

        $stmt = $conn->prepare("
            SELECT u.id,
                   u.username,
                   u.password,
                   r.name AS role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.id
            WHERE u.username = ?
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_name'] = $user['role_name'];

                // Audit Log
                logAudit(
                    $conn,
                    $user['id'],
                    'User Login',
                    'user',
                    $user['id'],
                    'User logged in'
                );

                if ($user['role_name'] == 'Citizen') {

                    header("Location: ../citizen/dashboard.php");

                } elseif ($user['role_name'] == 'Area Officer') {

                    header("Location: ../officer/dashboard.php");

                } elseif ($user['role_name'] == 'Admin') {

                    header("Location: ../admin/dashboard.php");

                } else {

                    header("Location: ../index.php");

                }

                exit();

            } else {

                $error = "Invalid password.";

            }

        } else {

            $error = "User not found.";

        }

        $stmt->close();
    }
}

include '../includes/header.php';
?>

<div class="form-container">
    <h2>Login</h2>

    <?php if ($error): ?>
        <div style="background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn">Login</button>

    </form>

    <p style="margin-top:20px;">
        Don't have an account?
        <a href="register.php">Register here</a>
    </p>

</div>

<?php include '../includes/footer.php'; ?>