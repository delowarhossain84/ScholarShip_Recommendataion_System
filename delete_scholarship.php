<?php

require_once 'config.php';

require_role('provider');


// ============================================================
// ONLY POST REQUEST ALLOWED
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    flash(
        'error',
        'Invalid request method.'
    );

    header('Location: provider_scholarships.php');

    exit;
}


// ============================================================
// CSRF CHECK
// ============================================================

try {

    check_csrf();

} catch (Throwable $e) {

    flash(
        'error',
        'Invalid request token. Please refresh and try again.'
    );

    header('Location: provider_scholarships.php');

    exit;
}


// ============================================================
// GET SCHOLARSHIP ID FROM POST
// ============================================================

$id = (int)(
    $_POST['id'] ?? 0
);


// ============================================================
// VALIDATE ID
// ============================================================

if ($id <= 0) {

    flash(
        'error',
        'Invalid scholarship selected.'
    );

    header('Location: provider_scholarships.php');

    exit;
}


// ============================================================
// CURRENT PROVIDER ID
// ============================================================

$uid = (int)user()['id'];


// ============================================================
// CHECK SCHOLARSHIP BELONGS TO THIS PROVIDER
// ============================================================

$st = $conn->prepare("
    SELECT
        id,
        title
    FROM scholarships
    WHERE id = ?
      AND provider_id = ?
    LIMIT 1
");

$st->bind_param(
    "ii",
    $id,
    $uid
);

$st->execute();

$result = $st->get_result();

$scholarship = $result->fetch_assoc();

$st->close();


// ============================================================
// SCHOLARSHIP NOT FOUND
// ============================================================

if (!$scholarship) {

    flash(
        'error',
        'Scholarship not found or you do not have permission to delete it.'
    );

    header('Location: provider_scholarships.php');

    exit;
}


// ============================================================
// DELETE SCHOLARSHIP
// ============================================================

$st = $conn->prepare("
    DELETE FROM scholarships
    WHERE id = ?
      AND provider_id = ?
");

$st->bind_param(
    "ii",
    $id,
    $uid
);


if ($st->execute()) {

    flash(
        'success',
        'Scholarship "' .
        $scholarship['title'] .
        '" deleted successfully.'
    );

} else {

    flash(
        'error',
        'Unable to delete scholarship. Please try again.'
    );
}


$st->close();


// ============================================================
// REDIRECT
// ============================================================

header(
    'Location: provider_scholarships.php'
);

exit;

?>