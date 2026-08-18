<?php

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Area Officer']);


$officer_id = $_SESSION['user_id'];
$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


$error = '';
$success = '';



// ==========================
// Check Assignment
// ==========================

$stmt = $conn->prepare("
    SELECT id 
    FROM assignments
    WHERE complaint_id = ?
    AND officer_id = ?
    AND resolved_at IS NULL
");


$stmt->bind_param(
    "ii",
    $complaint_id,
    $officer_id
);


$stmt->execute();


$result = $stmt->get_result();


if($result->num_rows == 0){

    header("Location: dashboard.php?error=not_assigned");
    exit();

}




// ==========================
// POST ACTIONS
// ==========================


if($_SERVER['REQUEST_METHOD']=="POST"){


    $action = $_POST['action'];



    // ======================
    // Update Status
    // ======================


    if($action=="update_status"){


        $new_status_id = (int)$_POST['status_id'];



        $stmt=$conn->prepare("
            UPDATE complaints
            SET status_id=?
            WHERE id=?
        ");


        $stmt->bind_param(
            "ii",
            $new_status_id,
            $complaint_id
        );



        if($stmt->execute()){



            logAudit(
                $conn,
                $officer_id,
                "Complaint Status Updated",
                "complaint",
                $complaint_id,
                "New status ID ".$new_status_id
            );



            // Get citizen

            $stmt=$conn->prepare("
                SELECT user_id
                FROM complaints
                WHERE id=?
            ");


            $stmt->bind_param(
                "i",
                $complaint_id
            );


            $stmt->execute();


            $citizen =
            $stmt->get_result()->fetch_assoc();



            // Get status name


            $stmt=$conn->prepare("
                SELECT name
                FROM complaint_status
                WHERE id=?
            ");



            $stmt->bind_param(
                "i",
                $new_status_id
            );


            $stmt->execute();



            $status =
            $stmt->get_result()->fetch_assoc();




            addNotification(
                $conn,
                $citizen['user_id'],
                $complaint_id,
                "Complaint status changed to ".$status['name']
            );



            $success="Status updated successfully";



        }
        else{


            $error="Status update failed";


        }


    }






    // ======================
    // Add Internal Note
    // ======================


    if($action=="add_note"){


        $note = sanitize($_POST['note']);



        if(empty($note)){


            $error="Note cannot be empty";


        }
        else{


            $stmt=$conn->prepare("
                INSERT INTO internal_notes
                (
                    complaint_id,
                    user_id,
                    note
                )
                VALUES(?,?,?)
            ");



            $stmt->bind_param(
                "iis",
                $complaint_id,
                $officer_id,
                $note
            );



            if($stmt->execute()){



                logAudit(
                    $conn,
                    $officer_id,
                    "Internal Note Added",
                    "complaint",
                    $complaint_id,
                    "Note added"
                );



                $success="Note added successfully";



            }
            else{


                $error="Note adding failed";


            }


        }


    }



}







// ==========================
// Fetch Complaint
// ==========================


$stmt=$conn->prepare("
SELECT

c.*,
cat.name AS category,
cs.name AS status,
u.username AS citizen

FROM complaints c

JOIN categories cat
ON c.category_id=cat.id

JOIN complaint_status cs
ON c.status_id=cs.id

JOIN users u
ON c.user_id=u.id

WHERE c.id=?
");



$stmt->bind_param(
    "i",
    $complaint_id
);



$stmt->execute();



$complaint =
$stmt->get_result()->fetch_assoc();







// ==========================
// Status List
// ==========================


$status_result =
$conn->query("
SELECT id,name 
FROM complaint_status
");



$statuses=[];


while($row=$status_result->fetch_assoc()){

    $statuses[]=$row;

}






// ==========================
// Notes
// ==========================


$stmt=$conn->prepare("
SELECT

n.note,
n.created_at,
u.username

FROM internal_notes n

JOIN users u
ON n.user_id=u.id

WHERE n.complaint_id=?

ORDER BY n.created_at DESC
");



$stmt->bind_param(
    "i",
    $complaint_id
);



$stmt->execute();



$notes=[];


$result=$stmt->get_result();


while($row=$result->fetch_assoc()){

    $notes[]=$row;

}



include '../includes/header.php';

?>



<div style="margin-top:30px;">


<a href="dashboard.php" class="btn">
Back Dashboard
</a>




<?php if($error): ?>

<div class="alert">
<?php echo $error; ?>
</div>

<?php endif; ?>



<?php if($success): ?>

<div class="success">
<?php echo $success; ?>
</div>

<?php endif; ?>






<div class="card">


<h2>
<?php echo htmlspecialchars($complaint['title']); ?>
</h2>



<p>
<b>Reference:</b>
<?php echo $complaint['reference_number']; ?>
</p>


<p>
<b>Category:</b>
<?php echo $complaint['category']; ?>
</p>


<p>
<b>Status:</b>
<?php echo $complaint['status']; ?>
</p>


<p>
<b>Citizen:</b>

<?php

echo $complaint['is_anonymous']
?
"Anonymous"
:
$complaint['citizen'];

?>

</p>


<p>
<b>Location:</b>

<?php echo $complaint['location']; ?>

</p>



<h3>Description</h3>


<p>

<?php

echo nl2br(
htmlspecialchars($complaint['description'])
);

?>

</p>



<?php if(!empty($complaint['image_url'])): ?>

<img 
src="<?php echo BASE_URL.$complaint['image_url']; ?>"
width="400"
>

<?php endif; ?>


</div>








<div class="card">


<h3>
Update Status
</h3>



<form method="POST">


<input type="hidden" name="action" value="update_status">


<select name="status_id">


<?php foreach($statuses as $s): ?>


<option value="<?php echo $s['id']; ?>">

<?php echo $s['name']; ?>

</option>


<?php endforeach; ?>


</select>


<br><br>


<button class="btn">
Update
</button>



</form>


</div>







<div class="card">


<h3>
Add Note
</h3>


<form method="POST">


<input type="hidden" name="action" value="add_note">


<textarea 
name="note"
rows="5"
style="width:100%"
></textarea>


<br><br>


<button class="btn">
Add Note
</button>


</form>


</div>







<div class="card">


<h3>
Internal Notes
</h3>


<?php foreach($notes as $n): ?>


<div>


<b>
<?php echo $n['username']; ?>
</b>


<br>


<?php echo $n['note']; ?>


<hr>


</div>


<?php endforeach; ?>


</div>



</div>



<?php include '../includes/footer.php'; ?>