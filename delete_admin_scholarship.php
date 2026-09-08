<?php

require_once 'config.php';

require_role('admin');


/*
|--------------------------------------------------------------------------
| Only POST requests allowed
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
| Get Scholarship ID
|--------------------------------------------------------------------------
*/

$id = (int)($_POST['id'] ?? 0);


if ($id <= 0) {

    flash(
        'Invalid scholarship ID.',
        'error'
    );

    header('Location: admin_scholarships.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| Check Scholarship
|--------------------------------------------------------------------------
*/

$check = $conn->prepare(
    "SELECT
        id,
        title,
        provider_id
     FROM scholarships
     WHERE id = ?
     LIMIT 1"
);

$check->bind_param(
    "i",
    $id
);

$check->execute();

$result = $check->get_result();

$scholarship = $result->fetch_assoc();


if (!$scholarship) {

    flash(
        'Scholarship not found.',
        'error'
    );

    header('Location: admin_scholarships.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| Delete Scholarship
|--------------------------------------------------------------------------
*/

$delete = $conn->prepare(
    "DELETE FROM scholarships
     WHERE id = ?"
);

$delete->bind_param(
    "i",
    $id
);

$delete->execute();


/*
|--------------------------------------------------------------------------
| Result
|--------------------------------------------------------------------------
*/

if ($delete->affected_rows > 0) {

    flash(
        'Scholarship "' .
        $scholarship['title'] .
        '" deleted successfully.',
        'success'
    );

} else {

    flash(
        'Unable to delete the scholarship.',
        'error'
    );

}


header(
    'Location: admin_scholarships.php'
);

exit;

?>