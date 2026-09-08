<?php

require_once 'config.php';

require_role('admin');


/*
|--------------------------------------------------------------------------
| Only POST requests are allowed
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    die('Method Not Allowed');

}


/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

check_csrf();


/*
|--------------------------------------------------------------------------
| Get Data
|--------------------------------------------------------------------------
*/

$application_id = (int)($_POST['id'] ?? 0);

$status = $_POST['status'] ?? '';


/*
|--------------------------------------------------------------------------
| Validate Application ID
|--------------------------------------------------------------------------
*/

if ($application_id <= 0) {

    flash(
        'Invalid application ID.',
        'error'
    );

    header('Location: admin_applications.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| Validate Status
|--------------------------------------------------------------------------
*/

if (
    !in_array(
        $status,
        ['Approved', 'Rejected'],
        true
    )
) {

    flash(
        'Invalid application status.',
        'error'
    );

    header('Location: admin_applications.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| Check Application
|--------------------------------------------------------------------------
*/

$check = $conn->prepare(
    "SELECT
        a.id,
        a.status,
        u.name AS student_name,
        s.title AS scholarship_title

     FROM applications a

     JOIN users u
        ON a.student_id = u.id

     JOIN scholarships s
        ON a.scholarship_id = s.id

     WHERE a.id = ?

     LIMIT 1"
);

$check->bind_param(
    "i",
    $application_id
);

$check->execute();

$result = $check->get_result();

$application = $result->fetch_assoc();


/*
|--------------------------------------------------------------------------
| Application Not Found
|--------------------------------------------------------------------------
*/

if (!$application) {

    flash(
        'Application not found.',
        'error'
    );

    header('Location: admin_applications.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| Prevent Updating Already Reviewed Application
|--------------------------------------------------------------------------
*/

if ($application['status'] !== 'Pending') {

    flash(
        'This application has already been reviewed.',
        'error'
    );

    header('Location: admin_applications.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| Update Application
|--------------------------------------------------------------------------
*/

$update = $conn->prepare(
    "UPDATE applications

     SET status = ?

     WHERE id = ?

       AND status = 'Pending'"
);

$update->bind_param(
    "si",
    $status,
    $application_id
);

$update->execute();


/*
|--------------------------------------------------------------------------
| Result
|--------------------------------------------------------------------------
*/

if ($update->affected_rows > 0) {

    if ($status === 'Approved') {

        flash(
            'Application approved successfully.',
            'success'
        );

    } else {

        flash(
            'Application rejected successfully.',
            'success'
        );

    }

} else {

    flash(
        'Application could not be updated.',
        'error'
    );

}


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    'Location: admin_applications.php'
);

exit;

?>