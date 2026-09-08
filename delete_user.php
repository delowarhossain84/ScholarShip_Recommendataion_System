<?php

require_once 'config.php';

require_role('admin');


// ============================================================
// ONLY POST REQUEST ALLOWED
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: admin_users.php');

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
        'Invalid security token. Please refresh the page and try again.'
    );

    header('Location: admin_users.php');

    exit;
}


// ============================================================
// GET USER ID
// ============================================================

$user_id = (int)(
    $_POST['user_id'] ?? 0
);


// ============================================================
// VALIDATE USER ID
// ============================================================

if ($user_id <= 0) {

    flash(
        'error',
        'Invalid user selected.'
    );

    header('Location: admin_users.php');

    exit;
}


// ============================================================
// CURRENT ADMIN ID
// ============================================================

$current_user = user();

$current_admin_id = (int)$current_user['id'];


// ============================================================
// PREVENT ADMIN FROM DELETING THEMSELVES
// ============================================================

if ($user_id === $current_admin_id) {

    flash(
        'error',
        'You cannot delete your own administrator account.'
    );

    header('Location: admin_users.php');

    exit;
}


// ============================================================
// FIND USER
// ============================================================

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        email,
        role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

$target_user = $result->fetch_assoc();

$stmt->close();


// ============================================================
// USER NOT FOUND
// ============================================================

if (!$target_user) {

    flash(
        'error',
        'User not found.'
    );

    header('Location: admin_users.php');

    exit;
}


// ============================================================
// EXTRA SAFETY
// PREVENT DELETING ANOTHER ADMIN
// ============================================================

if ($target_user['role'] === 'admin') {

    flash(
        'error',
        'Administrator accounts cannot be deleted from this page.'
    );

    header('Location: admin_users.php');

    exit;
}


// ============================================================
// DELETE USER
// ============================================================

$stmt = $conn->prepare("
    DELETE FROM users
    WHERE id = ?
");

$stmt->bind_param(
    "i",
    $user_id
);


if ($stmt->execute()) {

    flash(
        'success',
        $target_user['name'] .
        ' (' .
        $target_user['role'] .
        ') has been deleted successfully.'
    );

} else {

    flash(
        'error',
        'Unable to delete the selected user.'
    );
}


$stmt->close();


// ============================================================
// RETURN TO ADMIN USERS
// ============================================================

header('Location: admin_users.php');

exit;