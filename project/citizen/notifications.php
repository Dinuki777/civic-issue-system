<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Citizen']);

$user_id = $_SESSION['user_id'];


// ===============================
// Mark all notifications as read
// ===============================

if (isset($_GET['mark_read'])) {

    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = ? 
        AND is_read = 0
    ");

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

}




// ===============================
// Fetch Notifications
// ===============================

$notifications = [];


$stmt = $conn->prepare("

    SELECT 
        n.id,
        n.message,
        n.is_read,
        n.created_at,
        n.complaint_id

    FROM notifications n

    WHERE n.user_id = ?

    ORDER BY n.created_at DESC

");


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$result = $stmt->get_result();



while($row = $result->fetch_assoc()){

    $notifications[] = $row;

}



include '../includes/header.php';

?>


<div style="margin-top:30px;">


<h2>Notifications</h2>




<div style="margin-bottom:20px;">


<a href="dashboard.php" class="btn" style="margin-right:10px;">
Back to Dashboard
</a>



<?php if(count($notifications)>0): ?>


<a href="?mark_read=1" 
class="btn" 
style="background:#6c757d;">

Mark All as Read

</a>


<?php endif; ?>


</div>






<?php if(count($notifications)>0): ?>


<?php foreach($notifications as $notif): ?>


<div class="card"
style="
<?php echo $notif['is_read'] 
? '' 
: 'border-left:4px solid #e8491d;'; 
?>
">


<p>

<?php echo htmlspecialchars($notif['message']); ?>

</p>



<small>

<?php echo date(
'M d, Y H:i',
strtotime($notif['created_at'])
); ?>

</small>




<?php if(!empty($notif['complaint_id'])): ?>


<br>


<a href="view_complaint.php?id=<?php echo $notif['complaint_id']; ?>"
class="btn"
style="
padding:5px 10px;
font-size:12px;
margin-top:10px;
">

View Complaint

</a>



<?php endif; ?>



</div>



<?php endforeach; ?>





<?php else: ?>


<div class="card">

<p>
You have no notifications.
</p>

</div>


<?php endif; ?>



</div>



<?php include '../includes/footer.php'; ?>