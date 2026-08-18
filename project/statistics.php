<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

include 'includes/header.php';


// ===============================
// Public Statistics
// ===============================


// Total complaints

$result = $conn->query("
    SELECT COUNT(*) AS total 
    FROM complaints
");

$row = $result->fetch_assoc();

$total_complaints = $row['total'];




// Resolved complaints

$result = $conn->query("
    SELECT COUNT(*) AS resolved
    FROM complaints
    WHERE status_id = (
        SELECT id 
        FROM complaint_status 
        WHERE name='Resolved'
        LIMIT 1
    )
");

$row = $result->fetch_assoc();

$resolved = $row['resolved'];




// In Progress complaints

$result = $conn->query("
    SELECT COUNT(*) AS in_progress
    FROM complaints
    WHERE status_id = (
        SELECT id 
        FROM complaint_status 
        WHERE name='In Progress'
        LIMIT 1
    )
");


$row = $result->fetch_assoc();

$in_progress = $row['in_progress'];




// ===============================
// Category Statistics
// ===============================


$category_stats = [];


$result = $conn->query("

    SELECT 
        cat.name,
        COUNT(*) AS count

    FROM complaints c

    INNER JOIN categories cat
    ON c.category_id = cat.id

    GROUP BY cat.id

    ORDER BY count DESC

");


if($result){

    while($row = $result->fetch_assoc()){

        $category_stats[] = $row;

    }

}




// ===============================
// Status Statistics
// ===============================


$status_stats = [];


$result = $conn->query("

    SELECT 
        cs.name,
        COUNT(*) AS count

    FROM complaints c

    INNER JOIN complaint_status cs

    ON c.status_id = cs.id

    GROUP BY cs.id

    ORDER BY count DESC

");



if($result){

    while($row = $result->fetch_assoc()){

        $status_stats[] = $row;

    }

}

?>



<div style="margin-top:30px;">


<h2>Public Statistics</h2>


<p>
This page displays public statistics about the civic reporting system.
Individual complaint details remain private and confidential.
</p>





<div style="
display:grid;
grid-template-columns:repeat(3,1fr);
gap:20px;
margin-bottom:30px;
">



<div class="card">

<h3>Total Reports</h3>

<p style="font-size:2em;color:#e8491d;">

<?php echo $total_complaints; ?>

</p>

</div>




<div class="card">

<h3>Resolved Issues</h3>

<p style="font-size:2em;color:#28a745;">

<?php echo $resolved; ?>

</p>

</div>




<div class="card">

<h3>In Progress</h3>

<p style="font-size:2em;color:#ffc107;">

<?php echo $in_progress; ?>

</p>

</div>



</div>






<div style="
display:grid;
grid-template-columns:1fr 1fr;
gap:20px;
">





<div class="card">


<h3>Reports by Category</h3>


<table>


<thead>

<tr>

<th>Category</th>
<th>Count</th>

</tr>

</thead>



<tbody>


<?php foreach($category_stats as $stat): ?>


<tr>


<td>
<?php echo htmlspecialchars($stat['name']); ?>
</td>


<td>
<?php echo $stat['count']; ?>
</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>







<div class="card">


<h3>Reports by Status</h3>


<table>


<thead>

<tr>

<th>Status</th>
<th>Count</th>

</tr>

</thead>



<tbody>


<?php foreach($status_stats as $stat): ?>


<tr>


<td>
<?php echo htmlspecialchars($stat['name']); ?>
</td>


<td>
<?php echo $stat['count']; ?>
</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>





</div>


</div>



<?php include 'includes/footer.php'; ?>