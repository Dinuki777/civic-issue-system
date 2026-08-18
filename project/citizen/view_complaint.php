<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Citizen']);

$user_id = $_SESSION['user_id'];

$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


// ===============================
// Fetch Complaint
// ===============================

$complaint = null;


$stmt = $conn->prepare("

    SELECT 
        c.*,
        cat.name AS category,
        cs.name AS status

    FROM complaints c

    INNER JOIN categories cat
    ON c.category_id = cat.id

    INNER JOIN complaint_status cs
    ON c.status_id = cs.id

    WHERE c.id = ?
    AND c.user_id = ?

");


$stmt->bind_param(
    "ii",
    $complaint_id,
    $user_id
);


$stmt->execute();


$result = $stmt->get_result();



if($result->num_rows == 0){

    header("Location: dashboard.php?error=not_found");
    exit();

}


$complaint = $result->fetch_assoc();





// ===============================
// Fetch Internal Notes
// ===============================

$notes = [];


$stmt = $conn->prepare("

    SELECT 
        n.note,
        n.created_at,
        u.username

    FROM internal_notes n

    INNER JOIN users u

    ON n.user_id = u.id

    WHERE n.complaint_id = ?

    ORDER BY n.created_at DESC

");


$stmt->bind_param(
    "i",
    $complaint_id
);


$stmt->execute();


$result = $stmt->get_result();



while($row = $result->fetch_assoc()){

    $notes[] = $row;

}



include '../includes/header.php';

?>



<div style="margin-top:30px;">


<a href="dashboard.php" class="btn" style="margin-bottom:20px;">
Back to Dashboard
</a>




<div class="card">


<h2>
<?php echo htmlspecialchars($complaint['title']); ?>
</h2>




<div style="
display:grid;
grid-template-columns:1fr 1fr;
gap:20px;
margin-bottom:20px;
">


<div>


<p>
<strong>Reference Number:</strong>
<?php echo htmlspecialchars($complaint['reference_number']); ?>
</p>


<p>
<strong>Category:</strong>
<?php echo htmlspecialchars($complaint['category']); ?>
</p>



<p>

<strong>Status:</strong>

<span class="status-<?php echo strtolower(str_replace(' ','-',$complaint['status'])); ?>">

<?php echo htmlspecialchars($complaint['status']); ?>

</span>

</p>



<p>
<strong>Location:</strong>
<?php echo htmlspecialchars($complaint['location']); ?>
</p>



</div>





<div>


<p>
<strong>Submitted:</strong>

<?php echo date(
'M d, Y H:i',
strtotime($complaint['created_at'])
); ?>

</p>



<p>
<strong>Last Updated:</strong>

<?php echo date(
'M d, Y H:i',
strtotime($complaint['updated_at'])
); ?>

</p>




<?php if($complaint['is_anonymous']): ?>

<p>
<strong>Submitted as:</strong>
Anonymous
</p>

<?php endif; ?>


</div>


</div>





<h3>Description</h3>


<p>

<?php echo nl2br(htmlspecialchars($complaint['description'])); ?>

</p>





<?php if(!empty($complaint['image_url'])): ?>


<h3>Attached Image</h3>


<img 
src="<?php echo BASE_URL . $complaint['image_url']; ?>"
alt="Complaint Image"
style="max-width:400px;border-radius:5px;"
>


<?php endif; ?>






<?php if(count($notes)>0): ?>


<h3>Officer Updates</h3>



<?php foreach($notes as $note): ?>


<div style="
background:#f8f9fa;
padding:15px;
border-radius:5px;
margin-bottom:10px;
">


<p>

<strong>
<?php echo htmlspecialchars($note['username']); ?>
</strong>

-

<?php echo date(
'M d, Y H:i',
strtotime($note['created_at'])
); ?>


</p>



<p>

<?php echo nl2br(htmlspecialchars($note['note'])); ?>

</p>


</div>



<?php endforeach; ?>



<?php endif; ?>



</div>


</div>




<?php include '../includes/footer.php'; ?>