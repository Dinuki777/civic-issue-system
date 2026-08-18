<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Admin']);


// Filters
$filter_status = isset($_GET['status']) ? (int)$_GET['status'] : 0;
$filter_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;


// Base Query
$query = "
SELECT 
    c.id,
    c.title,
    c.reference_number,
    c.created_at,
    cs.name AS status,
    cat.name AS category,
    u.username AS citizen

FROM complaints c

INNER JOIN complaint_status cs
ON c.status_id = cs.id

INNER JOIN categories cat
ON c.category_id = cat.id

INNER JOIN users u
ON c.user_id = u.id

WHERE 1=1
";


// Add filters
if ($filter_status > 0) {
    $query .= " AND c.status_id = $filter_status";
}


if ($filter_category > 0) {
    $query .= " AND c.category_id = $filter_category";
}


$query .= " ORDER BY c.created_at DESC";


// Fetch Complaints

$complaints = [];

$result = $conn->query($query);


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $complaints[] = $row;

    }

}



// Fetch Statuses

$statuses = [];

$result = $conn->query("
    SELECT id, name 
    FROM complaint_status
    ORDER BY name
");


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $statuses[] = $row;

    }

}



// Fetch Categories

$categories = [];

$result = $conn->query("
    SELECT id, name 
    FROM categories
    ORDER BY name
");


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $categories[] = $row;

    }

}



include '../includes/header.php';

?>


<div style="margin-top:30px;">


<h2>All Complaints</h2>


<a href="dashboard.php" class="btn" style="margin-bottom:20px;">
Back to Dashboard
</a>




<div class="card">


<h3>Filter</h3>


<form method="GET"
style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;">



<div class="form-group">

<label>Status:</label>


<select name="status">


<option value="">
All Statuses
</option>


<?php foreach($statuses as $status): ?>


<option value="<?php echo $status['id']; ?>"
<?php echo ($filter_status == $status['id']) ? 'selected' : ''; ?>
>

<?php echo $status['name']; ?>

</option>


<?php endforeach; ?>


</select>


</div>





<div class="form-group">


<label>Category:</label>


<select name="category">


<option value="">
All Categories
</option>


<?php foreach($categories as $category): ?>


<option value="<?php echo $category['id']; ?>"
<?php echo ($filter_category == $category['id']) ? 'selected' : ''; ?>
>


<?php echo $category['name']; ?>


</option>


<?php endforeach; ?>


</select>


</div>




<div style="display:flex;align-items:flex-end;">

<button type="submit" class="btn" style="width:100%;">
Filter
</button>


</div>


</form>


</div>





<h3>
Results: <?php echo count($complaints); ?> complaint(s)
</h3>





<?php if(count($complaints)>0): ?>


<table>


<thead>

<tr>

<th>Reference #</th>
<th>Title</th>
<th>Category</th>
<th>Status</th>
<th>Citizen</th>
<th>Submitted</th>

</tr>


</thead>



<tbody>


<?php foreach($complaints as $complaint): ?>


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
<?php echo htmlspecialchars($complaint['citizen']); ?>
</td>


<td>

<?php echo date(
'M d, Y',
strtotime($complaint['created_at'])
); ?>

</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>




<?php else: ?>


<div class="card">

<p>
No complaints found matching the filters.
</p>


</div>


<?php endif; ?>



</div>



<?php include '../includes/footer.php'; ?>