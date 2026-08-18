<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Area Officer']);

$officer_id = $_SESSION['user_id'];


// ===============================
// Fetch Assigned Complaints
// ===============================

$assigned_complaints = [];


$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.title,
        c.reference_number,
        c.created_at,
        cs.name AS status,
        cat.name AS category,
        u.username AS citizen

    FROM complaints c

    JOIN assignments a 
    ON c.id = a.complaint_id

    JOIN complaint_status cs 
    ON c.status_id = cs.id

    JOIN categories cat 
    ON c.category_id = cat.id

    JOIN users u 
    ON c.user_id = u.id

    WHERE a.officer_id = ?
    AND a.resolved_at IS NULL

    ORDER BY c.created_at DESC
");


$stmt->bind_param("i", $officer_id);

$stmt->execute();


$result = $stmt->get_result();


while($row = $result->fetch_assoc()){

    $assigned_complaints[] = $row;

}


$stmt->close();





// ===============================
// Count Active Assignments
// ===============================


$total_assigned = 0;


$stmt = $conn->prepare("
    SELECT COUNT(*) AS total

    FROM assignments

    WHERE officer_id = ?

    AND resolved_at IS NULL
");


$stmt->bind_param("i",$officer_id);

$stmt->execute();


$result = $stmt->get_result();


$row = $result->fetch_assoc();


$total_assigned = $row['total'];


$stmt->close();






// ===============================
// Count Resolved Complaints
// ===============================


$resolved_count = 0;



$stmt = $conn->prepare("
    SELECT COUNT(*) AS resolved

    FROM assignments a

    JOIN complaints c
    ON a.complaint_id = c.id

    WHERE a.officer_id = ?

    AND c.status_id = (
        SELECT id 
        FROM complaint_status 
        WHERE name='Resolved'
    )
");



$stmt->bind_param("i",$officer_id);


$stmt->execute();


$result = $stmt->get_result();


$row = $result->fetch_assoc();


$resolved_count = $row['resolved'];


$stmt->close();



include '../includes/header.php';

?>



<div style="margin-top:30px;">


<h2>Officer Dashboard</h2>




<div style="display:flex;gap:20px;margin-bottom:30px;">



<div class="card" style="flex:1;">

<h3>
Active Assignments
</h3>

<p style="font-size:2em;color:#e8491d;">
<?php echo $total_assigned; ?>
</p>

</div>




<div class="card" style="flex:1;">

<h3>
Resolved Issues
</h3>

<p style="font-size:2em;color:#28a745;">
<?php echo $resolved_count; ?>
</p>


</div>



</div>





<h3>
Assigned Complaints
</h3>




<?php if(count($assigned_complaints)>0): ?>



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


<?php foreach($assigned_complaints as $complaint): ?>


<tr>


<td>
<?php echo htmlspecialchars($complaint['reference_number']); ?>
</td>



<td>
<?php echo htmlspecialchars($complaint['title']); ?>
</td>



<td>
<?php echo htmlspecialchars($complaint['category']); ?>
</td>



<td class="status-<?php echo strtolower(str_replace(' ','-',$complaint['status'])); ?>">

<?php echo htmlspecialchars($complaint['status']); ?>

</td>




<td>

<?php echo date(
'M d, Y',
strtotime($complaint['created_at'])
); ?>

</td>




<td>

<a href="manage_complaint.php?id=<?php echo $complaint['id']; ?>"
class="btn"
style="padding:5px 10px;font-size:12px;">

Manage

</a>


</td>



</tr>



<?php endforeach; ?>



</tbody>


</table>



<?php else: ?>



<div class="card">

<p>
No assigned complaints at the moment.
</p>

</div>



<?php endif; ?>



</div>



<?php include '../includes/footer.php'; ?>