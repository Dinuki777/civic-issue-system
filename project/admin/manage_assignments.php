<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Admin']);

$error = '';
$success = '';


// ===============================
// Handle Assignment Creation
// ===============================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] == 'create_assignment') {

        $complaint_id = (int)$_POST['complaint_id'];
        $officer_id = (int)$_POST['officer_id'];


        if (empty($complaint_id) || empty($officer_id)) {

            $error = "All fields are required.";

        } else {


            // Check already assigned

            $stmt = $conn->prepare("
                SELECT id 
                FROM assignments 
                WHERE complaint_id = ? 
                AND resolved_at IS NULL
            ");

            $stmt->bind_param("i", $complaint_id);
            $stmt->execute();

            $result = $stmt->get_result();


            if ($result->num_rows > 0) {

                $error = "This complaint is already assigned.";

            } else {


                // Create assignment

                $stmt = $conn->prepare("
                    INSERT INTO assignments 
                    (complaint_id, officer_id) 
                    VALUES (?, ?)
                ");

                $stmt->bind_param(
                    "ii",
                    $complaint_id,
                    $officer_id
                );


                if ($stmt->execute()) {


                    // Update complaint status

                    $stmt2 = $conn->prepare("
                        UPDATE complaints 
                        SET status_id = (
                            SELECT id 
                            FROM complaint_status 
                            WHERE name='Assigned'
                            LIMIT 1
                        )
                        WHERE id = ?
                    ");

                    $stmt2->bind_param(
                        "i",
                        $complaint_id
                    );

                    $stmt2->execute();



                    $success = "Assignment created successfully!";


                    logAudit(
                        $conn,
                        $_SESSION['user_id'],
                        'Assignment Created',
                        'assignment',
                        $stmt->insert_id,
                        "Complaint: $complaint_id, Officer: $officer_id"
                    );


                } else {

                    $error = "Failed to create assignment.";

                }

            }

            $stmt->close();

        }

    }

}



// ===============================
// Fetch Unassigned Complaints
// ===============================

$unassigned_complaints = [];


$result = $conn->query("

    SELECT 
        c.id,
        c.reference_number,
        c.title

    FROM complaints c

    LEFT JOIN assignments a

    ON c.id = a.complaint_id

    AND a.resolved_at IS NULL

    WHERE a.id IS NULL

    ORDER BY c.created_at DESC

");


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $unassigned_complaints[] = $row;

    }

}




// ===============================
// Fetch Area Officers
// ===============================

$officers = [];


$result = $conn->query("

    SELECT 
        u.id,
        u.username

    FROM users u

    INNER JOIN roles r

    ON u.role_id = r.id

    WHERE r.name = 'Area Officer'

    ORDER BY u.username

");


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $officers[] = $row;

    }

}




// ===============================
// Fetch Current Assignments
// ===============================

$assignments = [];


$result = $conn->query("

    SELECT 

        a.id,
        c.reference_number,
        c.title,
        u.username,
        a.assigned_at


    FROM assignments a


    INNER JOIN complaints c

    ON a.complaint_id = c.id


    INNER JOIN users u

    ON a.officer_id = u.id


    WHERE a.resolved_at IS NULL


    ORDER BY a.assigned_at DESC

");


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $assignments[] = $row;

    }

}



include '../includes/header.php';

?>


<div style="margin-top:30px;">


<h2>Manage Assignments</h2>


<a href="dashboard.php" class="btn" style="margin-bottom:20px;">
Back to Dashboard
</a>



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

<?php endif; ?>





<div class="card">


<h3>Create New Assignment</h3>


<form method="POST">


<input type="hidden" name="action" value="create_assignment">


<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">



<div class="form-group">


<label>
Complaint:
</label>


<select name="complaint_id" required>


<option value="">
Select a complaint
</option>


<?php foreach($unassigned_complaints as $complaint): ?>


<option value="<?php echo $complaint['id']; ?>">


<?php echo 
$complaint['reference_number']
." - ".
$complaint['title'];
?>


</option>


<?php endforeach; ?>


</select>


</div>





<div class="form-group">


<label>
Officer:
</label>


<select name="officer_id" required>


<option value="">
Select an officer
</option>



<?php foreach($officers as $officer): ?>


<option value="<?php echo $officer['id']; ?>">


<?php echo $officer['username']; ?>


</option>


<?php endforeach; ?>


</select>


</div>



</div>



<button type="submit" class="btn">
Create Assignment
</button>


</form>


</div>





<h3>Current Assignments</h3>




<?php if(count($assignments)>0): ?>


<table>


<thead>

<tr>

<th>Reference #</th>
<th>Title</th>
<th>Officer</th>
<th>Assigned Date</th>

</tr>


</thead>



<tbody>


<?php foreach($assignments as $assignment): ?>


<tr>


<td>
<?php echo $assignment['reference_number']; ?>
</td>


<td>
<?php echo $assignment['title']; ?>
</td>


<td>
<?php echo $assignment['username']; ?>
</td>


<td>

<?php 

echo date(
'M d, Y',
strtotime($assignment['assigned_at'])
);

?>

</td>


</tr>



<?php endforeach; ?>


</tbody>


</table>



<?php else: ?>


<div class="card">

<p>
No active assignments.
</p>

</div>


<?php endif; ?>



</div>



<?php include '../includes/footer.php'; ?>