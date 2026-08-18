<?php

require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';


/*
|--------------------------------------------------------------------------
| Check Citizen Role
|--------------------------------------------------------------------------
*/

checkRole(['Citizen']);


/*
|--------------------------------------------------------------------------
| Logged-in User
|--------------------------------------------------------------------------
*/

$user_id = (int) $_SESSION['user_id'];

$error = '';
$success = '';

$image_url = null;


/*
|--------------------------------------------------------------------------
| Fetch Categories
|--------------------------------------------------------------------------
*/

$categories = [];

$sql = "SELECT id, name FROM categories ORDER BY name ASC";

$result = $conn->query($sql);

if ($result) {

    $categories = $result->fetch_all(MYSQLI_ASSOC);

} else {

    $error = "Unable to load complaint categories.";

}


/*
|--------------------------------------------------------------------------
| Fetch Priority Levels
|--------------------------------------------------------------------------
*/

$priorities = [];

$sql = "SELECT id, name FROM priority_levels ORDER BY id ASC";

$result = $conn->query($sql);

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


    /*
    |--------------------------------------------------------------------------
    | Get Form Data
    |--------------------------------------------------------------------------
    */

    $title = isset($_POST['title'])
        ? trim($_POST['title'])
        : '';

    $description = isset($_POST['description'])
        ? trim($_POST['description'])
        : '';

    $category_id = isset($_POST['category_id'])
        ? (int) $_POST['category_id']
        : 0;

    $priority_id = isset($_POST['priority_id'])
        ? (int) $_POST['priority_id']
        : 0;

    $location = isset($_POST['location'])
        ? trim($_POST['location'])
        : '';


    /*
    |--------------------------------------------------------------------------
    | Latitude
    |--------------------------------------------------------------------------
    */

    $latitude = null;

    if (
        isset($_POST['latitude']) &&
        $_POST['latitude'] !== ''
    ) {

        $latitude = (float) $_POST['latitude'];

    }


    /*
    |--------------------------------------------------------------------------
    | Longitude
    |--------------------------------------------------------------------------
    */

    $longitude = null;

    if (
        isset($_POST['longitude']) &&
        $_POST['longitude'] !== ''
    ) {

        $longitude = (float) $_POST['longitude'];

    }


    /*
    |--------------------------------------------------------------------------
    | Anonymous Submission
    |--------------------------------------------------------------------------
    */

    $is_anonymous = isset($_POST['is_anonymous'])
        ? 1
        : 0;


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($title === '') {

        $error = "Please enter the issue title.";

    } elseif ($description === '') {

        $error = "Please enter the complaint description.";

    } elseif ($category_id <= 0) {

        $error = "Please select a complaint category.";

    } elseif ($priority_id <= 0) {

        $error = "Please select a priority level.";

    } elseif ($location === '') {

        $error = "Please enter the complaint location.";

    } elseif (strlen($title) > 255) {

        $error = "Issue title is too long.";

    }


    /*
    |--------------------------------------------------------------------------
    | Validate Latitude
    |--------------------------------------------------------------------------
    */

    if (
        $error === '' &&
        $latitude !== null &&
        (
            $latitude < -90 ||
            $latitude > 90
        )
    ) {

        $error = "Invalid latitude value.";

    }


    /*
    |--------------------------------------------------------------------------
    | Validate Longitude
    |--------------------------------------------------------------------------
    */

    if (
        $error === '' &&
        $longitude !== null &&
        (
            $longitude < -180 ||
            $longitude > 180
        )
    ) {

        $error = "Invalid longitude value.";

    }


    /*
    |--------------------------------------------------------------------------
    | Check Category Exists
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $checkCategory = $conn->prepare(
            "SELECT id FROM categories WHERE id = ? LIMIT 1"
        );

        if ($checkCategory) {

            $checkCategory->bind_param(
                "i",
                $category_id
            );

            $checkCategory->execute();

            $categoryResult =
                $checkCategory->get_result();

            if (
                !$categoryResult ||
                $categoryResult->num_rows === 0
            ) {

                $error =
                    "Selected category does not exist.";

            }

            $checkCategory->close();

        } else {

            $error =
                "Unable to validate category.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Check Priority Exists
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $checkPriority = $conn->prepare(
            "SELECT id FROM priority_levels WHERE id = ? LIMIT 1"
        );

        if ($checkPriority) {

            $checkPriority->bind_param(
                "i",
                $priority_id
            );

            $checkPriority->execute();

            $priorityResult =
                $checkPriority->get_result();

            if (
                !$priorityResult ||
                $priorityResult->num_rows === 0
            ) {

                $error =
                    "Selected priority level does not exist.";

            }

            $checkPriority->close();

        } else {

            $error =
                "Unable to validate priority level.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    |
    | Make sure complaint_status table has:
    |
    | id = 1
    | name = Received
    |
    */

    $status_id = 1;


    /*
    |--------------------------------------------------------------------------
    | Check Status Exists
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $checkStatus = $conn->prepare(
            "SELECT id FROM complaint_status WHERE id = ? LIMIT 1"
        );

        if ($checkStatus) {

            $checkStatus->bind_param(
                "i",
                $status_id
            );

            $checkStatus->execute();

            $statusResult =
                $checkStatus->get_result();

            if (
                !$statusResult ||
                $statusResult->num_rows === 0
            ) {

                $error =
                    "Complaint status ID 1 does not exist. Please add 'Received' status.";

            }

            $checkStatus->close();

        } else {

            $error =
                "Unable to validate complaint status.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Generate Reference Number
    |--------------------------------------------------------------------------
    */

    $reference_number = '';

    if ($error === '') {

        $reference_number =
            generateReferenceNumber();


        /*
        |--------------------------------------------------------------------------
        | Make Sure Reference Number Is Unique
        |--------------------------------------------------------------------------
        */

        $checkReference = $conn->prepare(
            "SELECT id
             FROM complaints
             WHERE reference_number = ?
             LIMIT 1"
        );

        if ($checkReference) {

            $checkReference->bind_param(
                "s",
                $reference_number
            );

            $checkReference->execute();

            $referenceResult =
                $checkReference->get_result();


            /*
            |--------------------------------------------------------------------------
            | Generate Again If Duplicate
            |--------------------------------------------------------------------------
            */

            while (
                $referenceResult &&
                $referenceResult->num_rows > 0
            ) {

                $reference_number =
                    generateReferenceNumber();

                $checkReference->bind_param(
                    "s",
                    $reference_number
                );

                $checkReference->execute();

                $referenceResult =
                    $checkReference->get_result();

            }

            $checkReference->close();

        } else {

            $error =
                "Unable to generate complaint reference number.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Image Upload
    |--------------------------------------------------------------------------
    */

    if (
        $error === '' &&
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['image'];


        /*
        |--------------------------------------------------------------------------
        | Upload Error
        |--------------------------------------------------------------------------
        */

        if (
            $file['error'] !== UPLOAD_ERR_OK
        ) {

            $error =
                "There was an error uploading the image.";

        }


        /*
        |--------------------------------------------------------------------------
        | File Size
        |--------------------------------------------------------------------------
        */

        elseif (
            $file['size'] > 5 * 1024 * 1024
        ) {

            $error =
                "Image size must not exceed 5MB.";

        }


        /*
        |--------------------------------------------------------------------------
        | Validate MIME Type
        |--------------------------------------------------------------------------
        */

        else {

            $finfo =
                finfo_open(FILEINFO_MIME_TYPE);


            if ($finfo) {

                $mime_type =
                    finfo_file(
                        $finfo,
                        $file['tmp_name']
                    );

                finfo_close($finfo);

            } else {

                $mime_type = '';

            }


            /*
            |--------------------------------------------------------------------------
            | Allowed Images
            |--------------------------------------------------------------------------
            */

            $allowed_types = [

                'image/jpeg' => 'jpg',

                'image/png' => 'png',

                'image/gif' => 'gif'

            ];


            if (
                !isset(
                    $allowed_types[$mime_type]
                )
            ) {

                $error =
                    "Only JPEG, PNG, and GIF images are allowed.";

            } else {


                /*
                |--------------------------------------------------------------------------
                | Upload Directory
                |--------------------------------------------------------------------------
                */

                $upload_dir =
                    '../assets/images/';


                /*
                |--------------------------------------------------------------------------
                | Create Directory
                |--------------------------------------------------------------------------
                */

                if (
                    !is_dir($upload_dir)
                ) {

                    if (
                        !mkdir(
                            $upload_dir,
                            0755,
                            true
                        )
                    ) {

                        $error =
                            "Failed to create image upload directory.";

                    }

                }


                /*
                |--------------------------------------------------------------------------
                | Save Image
                |--------------------------------------------------------------------------
                */

                if ($error === '') {

                    $extension =
                        $allowed_types[$mime_type];


                    $filename =
                        uniqid(
                            'complaint_',
                            true
                        ) .
                        '.' .
                        $extension;


                    $filepath =
                        $upload_dir .
                        $filename;


                    if (
                        move_uploaded_file(
                            $file['tmp_name'],
                            $filepath
                        )
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | Database Path
                        |--------------------------------------------------------------------------
                        */

                        $image_url =
                            'assets/images/' .
                            $filename;

                    } else {

                        $error =
                            "Failed to upload image.";

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

    if ($error === '') {


        /*
        |--------------------------------------------------------------------------
        | SQL Query
        |--------------------------------------------------------------------------
        */

        $sql = "
            INSERT INTO complaints
            (
                user_id,
                title,
                description,
                category_id,
                priority_id,
                location,
                latitude,
                longitude,
                image_url,
                status_id,
                reference_number,
                is_anonymous,
                created_at,
                updated_at
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
                ?,
                ?,
                ?,
                ?,
                NOW(),
                NOW()
            )
        ";


        /*
        |--------------------------------------------------------------------------
        | Prepare Statement
        |--------------------------------------------------------------------------
        */

        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {


            /*
            |--------------------------------------------------------------------------
            | Delete Uploaded Image
            |--------------------------------------------------------------------------
            */

            if ($image_url !== null) {

                $uploadedFile =
                    '../' .
                    $image_url;


                if (
                    file_exists(
                        $uploadedFile
                    )
                ) {

                    unlink(
                        $uploadedFile
                    );

                }

            }


            $error =
                "Database error while preparing complaint: " .
                $conn->error;


        } else {


            /*
            |--------------------------------------------------------------------------
            | Bind Parameters
            |--------------------------------------------------------------------------
            |
            | 1  = user_id          -> i
            | 2  = title            -> s
            | 3  = description      -> s
            | 4  = category_id      -> i
            | 5  = priority_id     -> i
            | 6  = location         -> s
            | 7  = latitude         -> d
            | 8  = longitude        -> d
            | 9  = image_url        -> s
            | 10 = status_id       -> i
            | 11 = reference       -> s
            | 12 = is_anonymous    -> i
            |
            | Total parameters = 12
            |
            */

            $stmt->bind_param(
                "issiisddsisi",
                $user_id,
                $title,
                $description,
                $category_id,
                $priority_id,
                $location,
                $latitude,
                $longitude,
                $image_url,
                $status_id,
                $reference_number,
                $is_anonymous
            );


            /*
            |--------------------------------------------------------------------------
            | Execute
            |--------------------------------------------------------------------------
            */

            if ($stmt->execute()) {


                /*
                |--------------------------------------------------------------------------
                | Get Complaint ID
                |--------------------------------------------------------------------------
                */

                $complaint_id =
                    $conn->insert_id;


                /*
                |--------------------------------------------------------------------------
                | Audit Log
                |--------------------------------------------------------------------------
                */

                if (
                    function_exists('logAudit')
                ) {

                    logAudit(
                        $conn,
                        $user_id,
                        'Complaint Submitted',
                        'complaint',
                        $complaint_id,
                        "Reference: " .
                        $reference_number
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Notification
                |--------------------------------------------------------------------------
                */

                if (
                    function_exists('addNotification')
                ) {

                    addNotification(
                        $conn,
                        $user_id,
                        $complaint_id,
                        "Your complaint has been received. Reference: " .
                        $reference_number
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Success Message
                |--------------------------------------------------------------------------
                */

                $success =
                    "Complaint submitted successfully! " .
                    "Your reference number is: <strong>" .
                    htmlspecialchars(
                        $reference_number,
                        ENT_QUOTES,
                        'UTF-8'
                    ) .
                    "</strong>";


                /*
                |--------------------------------------------------------------------------
                | Clear Form
                |--------------------------------------------------------------------------
                */

                $_POST = [];

                $image_url = null;


            } else {


                /*
                |--------------------------------------------------------------------------
                | Database Insert Error
                |--------------------------------------------------------------------------
                */

                $error =
                    "Unable to submit complaint. " .
                    "MySQL Error (" .
                    $stmt->errno .
                    "): " .
                    $stmt->error;


                /*
                |--------------------------------------------------------------------------
                | Delete Uploaded Image
                |--------------------------------------------------------------------------
                */

                if ($image_url !== null) {

                    $uploadedFile =
                        '../' .
                        $image_url;


                    if (
                        file_exists(
                            $uploadedFile
                        )
                    ) {

                        unlink(
                            $uploadedFile
                        );

                    }

                }

            }


            $stmt->close();

        }

    }

}


include '../includes/header.php';

?>


<div
    class="form-container"
    style="max-width:700px;"
>


    <h2>
        Submit a Complaint
    </h2>


    <p style="color:#666;">
        Report a civic issue in your area.
    </p>


    <!-- =========================================================
         ERROR MESSAGE
    ========================================================== -->

    <?php if (!empty($error)): ?>

        <div
            style="
                background:#f8d7da;
                color:#721c24;
                padding:12px;
                border-radius:5px;
                margin-bottom:15px;
                border:1px solid #f5c6cb;
            "
        >

            <strong>
                Error:
            </strong>

            <?php
            echo htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         SUCCESS MESSAGE
    ========================================================== -->

    <?php if (!empty($success)): ?>

        <div
            style="
                background:#d4edda;
                color:#155724;
                padding:15px;
                border-radius:5px;
                margin-bottom:15px;
                border:1px solid #c3e6cb;
            "
        >

            <?php echo $success; ?>

            <br><br>

            <span>
                Please keep your reference number
                for complaint tracking.
            </span>

        </div>


        <p>

            <a
                href="dashboard.php"
                class="btn"
            >
                Back to Dashboard
            </a>

        </p>


    <?php else: ?>


        <form
            method="POST"
            enctype="multipart/form-data"
            id="complaintForm"
        >


            <!-- =========================================================
                 ISSUE TITLE
            ========================================================== -->

            <div class="form-group">

                <label for="title">

                    Issue Title

                    <span style="color:red;">
                        *
                    </span>

                </label>


                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="255"
                    placeholder="e.g. Damaged Road near Main Street"
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


            <!-- =========================================================
                 DESCRIPTION
            ========================================================== -->

            <div class="form-group">

                <label for="description">

                    Description

                    <span style="color:red;">
                        *
                    </span>

                </label>


                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    placeholder="Describe the civic issue..."
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


            <!-- =========================================================
                 CATEGORY
            ========================================================== -->

            <div class="form-group">

                <label for="category_id">

                    Category

                    <span style="color:red;">
                        *
                    </span>

                </label>


                <select
                    id="category_id"
                    name="category_id"
                    required
                >

                    <option value="">
                        Select a category
                    </option>


                    <?php foreach (
                        $categories
                        as $cat
                    ): ?>

                        <option
                            value="<?php
                                echo (int)$cat['id'];
                            ?>"
                            <?php

                            if (
                                isset(
                                    $_POST['category_id']
                                ) &&
                                (int)$_POST['category_id']
                                ===
                                (int)$cat['id']
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


            <!-- =========================================================
                 PRIORITY
            ========================================================== -->

            <div class="form-group">

                <label for="priority_id">

                    Priority Level

                    <span style="color:red;">
                        *
                    </span>

                </label>


                <select
                    id="priority_id"
                    name="priority_id"
                    required
                >

                    <option value="">
                        Select priority
                    </option>


                    <?php foreach (
                        $priorities
                        as $priority
                    ): ?>

                        <option
                            value="<?php
                                echo (int)$priority['id'];
                            ?>"
                            <?php

                            if (
                                isset(
                                    $_POST['priority_id']
                                ) &&
                                (int)$_POST['priority_id']
                                ===
                                (int)$priority['id']
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


            <!-- =========================================================
                 LOCATION
            ========================================================== -->

            <div
                style="
                    border:1px solid #ddd;
                    padding:18px;
                    border-radius:8px;
                    margin-bottom:20px;
                    background:#f8f9fa;
                "
            >

                <h3 style="margin-top:0;">
                    Complaint Location
                </h3>


                <!-- Address -->

                <div class="form-group">

                    <label for="location">

                        Location / Address

                        <span style="color:red;">
                            *
                        </span>

                    </label>


                    <input
                        type="text"
                        id="location"
                        name="location"
                        maxlength="255"
                        placeholder="e.g. Main Street, near City Hall"
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


                <!-- GPS Button -->

                <button
                    type="button"
                    id="getLocationBtn"
                    class="btn"
                    onclick="getCurrentLocation()"
                    style="
                        margin-bottom:15px;
                        background:#1f3c88;
                        color:white;
                    "
                >

                    📍 Use My Current Location

                </button>


                <!-- Location Message -->

                <div
                    id="locationMessage"
                    style="
                        margin-bottom:15px;
                        font-size:14px;
                    "
                ></div>


                <!-- Coordinates -->

                <div
                    style="
                        display:grid;
                        grid-template-columns:1fr 1fr;
                        gap:15px;
                    "
                >


                    <!-- Latitude -->

                    <div class="form-group">

                        <label for="latitude">
                            Latitude
                        </label>


                        <input
                            type="number"
                            id="latitude"
                            name="latitude"
                            step="0.00000001"
                            placeholder="Automatically detected"
                            value="<?php

                                echo isset(
                                    $_POST['latitude']
                                )
                                    ? htmlspecialchars(
                                        $_POST['latitude'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                    : '';

                            ?>"
                            readonly
                        >

                    </div>


                    <!-- Longitude -->

                    <div class="form-group">

                        <label for="longitude">
                            Longitude
                        </label>


                        <input
                            type="number"
                            id="longitude"
                            name="longitude"
                            step="0.00000001"
                            placeholder="Automatically detected"
                            value="<?php

                                echo isset(
                                    $_POST['longitude']
                                )
                                    ? htmlspecialchars(
                                        $_POST['longitude'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                    : '';

                            ?>"
                            readonly
                        >

                    </div>

                </div>


                <small style="color:#666;">

                    Click
                    <strong>
                        "Use My Current Location"
                    </strong>
                    and allow your browser to access
                    your location.

                </small>

            </div>


            <!-- =========================================================
                 IMAGE
            ========================================================== -->

            <div class="form-group">

                <label for="image">

                    Upload Image (optional)

                </label>


                <input
                    type="file"
                    id="image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif"
                >


                <small>

                    Maximum size: 5MB.
                    Allowed formats:
                    JPEG, PNG, GIF.

                </small>

            </div>


            <!-- =========================================================
                 ANONYMOUS
            ========================================================== -->

            <div class="form-group">

                <label>

                    <input
                        type="checkbox"
                        name="is_anonymous"
                        value="1"
                        <?php

                        if (
                            isset(
                                $_POST['is_anonymous']
                            )
                        ) {

                            echo 'checked';

                        }

                        ?>
                    >

                    Submit anonymously

                </label>


                <small
                    style="
                        display:block;
                        color:#666;
                        margin-top:5px;
                    "
                >

                    Your identity will not be displayed
                    to other users when this complaint
                    is viewed.

                </small>

            </div>


            <!-- =========================================================
                 BUTTONS
            ========================================================== -->

            <button
                type="submit"
                class="btn"
                id="submitButton"
            >

                Submit Complaint

            </button>


            <a
                href="dashboard.php"
                class="btn"
                style="
                    background:#6c757d;
                "
            >

                Cancel

            </a>


        </form>


    <?php endif; ?>


</div>


<script>

/*
|--------------------------------------------------------------------------
| Get Current Location
|--------------------------------------------------------------------------
*/

function getCurrentLocation() {

    const button =
        document.getElementById(
            'getLocationBtn'
        );

    const message =
        document.getElementById(
            'locationMessage'
        );

    const latitude =
        document.getElementById(
            'latitude'
        );

    const longitude =
        document.getElementById(
            'longitude'
        );


    /*
    |--------------------------------------------------------------------------
    | Browser Support
    |--------------------------------------------------------------------------
    */

    if (!navigator.geolocation) {

        message.innerHTML =
            '❌ Geolocation is not supported by your browser.';

        message.style.color =
            'red';

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | Loading
    |--------------------------------------------------------------------------
    */

    button.disabled = true;

    button.innerHTML =
        '📍 Detecting Location...';

    message.innerHTML =
        'Please allow location access when your browser asks.';

    message.style.color =
        '#856404';


    /*
    |--------------------------------------------------------------------------
    | Get Current Position
    |--------------------------------------------------------------------------
    */

    navigator.geolocation.getCurrentPosition(

        function(position) {


            /*
            |--------------------------------------------------------------------------
            | Coordinates
            |--------------------------------------------------------------------------
            */

            const lat =
                position.coords.latitude;

            const lng =
                position.coords.longitude;


            /*
            |--------------------------------------------------------------------------
            | Put Coordinates Into Form
            |--------------------------------------------------------------------------
            */

            latitude.value =
                lat.toFixed(8);

            longitude.value =
                lng.toFixed(8);


            /*
            |--------------------------------------------------------------------------
            | Success Message
            |--------------------------------------------------------------------------
            */

            message.innerHTML =
                '✓ Current location detected successfully.';

            message.style.color =
                'green';


            /*
            |--------------------------------------------------------------------------
            | Button
            |--------------------------------------------------------------------------
            */

            button.disabled =
                false;

            button.innerHTML =
                '✓ Location Detected';

        },


        function(error) {


            /*
            |--------------------------------------------------------------------------
            | Reset Button
            |--------------------------------------------------------------------------
            */

            button.disabled =
                false;

            button.innerHTML =
                '📍 Use My Current Location';


            /*
            |--------------------------------------------------------------------------
            | Error Handling
            |--------------------------------------------------------------------------
            */

            switch (error.code) {


                case error.PERMISSION_DENIED:

                    message.innerHTML =
                        '❌ Location permission was denied. ' +
                        'Please allow location access and try again.';

                    break;


                case error.POSITION_UNAVAILABLE:

                    message.innerHTML =
                        '❌ Location information is unavailable.';

                    break;


                case error.TIMEOUT:

                    message.innerHTML =
                        '❌ Location request timed out. Please try again.';

                    break;


                default:

                    message.innerHTML =
                        '❌ Unable to detect your location.';

                    break;

            }


            message.style.color =
                'red';

        },


        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }

    );

}


/*
|--------------------------------------------------------------------------
| Form Submit Loading
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function() {

        const form =
            document.getElementById(
                'complaintForm'
            );

        const submitButton =
            document.getElementById(
                'submitButton'
            );


        if (
            form &&
            submitButton
        ) {

            form.addEventListener(
                'submit',
                function() {

                    submitButton.disabled =
                        true;

                    submitButton.innerHTML =
                        'Submitting Complaint...';

                }
            );

        }

    }
);

</script>


<?php

include '../includes/footer.php';

?>