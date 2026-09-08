<?php

require_once 'config.php';

require_role('student');

$id = (int)($_GET['id'] ?? 0);

$u = user();

$studentId = (int)$u['id'];

$error = '';

/* =========================================================
   GET SCHOLARSHIP
   ========================================================= */

$st = $conn->prepare(
    "SELECT * FROM scholarships WHERE id=? LIMIT 1"
);

$st->bind_param("i", $id);

$st->execute();

$s = $st->get_result()->fetch_assoc();

$st->close();

if (!$s) {

    die("Scholarship not found.");
}


/* =========================================================
   GET CURRENT STUDENT PROFILE
   ========================================================= */

$profileStmt = $conn->prepare("
    SELECT
        name,
        email,
        education_level,
        field_of_study,
        cgpa,
        income,
        country,
        ielts_score,
        gre_score,
        extracurricular
    FROM users
    WHERE id=?
    LIMIT 1
");

$profileStmt->bind_param(
    "i",
    $studentId
);

$profileStmt->execute();

$student = $profileStmt
    ->get_result()
    ->fetch_assoc();

$profileStmt->close();


/* =========================================================
   PROFILE VALUES
   ========================================================= */

$studentName =
    $student['name'] ?? '';

$studentEmail =
    $student['email'] ?? '';

$userCgpa =
    (float)($student['cgpa'] ?? 0);

$userEducation =
    trim($student['education_level'] ?? '');

$userField =
    trim($student['field_of_study'] ?? '');

$userCountry =
    trim($student['country'] ?? '');

$profileIelts =
    $student['ielts_score'] ?? '';

$profileGre =
    $student['gre_score'] ?? '';

$profileExtracurricular =
    $student['extracurricular'] ?? '';


/* =========================================================
   APPLICATION FORM VALUES
   ========================================================= */

$ieltsScore =
    $profileIelts !== null
        ? $profileIelts
        : '';

$greScore =
    $profileGre !== null
        ? $profileGre
        : '';

$extracurricular =
    $profileExtracurricular;

$personalStatement = '';


/* =========================================================
   ELIGIBILITY CHECK
   ========================================================= */

$eligible = true;

$matched = [];

$failed = [];


/* ---------------------------------------------------------
   Deadline check
   --------------------------------------------------------- */

if (
    strtotime($s['deadline']) <
    strtotime(date('Y-m-d'))
) {

    $eligible = false;

    $failed[] =
        "Application deadline has passed.";

} else {

    $matched[] =
        "Application deadline is still open.";
}


/* ---------------------------------------------------------
   CGPA check
   --------------------------------------------------------- */

$minCgpa =
    (float)$s['min_cgpa'];

if ($userCgpa >= $minCgpa) {

    $matched[] =
        "Your CGPA meets the minimum requirement.";

} else {

    $eligible = false;

    $failed[] =
        "Your CGPA is below the required minimum of "
        . $minCgpa . ".";
}


/* ---------------------------------------------------------
   Education level check
   --------------------------------------------------------- */

$requiredEducation =
    trim($s['education_level']);

if (
    $requiredEducation === 'Any' ||
    strcasecmp(
        $requiredEducation,
        $userEducation
    ) === 0
) {

    $matched[] =
        "Your education level matches.";

} else {

    $eligible = false;

    $failed[] =
        "Required education level: "
        . $requiredEducation . ".";
}


/* ---------------------------------------------------------
   Field of study check
   --------------------------------------------------------- */

$requiredField =
    trim($s['field_of_study']);

if (
    $requiredField === 'Any' ||
    $userField === '' ||
    stripos(
        $requiredField,
        $userField
    ) !== false ||
    stripos(
        $userField,
        $requiredField
    ) !== false
) {

    $matched[] =
        "Your field of study matches.";

} else {

    $eligible = false;

    $failed[] =
        "Required field of study: "
        . $requiredField . ".";
}


/* ---------------------------------------------------------
   Country check
   --------------------------------------------------------- */

$requiredCountry =
    trim($s['country']);

if (
    $requiredCountry === 'Any' ||
    strcasecmp(
        $requiredCountry,
        $userCountry
    ) === 0
) {

    $matched[] =
        "Your country matches.";

} else {

    $eligible = false;

    $failed[] =
        "This scholarship is available for: "
        . $requiredCountry . ".";
}


/* =========================================================
   CHECK EXISTING APPLICATION
   ========================================================= */

$dup = $conn->prepare("
    SELECT
        id,
        status,
        applied_at
    FROM applications
    WHERE scholarship_id=?
    AND student_id=?
    LIMIT 1
");

$dup->bind_param(
    "ii",
    $id,
    $studentId
);

$dup->execute();

$existing =
    $dup->get_result()->fetch_assoc();

$dup->close();


/* =========================================================
   LOAD PROFILE DOCUMENTS
   ========================================================= */

$profileDocuments = [];

$docStmt = $conn->prepare("
    SELECT
        id,
        document_type,
        file_name,
        file_path,
        uploaded_at
    FROM student_documents
    WHERE student_id=?
    ORDER BY document_type
");

$docStmt->bind_param(
    "i",
    $studentId
);

$docStmt->execute();

$docResult =
    $docStmt->get_result();

while (
    $doc = $docResult->fetch_assoc()
) {

    $profileDocuments[
        $doc['document_type']
    ] = $doc;
}

$docStmt->close();


/* =========================================================
   SUBMIT APPLICATION
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    check_csrf();


    /* ---------------------------------------------------------
       Get application form values
       --------------------------------------------------------- */

    $ieltsScore =
        trim($_POST['ielts_score'] ?? '');

    $greScore =
        trim($_POST['gre_score'] ?? '');

    $extracurricular =
        trim(
            $_POST['extracurricular'] ?? ''
        );

    $personalStatement =
        trim(
            $_POST['personal_statement'] ?? ''
        );


    /* ---------------------------------------------------------
       IELTS validation
       --------------------------------------------------------- */

    if (
        $ieltsScore !== '' &&
        (
            !is_numeric($ieltsScore) ||
            (float)$ieltsScore < 0 ||
            (float)$ieltsScore > 9
        )
    ) {

        $error =
            "IELTS score must be between 0 and 9.";
    }


    /* ---------------------------------------------------------
       GRE validation
       --------------------------------------------------------- */

    if (
        $error === '' &&
        $greScore !== '' &&
        (
            !is_numeric($greScore) ||
            (float)$greScore < 0 ||
            (float)$greScore > 340
        )
    ) {

        $error =
            "GRE score must be between 0 and 340.";
    }


    /* ---------------------------------------------------------
       Personal statement validation
       --------------------------------------------------------- */

    if (
        $error === '' &&
        $personalStatement === ''
    ) {

        $error =
            "Please write your personal statement.";
    }


    /* ---------------------------------------------------------
       Already applied
       --------------------------------------------------------- */

    if (
        $error === '' &&
        $existing
    ) {

        $error =
            "You have already applied for this scholarship.";
    }


    /* ---------------------------------------------------------
       Eligibility
       --------------------------------------------------------- */

    elseif (
        $error === '' &&
        !$eligible
    ) {

        $error =
            "You are not eligible for this scholarship.";
    }


    /* =========================================================
       VALIDATE UPLOADED FILES BEFORE INSERTING
       ========================================================= */

    if (
        $error === '' &&
        isset($_FILES)
    ) {

        $uploadFields = [

            'academic_certificate' =>
                'Academic Certificate',

            'ielts_certificate' =>
                'IELTS Certificate',

            'gre_certificate' =>
                'GRE Certificate',

            'cv_resume' =>
                'CV / Resume'
        ];


        foreach (
            $uploadFields
            as $fieldName => $documentType
        ) {

            /*
            | No file selected.
            */
            if (
                !isset(
                    $_FILES[$fieldName]
                ) ||
                $_FILES[$fieldName]['error'] ===
                UPLOAD_ERR_NO_FILE
            ) {

                continue;
            }


            /*
            | Upload error.
            */
            if (
                $_FILES[$fieldName]['error'] !==
                UPLOAD_ERR_OK
            ) {

                $error =
                    $documentType .
                    " upload failed.";

                break;
            }


            /*
            | Maximum 5 MB.
            */
            if (
                $_FILES[$fieldName]['size'] >
                5 * 1024 * 1024
            ) {

                $error =
                    $documentType .
                    " must be 5 MB or smaller.";

                break;
            }


            /*
            | Check actual MIME type.
            */
            $finfo =
                finfo_open(
                    FILEINFO_MIME_TYPE
                );

            $mimeType =
                finfo_file(
                    $finfo,
                    $_FILES[$fieldName]['tmp_name']
                );

            finfo_close($finfo);


            /*
            | PDF only.
            */
            if (
                $mimeType !==
                'application/pdf'
            ) {

                $error =
                    $documentType .
                    " must be a PDF file.";

                break;
            }
        }
    }


    /* =========================================================
       INSERT APPLICATION
       ========================================================= */

    if ($error === '') {

        $ieltsValue =
            $ieltsScore === ''
                ? null
                : (float)$ieltsScore;

        $greValue =
            $greScore === ''
                ? null
                : (float)$greScore;


        $x = $conn->prepare("
            INSERT INTO applications
            (
                scholarship_id,
                student_id,
                ielts_score,
                gre_score,
                extracurricular,
                personal_statement,
                status
            )
            VALUES
            (?, ?, ?, ?, ?, ?, 'Pending')
        ");


        $x->bind_param(
            "iiddss",
            $id,
            $studentId,
            $ieltsValue,
            $greValue,
            $extracurricular,
            $personalStatement
        );


        if ($x->execute()) {

            /*
            |--------------------------------------------------------------------------
            | Get newly created application ID
            |--------------------------------------------------------------------------
            */

            $applicationId =
                $conn->insert_id;


            /* =================================================
               APPLICATION DOCUMENTS
               ================================================= */

            $applicationUploadDir =
                __DIR__ .
                '/uploads/application_documents/';


            if (
                !is_dir(
                    $applicationUploadDir
                )
            ) {

                mkdir(
                    $applicationUploadDir,
                    0755,
                    true
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Document mapping
            |--------------------------------------------------------------------------
            */

            $documentFields = [

                'academic_certificate' =>
                    'Academic Certificate',

                'ielts_certificate' =>
                    'IELTS Certificate',

                'gre_certificate' =>
                    'GRE Certificate',

                'cv_resume' =>
                    'CV / Resume'
            ];


            /*
            |--------------------------------------------------------------------------
            | Process each document
            |--------------------------------------------------------------------------
            */

            foreach (
                $documentFields
                as $fieldName => $documentType
            ) {

                /*
                | -------------------------------------------------------
                | CASE 1:
                | New file uploaded during application
                | -------------------------------------------------------
                */

                if (
                    isset(
                        $_FILES[$fieldName]
                    ) &&
                    $_FILES[$fieldName]['error'] ===
                    UPLOAD_ERR_OK
                ) {

                    $newFileName =
                        'application_' .
                        $applicationId .
                        '_' .
                        bin2hex(
                            random_bytes(16)
                        ) .
                        '.pdf';


                    $destination =
                        $applicationUploadDir .
                        $newFileName;


                    $relativePath =
                        'uploads/application_documents/' .
                        $newFileName;


                    if (
                        move_uploaded_file(
                            $_FILES[$fieldName]['tmp_name'],
                            $destination
                        )
                    ) {

                        $originalFileName =
                            basename(
                                $_FILES[$fieldName]['name']
                            );


                        $insertDoc =
                            $conn->prepare("
                                INSERT INTO application_documents
                                (
                                    application_id,
                                    document_type,
                                    file_name,
                                    file_path
                                )
                                VALUES (?, ?, ?, ?)
                            ");


                        $insertDoc->bind_param(
                            "isss",
                            $applicationId,
                            $documentType,
                            $originalFileName,
                            $relativePath
                        );


                        $insertDoc->execute();

                        $insertDoc->close();
                    }


                    /*
                    | New upload takes priority.
                    */
                    continue;
                }


                /*
                | -------------------------------------------------------
                | CASE 2:
                | Reuse profile document
                | -------------------------------------------------------
                */

                $reuseField =
                    'use_' .
                    $fieldName;


                if (
                    isset(
                        $_POST[$reuseField]
                    ) &&
                    $_POST[$reuseField] === '1' &&
                    isset(
                        $profileDocuments[
                            $documentType
                        ]
                    )
                ) {

                    $profileDoc =
                        $profileDocuments[
                            $documentType
                        ];


                    $sourceFile =
                        __DIR__ .
                        '/' .
                        $profileDoc['file_path'];


                    if (
                        is_file(
                            $sourceFile
                        )
                    ) {

                        $newFileName =
                            'application_' .
                            $applicationId .
                            '_' .
                            bin2hex(
                                random_bytes(16)
                            ) .
                            '.pdf';


                        $destination =
                            $applicationUploadDir .
                            $newFileName;


                        $relativePath =
                            'uploads/application_documents/' .
                            $newFileName;


                        /*
                        | Copy profile document.
                        */
                        if (
                            copy(
                                $sourceFile,
                                $destination
                            )
                        ) {

                            $insertDoc =
                                $conn->prepare("
                                    INSERT INTO application_documents
                                    (
                                        application_id,
                                        document_type,
                                        file_name,
                                        file_path
                                    )
                                    VALUES (?, ?, ?, ?)
                                ");


                            $insertDoc->bind_param(
                                "isss",
                                $applicationId,
                                $documentType,
                                $profileDoc['file_name'],
                                $relativePath
                            );


                            $insertDoc->execute();

                            $insertDoc->close();
                        }
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Application successful
            |--------------------------------------------------------------------------
            */

            flash(
                'success',
                'Application submitted successfully. Your application is now Pending.'
            );


            header(
                "Location: applications.php"
            );

            exit;


        } else {

            /*
            |--------------------------------------------------------------------------
            | Duplicate protection
            |--------------------------------------------------------------------------
            */

            if (
                $conn->errno === 1062
            ) {

                $error =
                    "You already applied for this scholarship.";

            } else {

                $error =
                    "Could not submit application. Please try again.";
            }
        }


        $x->close();
    }
}

?>


<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        <?=e($s['title'])?> | ScholarMatch
    </title>

    <?php include 'partials/head.php'; ?>


    <style>

        /* =====================================================
           MAIN PAGE
           ===================================================== */

        .apply-page {

            width: 100%;

            max-width: 1100px;

            margin: 0 auto;
        }


        /* =====================================================
           HEADER
           ===================================================== */

        .apply-header {

            margin-bottom: 24px;
        }


        .apply-header h1 {

            margin: 8px 0;
        }


        /* =====================================================
           TWO COLUMN LAYOUT
           ===================================================== */

        .apply-layout {

            display: grid;

            grid-template-columns:
                minmax(0, 2fr)
                minmax(300px, 1fr);

            gap: 24px;

            align-items: start;
        }


        /* =====================================================
           SCHOLARSHIP DETAILS
           ===================================================== */

        .scholarship-details {

            padding: 30px;

            min-width: 0;

            box-sizing: border-box;
        }


        .scholarship-title {

            font-size: 30px;

            line-height: 1.25;

            margin: 10px 0 15px;

            color: #0f172a;

            overflow-wrap: anywhere;
        }


        .scholarship-description {

            color: #475569;

            line-height: 1.7;

            font-size: 16px;

            overflow-wrap: anywhere;
        }


        /* =====================================================
           AMOUNT
           ===================================================== */

        .amount-box {

            background: #eff6ff;

            border-radius: 14px;

            padding: 18px;

            margin: 22px 0;
        }


        .amount-label {

            color: #64748b;

            font-size: 13px;

            margin-bottom: 5px;
        }


        .amount-value {

            color: #2563eb;

            font-size: 30px;

            font-weight: 800;
        }


        /* =====================================================
           DETAILS GRID
           ===================================================== */

        .details-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 14px;

            margin-top: 20px;
        }


        .detail-item {

            border: 1px solid #e2e8f0;

            border-radius: 12px;

            padding: 15px;

            background: #fff;

            min-width: 0;

            box-sizing: border-box;
        }


        .detail-label {

            font-size: 12px;

            color: #64748b;

            margin-bottom: 5px;
        }


        .detail-value {

            font-weight: 700;

            color: #0f172a;

            overflow-wrap: anywhere;
        }


        /* =====================================================
           APPLICATION FORM
           ===================================================== */

        .application-form {

            margin-top: 30px;

            padding-top: 25px;

            border-top: 1px solid #e2e8f0;
        }


        .application-form h3 {

            margin-bottom: 6px;
        }


        .form-description {

            color: #64748b;

            font-size: 14px;

            margin-bottom: 20px;
        }


        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px;
        }


        .form-group {

            min-width: 0;
        }


        .form-group.full {

            grid-column: 1 / -1;
        }


        .form-group label {

            display: block;

            font-weight: 700;

            font-size: 14px;

            margin-bottom: 7px;

            color: #334155;
        }


        .form-group input,
        .form-group textarea {

            width: 100%;

            box-sizing: border-box;
        }


        .form-group textarea {

            min-height: 140px;

            resize: vertical;
        }


        .help-text {

            display: block;

            color: #64748b;

            font-size: 12px;

            margin-top: 6px;
        }


        /* =====================================================
           DOCUMENT BOX
           ===================================================== */

        .document-box {

            border: 1px solid #e2e8f0;

            border-radius: 12px;

            padding: 16px;

            margin-top: 12px;

            background: #f8fafc;
        }


        .document-box h4 {

            margin: 0 0 6px;
        }


        .document-box p {

            color: #64748b;

            font-size: 13px;

            margin: 0 0 12px;
        }


        .document-box input[type="file"] {

            width: 100%;

            box-sizing: border-box;
        }


        .reuse-document {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-top: 12px;

            font-size: 13px;
        }


        .reuse-document input {

            width: auto;
        }


        .existing-file {

            color: #15803d;

            font-size: 12px;

            margin-top: 8px;
        }


        /* =====================================================
           RIGHT SIDE PANEL
           ===================================================== */

        .side-panel {

            padding: 24px;

            height: fit-content;

            position: sticky;

            top: 20px;

            min-width: 0;

            width: 100%;

            box-sizing: border-box;

            overflow: hidden;
        }


        .side-panel h3 {

            margin-top: 0;

            margin-bottom: 18px;
        }


        /* =====================================================
           ELIGIBILITY BOX
           ===================================================== */

        .eligibility-box {

            border-radius: 14px;

            padding: 18px;

            margin-bottom: 18px;

            box-sizing: border-box;

            overflow-wrap: anywhere;
        }


        .eligible-box {

            background: #f0fdf4;

            border: 1px solid #bbf7d0;
        }


        .not-eligible-box {

            background: #fef2f2;

            border: 1px solid #fecaca;
        }


        /* =====================================================
           CHECK LIST
           ===================================================== */

        .check-list {

            list-style: none;

            padding: 0;

            margin: 15px 0;
        }


        .check-list li {

            padding: 9px 0;

            border-bottom:
                1px solid rgba(148, 163, 184, .2);

            font-size: 14px;

            line-height: 1.5;

            overflow-wrap: anywhere;
        }


        .check-list li:last-child {

            border-bottom: 0;
        }


        .check-good {

            color: #15803d;
        }


        .check-bad {

            color: #dc2626;
        }


        /* =====================================================
           EXISTING APPLICATION
           ===================================================== */

        .application-status {

            background: #fff7ed;

            border: 1px solid #fed7aa;

            padding: 18px;

            border-radius: 14px;

            margin-bottom: 18px;

            box-sizing: border-box;
        }


        .application-status strong {

            color: #c2410c;
        }


        /* =====================================================
           ACTION AREA
           ===================================================== */

        .action-area {

            margin-top: 20px;

            width: 100%;

            box-sizing: border-box;
        }


        .action-area .btn {

            display: block;

            width: 100%;

            max-width: 100%;

            box-sizing: border-box;

            margin: 0 0 10px 0;

            text-align: center;

            white-space: normal;

            overflow-wrap: anywhere;
        }


        .action-area form {

            display: block;

            width: 100%;

            margin: 0;

            padding: 0;

            box-sizing: border-box;
        }


        /* =====================================================
           PROVIDER
           ===================================================== */

        .provider-box {

            margin-top: 25px;

            padding-top: 20px;

            border-top: 1px solid #e2e8f0;

            overflow-wrap: anywhere;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 900px) {

            .apply-layout {

                grid-template-columns: 1fr;
            }


            .side-panel {

                position: static;
            }

        }


        @media (max-width: 600px) {

            .scholarship-details {

                padding: 20px;
            }


            .side-panel {

                padding: 20px;
            }


            .scholarship-title {

                font-size: 24px;
            }


            .amount-value {

                font-size: 26px;
            }


            .details-grid,
            .form-grid {

                grid-template-columns: 1fr;
            }


            .form-group.full {

                grid-column: auto;
            }


            .apply-header h1 {

                font-size: 25px;
            }

        }


        @media (max-width: 380px) {

            .side-panel {

                padding: 16px;
            }


            .scholarship-details {

                padding: 16px;
            }


            .action-area .btn {

                font-size: 14px;

                padding-left: 10px;

                padding-right: 10px;
            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<main class="container">


<div class="apply-page">


    <!-- =====================================================
         PAGE HEADER
         ===================================================== -->

    <div class="apply-header">

        <span class="badge">
            SCHOLARSHIP APPLICATION
        </span>


        <h1>
            Review & Apply
        </h1>


        <p class="small">

            Review the scholarship information,
            complete your application information,
            and submit your application.

        </p>

    </div>


    <div class="apply-layout">


        <!-- =================================================
             LEFT SIDE
             ================================================= -->

        <section class="panel scholarship-details">


            <h2 class="scholarship-title">

                <?=e($s['title'])?>

            </h2>


            <p class="scholarship-description">

                <?=e($s['description'])?>

            </p>


            <!-- SCHOLARSHIP AMOUNT -->

            <div class="amount-box">

                <div class="amount-label">

                    Scholarship Amount

                </div>


                <div class="amount-value">

                    ৳<?=number_format(
                        (float)$s['amount']
                    )?>

                </div>

            </div>


            <!-- SCHOLARSHIP INFORMATION -->

            <h3>

                Scholarship Information

            </h3>


            <div class="details-grid">


                <div class="detail-item">

                    <div class="detail-label">
                        Provider
                    </div>

                    <div class="detail-value">
                        <?=e($s['provider_name'])?>
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        Application Deadline
                    </div>

                    <div class="detail-value">
                        <?=e($s['deadline'])?>
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        Minimum CGPA
                    </div>

                    <div class="detail-value">
                        <?=e($s['min_cgpa'])?>
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        Education Level
                    </div>

                    <div class="detail-value">
                        <?=e($s['education_level'])?>
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        Field of Study
                    </div>

                    <div class="detail-value">
                        <?=e($s['field_of_study'])?>
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        Eligible Country
                    </div>

                    <div class="detail-value">
                        <?=e($s['country'])?>
                    </div>

                </div>


            </div>


            <!-- =================================================
                 APPLICATION FORM
                 ================================================= -->

            <?php if (!$existing && $eligible): ?>

                <div class="application-form">

                    <h3>
                        📝 Application Information
                    </h3>


                    <p class="form-description">

                        Your profile information is already
                        available. You can update the application
                        information below before submitting.

                    </p>


                    <form
                        method="post"
                        enctype="multipart/form-data"
                        onsubmit="return confirm('Are you sure you want to submit this application?');"
                    >


                        <input
                            type="hidden"
                            name="csrf"
                            value="<?=csrf()?>"
                        >


                        <!-- =========================================
                             TEST SCORES
                        ========================================== -->

                        <div class="form-grid">


                            <div class="form-group">

                                <label for="ielts_score">

                                    IELTS Score

                                </label>


                                <input
                                    type="number"
                                    id="ielts_score"
                                    name="ielts_score"
                                    min="0"
                                    max="9"
                                    step="0.5"
                                    value="<?=e($ieltsScore)?>"
                                    placeholder="Example: 7.5"
                                >


                                <span class="help-text">

                                    Score range: 0–9.
                                    Your profile score is loaded automatically.

                                </span>

                            </div>


                            <div class="form-group">

                                <label for="gre_score">

                                    GRE Score

                                </label>


                                <input
                                    type="number"
                                    id="gre_score"
                                    name="gre_score"
                                    min="0"
                                    max="340"
                                    step="1"
                                    value="<?=e($greScore)?>"
                                    placeholder="Example: 315"
                                >


                                <span class="help-text">

                                    Score range: 0–340.
                                    Your profile score is loaded automatically.

                                </span>

                            </div>


                            <!-- =====================================
                                 EXTRACURRICULAR
                            ====================================== -->

                            <div class="form-group full">

                                <label for="extracurricular">

                                    🏆 Extracurricular Activities

                                </label>


                                <textarea
                                    id="extracurricular"
                                    name="extracurricular"
                                    maxlength="5000"
                                    placeholder="Describe your clubs, volunteering, competitions, leadership activities, awards and achievements."
                                ><?=e($extracurricular)?></textarea>

                            </div>


                            <!-- =====================================
                                 PERSONAL STATEMENT
                            ====================================== -->

                            <div class="form-group full">

                                <label for="personal_statement">

                                    ✍️ Personal Statement

                                </label>


                                <textarea
                                    id="personal_statement"
                                    name="personal_statement"
                                    maxlength="10000"
                                    required
                                    placeholder="Explain why you are applying for this scholarship, your academic goals, career goals, financial need, and how this scholarship will help you."
                                ><?=e($personalStatement)?></textarea>


                                <span class="help-text">

                                    Explain why you deserve this scholarship
                                    and how it will support your goals.

                                </span>

                            </div>


                        </div>


                        <!-- =========================================
                             DOCUMENTS
                        ========================================== -->

                        <div style="margin-top:25px">

                            <h3>
                                📄 Application Documents
                            </h3>


                            <p class="form-description">

                                You can reuse documents from your profile
                                or upload a different PDF specifically
                                for this application.

                                Maximum 5 MB per document.

                            </p>


                            <!-- Academic Certificate -->

                            <div class="document-box">

                                <h4>
                                    🎓 Academic Certificate
                                </h4>


                                <p>
                                    Academic certificate or transcript.
                                </p>


                                <?php if (
                                    isset(
                                        $profileDocuments[
                                            'Academic Certificate'
                                        ]
                                    )
                                ): ?>

                                    <label
                                        class="reuse-document"
                                    >

                                        <input
                                            type="checkbox"
                                            name="use_academic_certificate"
                                            value="1"
                                            checked
                                        >

                                        Use my profile document:

                                        <strong>
                                            <?=e(
                                                $profileDocuments[
                                                    'Academic Certificate'
                                                ]['file_name']
                                            )?>
                                        </strong>

                                    </label>

                                <?php endif; ?>


                                <input
                                    type="file"
                                    name="academic_certificate"
                                    accept=".pdf,application/pdf"
                                >


                                <span class="help-text">

                                    Upload a new PDF only if you don't
                                    want to use your profile document.

                                </span>

                            </div>


                            <!-- IELTS Certificate -->

                            <div class="document-box">

                                <h4>
                                    🌐 IELTS Certificate
                                </h4>


                                <p>
                                    IELTS certificate or score report.
                                </p>


                                <?php if (
                                    isset(
                                        $profileDocuments[
                                            'IELTS Certificate'
                                        ]
                                    )
                                ): ?>

                                    <label
                                        class="reuse-document"
                                    >

                                        <input
                                            type="checkbox"
                                            name="use_ielts_certificate"
                                            value="1"
                                            checked
                                        >

                                        Use my profile document:

                                        <strong>
                                            <?=e(
                                                $profileDocuments[
                                                    'IELTS Certificate'
                                                ]['file_name']
                                            )?>
                                        </strong>

                                    </label>

                                <?php endif; ?>


                                <input
                                    type="file"
                                    name="ielts_certificate"
                                    accept=".pdf,application/pdf"
                                >

                            </div>


                            <!-- GRE Certificate -->

                            <div class="document-box">

                                <h4>
                                    📊 GRE Certificate
                                </h4>


                                <p>
                                    GRE score report.
                                </p>


                                <?php if (
                                    isset(
                                        $profileDocuments[
                                            'GRE Certificate'
                                        ]
                                    )
                                ): ?>

                                    <label
                                        class="reuse-document"
                                    >

                                        <input
                                            type="checkbox"
                                            name="use_gre_certificate"
                                            value="1"
                                            checked
                                        >

                                        Use my profile document:

                                        <strong>
                                            <?=e(
                                                $profileDocuments[
                                                    'GRE Certificate'
                                                ]['file_name']
                                            )?>
                                        </strong>

                                    </label>

                                <?php endif; ?>


                                <input
                                    type="file"
                                    name="gre_certificate"
                                    accept=".pdf,application/pdf"
                                >

                            </div>


                            <!-- CV -->

                            <div class="document-box">

                                <h4>
                                    📄 CV / Resume
                                </h4>


                                <p>
                                    Your latest CV or Resume.
                                </p>


                                <?php if (
                                    isset(
                                        $profileDocuments[
                                            'CV / Resume'
                                        ]
                                    )
                                ): ?>

                                    <label
                                        class="reuse-document"
                                    >

                                        <input
                                            type="checkbox"
                                            name="use_cv_resume"
                                            value="1"
                                            checked
                                        >

                                        Use my profile document:

                                        <strong>
                                            <?=e(
                                                $profileDocuments[
                                                    'CV / Resume'
                                                ]['file_name']
                                            )?>
                                        </strong>

                                    </label>

                                <?php endif; ?>


                                <input
                                    type="file"
                                    name="cv_resume"
                                    accept=".pdf,application/pdf"
                                >

                            </div>


                            <span class="help-text">

                                PDF files only. Maximum 5 MB per file.

                            </span>

                        </div>


                        <!-- =========================================
                             SUBMIT
                        ========================================== -->

                        <div
                            class="action-area"
                            style="margin-top:25px"
                        >

                            <button
                                type="submit"
                                class="btn"
                            >

                                ✓ Confirm & Submit Application

                            </button>


                            <a
                                class="btn secondary"
                                href="scholarships.php"
                            >

                                Cancel

                            </a>

                        </div>


                    </form>

                </div>

            <?php endif; ?>


            <!-- PROVIDER -->

            <div class="provider-box">

                <div class="small">

                    Scholarship provided by

                </div>


                <strong>

                    <?=e($s['provider_name'])?>

                </strong>

            </div>


        </section>


        <!-- =================================================
             RIGHT SIDE
             ELIGIBILITY
             ================================================= -->

        <aside class="panel side-panel">


            <h3>

                Eligibility Check

            </h3>


            <!-- =================================================
                 ALREADY APPLIED
                 ================================================= -->

            <?php if ($existing): ?>


                <div class="application-status">


                    <div>

                        You have already applied for this
                        scholarship.

                    </div>


                    <br>


                    <strong>

                        Current Status:
                        <?=e($existing['status'])?>

                    </strong>


                    <?php if (
                        !empty(
                            $existing['applied_at']
                        )
                    ): ?>

                        <div
                            class="small"
                            style="margin-top:8px"
                        >

                            Applied:
                            <?=e(
                                $existing['applied_at']
                            )?>

                        </div>

                    <?php endif; ?>


                </div>


                <div class="action-area">


                    <a
                        class="btn"
                        href="applications.php"
                    >

                        Track Application

                    </a>


                    <a
                        class="btn secondary"
                        href="scholarships.php"
                    >

                        Browse Scholarships

                    </a>


                </div>


            <?php else: ?>


                <!-- =================================================
                     ELIGIBLE
                     ================================================= -->

                <?php if ($eligible): ?>


                    <div
                        class="eligibility-box eligible-box"
                    >


                        <strong>

                            ✓ You are eligible

                        </strong>


                        <p class="small">

                            Based on your current profile,
                            you meet all requirements for
                            this scholarship.

                        </p>


                    </div>


                    <h4>

                        Requirements Matched

                    </h4>


                    <ul class="check-list">


                        <?php foreach (
                            $matched as $item
                        ): ?>


                            <li class="check-good">

                                ✓ <?=e($item)?>

                            </li>


                        <?php endforeach; ?>


                    </ul>


                    <?php if ($error): ?>

                        <div class="alert error">

                            <?=e($error)?>

                        </div>

                    <?php endif; ?>


                <!-- =================================================
                     NOT ELIGIBLE
                     ================================================= -->

                <?php else: ?>


                    <div
                        class="eligibility-box not-eligible-box"
                    >


                        <strong>

                            ✕ You are not eligible

                        </strong>


                        <p class="small">

                            One or more scholarship requirements
                            do not match your current profile.

                        </p>


                    </div>


                    <h4>

                        Requirements Met

                    </h4>


                    <ul class="check-list">


                        <?php if ($matched): ?>


                            <?php foreach (
                                $matched as $item
                            ): ?>


                                <li class="check-good">

                                    ✓ <?=e($item)?>

                                </li>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <li>

                                No requirements matched.

                            </li>


                        <?php endif; ?>


                    </ul>


                    <h4>

                        What Needs Improvement

                    </h4>


                    <ul class="check-list">


                        <?php foreach (
                            $failed as $item
                        ): ?>


                            <li class="check-bad">

                                ✕ <?=e($item)?>

                            </li>


                        <?php endforeach; ?>


                    </ul>


                    <div class="action-area">


                        <a
                            class="btn secondary"
                            href="recommendations.php"
                        >

                            ← Back to Recommendations

                        </a>


                    </div>


                <?php endif; ?>


            <?php endif; ?>


            <!-- =================================================
                 ERROR
                 ================================================= -->

            <?php if (
                $error &&
                (!$eligible || $existing)
            ): ?>


                <div
                    class="alert error"
                    style="margin-top:15px"
                >

                    <?=e($error)?>

                </div>


            <?php endif; ?>


        </aside>


    </div>


</div>


</main>


</body>

</html>