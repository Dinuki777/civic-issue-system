<?php

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

checkRole(['Citizen']);

$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Fetch Categories
|--------------------------------------------------------------------------
*/
$sql = "SELECT id, name FROM categories ORDER BY name ASC";
$result = $conn->query($sql);

$categories = [];

if ($result) {
    $categories = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $error = "Unable to load categories.";
}

/*
|--------------------------------------------------------------------------
| Fetch Priority Levels
|--------------------------------------------------------------------------
*/
$sql = "SELECT id, name FROM priority_levels ORDER BY id ASC";
$result = $conn->query($sql);

$priorities = [];

if ($result) {
    $priorities = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $error = "Unable to load priority levels.";
}

/*
|--------------------------------------------------------------------------
| Handle Complaint Submission
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form data safely
    $title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';

    $category_id = isset($_POST['category_id'])
        ? (int) $_POST['category_id']
        : 0;

    $priority_id = isset($_POST['priority_id'])
        ? (int) $_POST['priority_id']
        : 0;

    $location = isset($_POST['location'])
        ? sanitize($_POST['location'])
        : '';

    /*
    |--------------------------------------------------------------------------
    | Latitude and Longitude
    |--------------------------------------------------------------------------
    */
    $latitude = null;
    $longitude = null;

    if (
        isset($_POST['latitude']) &&
        $_POST['latitude'] !== ''
    ) {
        $latitude = (float) $_POST['latitude'];
    }

    if (
        isset($_POST['longitude']) &&
        $_POST['longitude'] !== ''
    ) {
        $longitude = (float) $_POST['longitude'];
    }

    /*
    |--------------------------------------------------------------------------
    | Anonymous Complaint
    |--------------------------------------------------------------------------
    */
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    if (
        empty($title) ||
        empty($description) ||
        $category_id <= 0 ||
        $priority_id <= 0 ||
        empty($location)
    ) {

        $error = "All required fields must be filled.";

    } elseif (strlen($title) > 255) {

        $error = "Issue title is too long.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Generate Reference Number
        |--------------------------------------------------------------------------
        */
        $reference_number = generateReferenceNumber();

        /*
        |--------------------------------------------------------------------------
        | Handle Image Upload
        |--------------------------------------------------------------------------
        */
        $image_url = null;

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $file = $_FILES['image'];

            // Check upload error
            if ($file['error'] !== UPLOAD_ERR_OK) {

                $error = "There was an error uploading the image.";

            } elseif ($file['size'] > 5000000) {

                $error = "File size must not exceed 5MB.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Check Real MIME Type
                |--------------------------------------------------------------------------
                */
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowed_types = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif'
                ];

                if (!array_key_exists($mime_type, $allowed_types)) {

                    $error = "Only JPEG, PNG, and GIF images are allowed.";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Create Upload Directory
                    |--------------------------------------------------------------------------
                    */
                    $upload_dir = '../assets/images/';

                    if (!is_dir($upload_dir)) {

                        if (!mkdir($upload_dir, 0755, true)) {
                            $error = "Failed to create upload directory.";
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Save Image
                    |--------------------------------------------------------------------------
                    */
                    if (empty($error)) {

                        $extension = $allowed_types[$mime_type];

                        $filename = uniqid('complaint_', true) . '.' . $extension;

                        $filepath = $upload_dir . $filename;

                        if (move_uploaded_file($file['tmp_name'], $filepath)) {

                            $image_url = 'assets/images/' . $filename;

                        } else {

                            $error = "Failed to upload image.";
                        }
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Insert Complaint
        |--------------------------------------------------------------------------
        */
        if (empty($error)) {

            $sql = "
                INSERT INTO complaints
                (
                    user_id,
                    title,
                    description,
                    category_id,
                    location,
                    latitude,
                    longitude,
                    image_url,
                    status_id,
                    priority_id,
                    reference_number,
                    is_anonymous
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    1,
                    ?,
                    ?,
                    ?
                )
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {

                $error = "Database error: " . $conn->error;

            } else {

                /*
                |--------------------------------------------------------------------------
                | Bind Parameters
                |--------------------------------------------------------------------------
                |
                | i = integer
                | s = string
                | d = double
                |
                */
                $stmt->bind_param(
                    "issisddsisi",
                    $user_id,
                    $title,
                    $description,
                    $category_id,
                    $location,
                    $latitude,
                    $longitude,
                    $image_url,
                    $priority_id,
                    $reference_number,
                    $is_anonymous
                );

                /*
                |--------------------------------------------------------------------------
                | Execute Insert
                |--------------------------------------------------------------------------
                */
                if ($stmt->execute()) {

                    $complaint_id = $conn->insert_id;

                    /*
                    |--------------------------------------------------------------------------
                    | Audit Log
                    |--------------------------------------------------------------------------
                    */
                    logAudit(
                        $conn,
                        $user_id,
                        'Complaint Submitted',
                        'complaint',
                        $complaint_id,
                        "Reference: $reference_number"
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Notification
                    |--------------------------------------------------------------------------
                    */
                    addNotification(
                        $conn,
                        $user_id,
                        $complaint_id,
                        "Your complaint has been received. Reference: $reference_number"
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Success Message
                    |--------------------------------------------------------------------------
                    */
                    $success =
                        "Complaint submitted successfully! " .
                        "Your reference number is: <strong>" .
                        htmlspecialchars($reference_number, ENT_QUOTES, 'UTF-8') .
                        "</strong>";

                } else {

                    $error =
                        "Failed to submit complaint. Please try again. " .
                        "Database error: " . $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}

include '../includes/header.php';

?>

<div class="form-container" style="max-width: 700px;">

    <h2>Submit a Complaint</h2>

    <?php if (!empty($error)): ?>

        <div
            style="
                background: #f8d7da;
                color: #721c24;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 15px;
            "
        >
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>

    <?php endif; ?>


    <?php if (!empty($success)): ?>

        <div
            style="
                background: #d4edda;
                color: #155724;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 15px;
            "
        >
            <?php echo $success; ?>
        </div>

        <p>
            <a href="dashboard.php" class="btn">
                Back to Dashboard
            </a>
        </p>

    <?php else: ?>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <!-- Issue Title -->
            <div class="form-group">

                <label for="title">
                    Issue Title:
                    <span style="color: red;">*</span>
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="255"
                    value="<?php
                        echo isset($_POST['title'])
                            ? htmlspecialchars(
                                $_POST['title'],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : '';
                    ?>"
                    required
                >

            </div>


            <!-- Description -->
            <div class="form-group">

                <label for="description">
                    Description:
                    <span style="color: red;">*</span>
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    required
                ><?php
                    echo isset($_POST['description'])
                        ? htmlspecialchars(
                            $_POST['description'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        : '';
                ?></textarea>

            </div>


            <!-- Category -->
            <div class="form-group">

                <label for="category_id">
                    Category:
                    <span style="color: red;">*</span>
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    required
                >

                    <option value="">
                        Select a category
                    </option>

                    <?php foreach ($categories as $cat): ?>

                        <option
                            value="<?php echo (int) $cat['id']; ?>"
                            <?php
                            if (
                                isset($_POST['category_id']) &&
                                $_POST['category_id'] == $cat['id']
                            ) {
                                echo 'selected';
                            }
                            ?>
                        >
                            <?php
                            echo htmlspecialchars(
                                $cat['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Priority -->
            <div class="form-group">

                <label for="priority_id">
                    Priority Level:
                    <span style="color: red;">*</span>
                </label>

                <select
                    id="priority_id"
                    name="priority_id"
                    required
                >

                    <option value="">
                        Select priority
                    </option>

                    <?php foreach ($priorities as $priority): ?>

                        <option
                            value="<?php echo (int) $priority['id']; ?>"
                            <?php
                            if (
                                isset($_POST['priority_id']) &&
                                $_POST['priority_id'] == $priority['id']
                            ) {
                                echo 'selected';
                            }
                            ?>
                        >
                            <?php
                            echo htmlspecialchars(
                                $priority['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Location -->
            <div class="form-group">

                <label for="location">
                    Location:
                    <span style="color: red;">*</span>
                </label>

                <input
                    type="text"
                    id="location"
                    name="location"
                    maxlength="255"
                    placeholder="e.g., Main Street, near City Hall"
                    value="<?php
                        echo isset($_POST['location'])
                            ? htmlspecialchars(
                                $_POST['location'],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : '';
                    ?>"
                    required
                >

            </div>


            <!-- Latitude -->
            <div class="form-group">

                <label for="latitude">
                    Latitude (optional):
                </label>

                <input
                    type="number"
                    id="latitude"
                    name="latitude"
                    step="0.00000001"
                    placeholder="e.g., 40.7128"
                    value="<?php
                        echo isset($_POST['latitude'])
                            ? htmlspecialchars(
                                $_POST['latitude'],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : '';
                    ?>"
                >

            </div>


            <!-- Longitude -->
            <div class="form-group">

                <label for="longitude">
                    Longitude (optional):
                </label>

                <input
                    type="number"
                    id="longitude"
                    name="longitude"
                    step="0.00000001"
                    placeholder="e.g., -74.0060"
                    value="<?php
                        echo isset($_POST['longitude'])
                            ? htmlspecialchars(
                                $_POST['longitude'],
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            : '';
                    ?>"
                >

            </div>


            <!-- Image -->
            <div class="form-group">

                <label for="image">
                    Upload Image (optional):
                </label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif"
                >

                <small>
                    Max file size: 5MB.
                    Allowed formats: JPEG, PNG, GIF
                </small>

            </div>


            <!-- Anonymous -->
            <div class="form-group">

                <label>

                    <input
                        type="checkbox"
                        name="is_anonymous"
                        value="1"
                        <?php
                        if (isset($_POST['is_anonymous'])) {
                            echo 'checked';
                        }
                        ?>
                    >

                    Submit anonymously

                </label>

            </div>


            <!-- Buttons -->
            <button
                type="submit"
                class="btn"
            >
                Submit Complaint
            </button>

            <a
                href="dashboard.php"
                class="btn"
                style="background: #6c757d;"
            >
                Cancel
            </a>

        </form>

    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>