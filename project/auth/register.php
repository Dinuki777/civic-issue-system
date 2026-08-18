<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {


    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Default role = Citizen
    $role_id = 1;



    // Validation

    if (empty($username) || empty($email) || empty($password)) {

        $error = 'All fields are required.';


    } elseif ($password !== $confirm_password) {

        $error = 'Passwords do not match.';


    } elseif (strlen($password) < 6) {

        $error = 'Password must be at least 6 characters.';


    } else {



        // Check existing user

        $stmt = $conn->prepare("
            SELECT id 
            FROM users 
            WHERE username = ? 
            OR email = ?
        ");


        $stmt->bind_param(
            "ss",
            $username,
            $email
        );


        $stmt->execute();


        $result = $stmt->get_result();



        if ($result->num_rows > 0) {


            $error = 'Username or email already exists.';



        } else {



            // Password hash

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );




            // Insert User

            $stmt = $conn->prepare("
                INSERT INTO users
                (
                    username,
                    email,
                    password,
                    role_id
                )
                VALUES (?, ?, ?, ?)
            ");



            $stmt->bind_param(
                "sssi",
                $username,
                $email,
                $hashed_password,
                $role_id
            );




            if ($stmt->execute()) {


                $success = 'Registration successful! <a href="login.php">Login here</a>';



                logAudit(
                    $conn,
                    null,
                    'User Registration',
                    'user',
                    $stmt->insert_id,
                    "New user registered: $username"
                );



            } else {


                $error = 'Registration failed. Please try again.';


            }


        }


    }


}



include '../includes/header.php';

?>



<div class="form-container">


<h2>Register</h2>




<?php if($error): ?>


<div style="
background:#f8d7da;
color:#721c24;
padding:10px;
border-radius:5px;
margin-bottom:15px;
">

<?php echo $error; ?>

</div>


<?php endif; ?>





<?php if($success): ?>


<div style="
background:#d4edda;
color:#155724;
padding:10px;
border-radius:5px;
margin-bottom:15px;
">

<?php echo $success; ?>


</div>




<?php else: ?>



<form method="POST">


<div class="form-group">


<label>
Username:
</label>


<input 
type="text"
name="username"
required
>


</div>





<div class="form-group">


<label>
Email:
</label>


<input 
type="email"
name="email"
required
>


</div>





<div class="form-group">


<label>
Password:
</label>


<input 
type="password"
name="password"
required
>


</div>





<div class="form-group">


<label>
Confirm Password:
</label>


<input 
type="password"
name="confirm_password"
required
>


</div>




<button type="submit" class="btn">
Register
</button>



</form>



<p style="margin-top:20px;">
Already have an account?
<a href="login.php">
Login here
</a>
</p>



<?php endif; ?>


</div>



<?php include '../includes/footer.php'; ?>