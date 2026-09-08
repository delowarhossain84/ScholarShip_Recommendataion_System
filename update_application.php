<?php

require_once 'config.php';

require_role('provider');


// Only POST requests are allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    die('Method Not Allowed');
}


// Check CSRF token
check_csrf();


// Get application ID
$id = (int)($_POST['id'] ?? 0);


// Get requested status
$status = $_POST['status'] ?? '';


// Get current provider ID
$uid = (int)user()['id'];


// Validate application ID
if ($id <= 0) {

    die('Invalid application ID');
}


// Validate status
if (!in_array($status, ['Approved', 'Rejected'], true)) {

    die('Invalid status');
}


/*
|--------------------------------------------------------------------------
| UPDATE APPLICATION
|--------------------------------------------------------------------------
*/

$st = $conn->prepare(
    "UPDATE applications a

     JOIN scholarships s
        ON a.scholarship_id = s.id

     SET a.status = ?

     WHERE a.id = ?

       AND s.provider_id = ?

       AND a.status = 'Pending'"
);

$st->bind_param(
    "sii",
    $status,
    $id,
    $uid
);

$st->execute();


/*
|--------------------------------------------------------------------------
| CHECK WHETHER APPLICATION WAS UPDATED
|--------------------------------------------------------------------------
*/

if ($st->affected_rows === 0) {

    flash(
        'Application could not be updated. It may already have been reviewed or you may not have permission.',
        'error'
    );

    header(
        'Location: provider_applications.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET STUDENT ID AND SCHOLARSHIP TITLE
|--------------------------------------------------------------------------
*/

$get = $conn->prepare(
    "SELECT
        a.student_id,
        s.title AS scholarship_title

     FROM applications a

     JOIN scholarships s
        ON a.scholarship_id = s.id

     WHERE a.id = ?

     LIMIT 1"
);

$get->bind_param(
    "i",
    $id
);

$get->execute();

$result = $get->get_result();

$application = $result->fetch_assoc();


/*
|--------------------------------------------------------------------------
| CREATE NOTIFICATION
|--------------------------------------------------------------------------
*/

if ($application) {

    $student_id = (int)$application['student_id'];

    $scholarship_title =
        $application['scholarship_title'];


    if ($status === 'Approved') {

        $notification_title =
            'Application Approved';

        $notification_message =
            'Your application for "' .
            $scholarship_title .
            '" has been approved.';

    } else {

        $notification_title =
            'Application Rejected';

        $notification_message =
            'Your application for "' .
            $scholarship_title .
            '" has been rejected.';
    }


    $notify = $conn->prepare(
        "INSERT INTO notifications
        (
            user_id,
            application_id,
            title,
            message,
            type
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'application'
        )"
    );


    $notify->bind_param(
        "iiss",
        $student_id,
        $id,
        $notification_title,
        $notification_message
    );


    $notify->execute();
}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

header(
    'Location: provider_applications.php'
);

exit;

?>