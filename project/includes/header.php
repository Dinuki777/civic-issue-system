
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civic Reporting System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><span class="highlight">Civic</span> Reporting</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                    <li><a href="<?php echo BASE_URL; ?>statistics.php">Statistics</a></li>
                    <?php if (isLoggedIn()): ?>
                        <?php if ($_SESSION['role_name'] == 'Citizen'): ?>
                            <li><a href="<?php echo BASE_URL; ?>citizen/dashboard.php">Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>citizen/submit_complaint.php">Report Issue</a></li>
                        <?php elseif ($_SESSION['role_name'] == 'Area Officer'): ?>
                            <li><a href="<?php echo BASE_URL; ?>officer/dashboard.php">Officer Portal</a></li>
                        <?php elseif ($_SESSION['role_name'] == 'Admin'): ?>
                            <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>auth/logout.php">Logout (<?php echo $_SESSION['username']; ?>)</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>auth/login.php">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>auth/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
