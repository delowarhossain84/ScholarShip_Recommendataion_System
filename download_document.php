<?php

require_once 'config.php';

require_login();

/*
|--------------------------------------------------------------------------
| Get document ID
|--------------------------------------------------------------------------
*/

$document_id = (int)($_GET['id'] ?? 0);

if ($document_id <= 0) {
    http_response_code(400);
    die('Invalid document ID.');
}


/*
|--------------------------------------------------------------------------
| Get document + application + scholarship information
|--------------------------------------------------------------------------
*/

$st = $conn->prepare(
    "SELECT
        ad.id,
        ad.file_name,
        ad.file_path,
        ad.document_type,
        a.student_id,
        s.provider_id
     FROM application_documents ad
     JOIN applications a
        ON ad.application_id = a.id
     JOIN scholarships s
        ON a.scholarship_id = s.id
     WHERE ad.id = ?
     LIMIT 1"
);

$st->bind_param("i", $document_id);

$st->execute();

$result = $st->get_result();

$document = $result->fetch_assoc();

if (!$document) {
    http_response_code(404);
    die('Document not found.');
}


/*
|--------------------------------------------------------------------------
| Authorization
|
| Student:
| Can view their own application document.
|
| Provider:
| Can view documents belonging to applications
| submitted to their own scholarship.
|
| Admin:
| Can view any document.
|--------------------------------------------------------------------------
*/

$current_user = user();

$authorized = false;

if ($current_user['role'] === 'admin') {

    $authorized = true;

}

elseif (
    $current_user['role'] === 'student'
    && (int)$document['student_id'] === (int)$current_user['id']
) {

    $authorized = true;

}

elseif (
    $current_user['role'] === 'provider'
    && (int)$document['provider_id'] === (int)$current_user['id']
) {

    $authorized = true;

}


/*
|--------------------------------------------------------------------------
| Reject unauthorized access
|--------------------------------------------------------------------------
*/

if (!$authorized) {

    http_response_code(403);

    die('You do not have permission to view this document.');

}


/*
|--------------------------------------------------------------------------
| Get file path
|--------------------------------------------------------------------------
*/

$file_path = $document['file_path'];


/*
|--------------------------------------------------------------------------
| Security check
|
| Only allow files inside the application document directory.
|--------------------------------------------------------------------------
*/

$base_dir = realpath(
    __DIR__ . DIRECTORY_SEPARATOR . 'uploads' .
    DIRECTORY_SEPARATOR . 'application_documents'
);

$real_file = realpath(__DIR__ . DIRECTORY_SEPARATOR . $file_path);


/*
|--------------------------------------------------------------------------
| Check file exists
|--------------------------------------------------------------------------
*/

if ($real_file === false || !is_file($real_file)) {

    http_response_code(404);

    die('File not found on server.');

}


/*
|--------------------------------------------------------------------------
| Prevent path traversal
|--------------------------------------------------------------------------
*/

if (
    $base_dir === false ||
    strpos($real_file, $base_dir . DIRECTORY_SEPARATOR) !== 0
) {

    http_response_code(403);

    die('Invalid file path.');

}


/*
|--------------------------------------------------------------------------
| Only PDF files are allowed
|--------------------------------------------------------------------------
*/

$finfo = finfo_open(FILEINFO_MIME_TYPE);

$mime_type = finfo_file($finfo, $real_file);

finfo_close($finfo);

if ($mime_type !== 'application/pdf') {

    http_response_code(403);

    die('Only PDF documents are allowed.');

}


/*
|--------------------------------------------------------------------------
| Send PDF to browser
|--------------------------------------------------------------------------
*/

header('Content-Type: application/pdf');

header(
    'Content-Disposition: inline; filename="' .
    basename($document['file_name']) .
    '"'
);

header('Content-Length: ' . filesize($real_file));

header('X-Content-Type-Options: nosniff');

readfile($real_file);

exit;