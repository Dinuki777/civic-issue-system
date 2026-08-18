<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Admin']);

$error = '';
$success = '';


// =========================
// Handle Form Actions
// =========================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {


    // =========================
    // CREATE USER
    // =========================

    if ($_POST['action'] == 'create_user') {


        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $role_id = (int)$_POST['role_id'];


        if(empty($username) || empty($email) || empty($password) || empty($role_id)){


            $error = "All fields are required.";


        } else {


            // Check duplicate username/email

            $check = $conn->prepare(
                "SELECT id FROM users WHERE username=? OR email=?"
            );

            $check->bind_param(
                "ss",
                $username,
                $email
            );

            $check->execute();

            $result = $check->get_result();


            if($result->num_rows > 0){


                $error = "Username or Email already exists.";


            } else {


                // Encrypt password

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                $stmt = $conn->prepare(
                    "INSERT INTO users 
                    (username,email,password,role_id) 
                    VALUES (?,?,?,?)"
                );


                $stmt->bind_param(
                    "sssi",
                    $username,
                    $email,
                    $hashed_password,
                    $role_id
                );


                if($stmt->execute()){


                    $success = "User created successfully.";


                    logAudit(
                        $conn,
                        $_SESSION['user_id'],
                        "User Created",
                        "user",
                        $stmt->insert_id,
                        "Username: ".$username
                    );


                }else{


                    $error = "Failed to create user.";


                }


                $stmt->close();

            }


            $check->close();

        }

    }




    // =========================
    // DELETE USER
    // =========================


    if($_POST['action']=="delete_user"){


        $user_id = (int)$_POST['user_id'];



        if($user_id == $_SESSION['user_id']){


            $error = "You cannot delete your own account.";



        }else{


            $stmt = $conn->prepare(
                "DELETE FROM users WHERE id=?"
            );


            $stmt->bind_param(
                "i",
                $user_id
            );



            if($stmt->execute()){


                $success="User deleted successfully.";


                logAudit(
                    $conn,
                    $_SESSION['user_id'],
                    "User Deleted",
                    "user",
                    $user_id,
                    ""
                );



            }else{


                $error="Failed to delete user.";


            }


            $stmt->close();


        }


    }


}



// =========================
// FETCH USERS
// =========================


$users=[];


$result=$conn->query("
SELECT 
u.id,
u.username,
u.email,
r.name AS role

FROM users u

INNER JOIN roles r 
ON u.role_id=r.id

ORDER BY u.created_at DESC
");



if($result){


    while($row=$result->fetch_assoc()){


        $users[]=$row;


    }

}




// =========================
// FETCH ROLES
// =========================


$roles=[];


$result=$conn->query("
SELECT id,name 
FROM roles
ORDER BY name
");



if($result){


    while($row=$result->fetch_assoc()){


        $roles[]=$row;


    }


}



include '../includes/header.php';

?>


<div style="margin-top:30px;">


<h2>Manage Users</h2>


<a href="dashboard.php" class="btn">
Back to Dashboard
</a>



<?php if($error): ?>

<div style="
background:#f8d7da;
color:#721c24;
padding:10px;
margin:15px 0;
border-radius:5px;
">

<?php echo $error; ?>

</div>

<?php endif; ?>




<?php if($success): ?>

<div style="
background:#d4edda;
color:#155724;
padding:10px;
margin:15px 0;
border-radius:5px;
">

<?php echo $success; ?>

</div>

<?php endif; ?>





<div class="card">


<h3>Create New User</h3>



<form method="POST">


<input type="hidden" name="action" value="create_user">


<div style="
display:grid;
grid-template-columns:1fr 1fr;
gap:15px;
">


<div class="form-group">

<label>Username</label>

<input type="text" name="username" required>

</div>



<div class="form-group">

<label>Email</label>

<input type="email" name="email" required>

</div>




<div class="form-group">

<label>Password</label>

<input type="password" name="password" required>

</div>




<div class="form-group">

<label>Role</label>


<select name="role_id" required>


<option value="">
Select Role
</option>



<?php foreach($roles as $role): ?>


<option value="<?php echo $role['id']; ?>">

<?php echo htmlspecialchars($role['name']); ?>

</option>


<?php endforeach; ?>


</select>


</div>


</div>


<br>


<button class="btn" type="submit">

Create User

</button>


</form>


</div>





<h3 style="margin-top:30px;">
All Users
</h3>



<table>


<thead>

<tr>

<th>Username</th>
<th>Email</th>
<th>Role</th>
<th>Action</th>

</tr>

</thead>




<tbody>



<?php foreach($users as $user): ?>


<tr>


<td>
<?php echo htmlspecialchars($user['username']); ?>
</td>



<td>
<?php echo htmlspecialchars($user['email']); ?>
</td>




<td>
<?php echo htmlspecialchars($user['role']); ?>
</td>




<td>



<?php if($user['id'] != $_SESSION['user_id']): ?>



<form method="POST">


<input type="hidden" name="action" value="delete_user">


<input type="hidden" 
name="user_id"
value="<?php echo $user['id']; ?>">



<button 
class="btn"
style="background:#dc3545;"
onclick="return confirm('Delete this user?');">

Delete

</button>


</form>


<?php endif; ?>


</td>


</tr>


<?php endforeach; ?>



</tbody>


</table>



</div>



<?php include '../includes/footer.php'; ?>