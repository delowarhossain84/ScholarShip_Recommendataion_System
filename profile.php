<?php

require_once 'config.php';

require_role('student');

$u = user();

$studentId = (int)$u['id'];

$error = '';

/* =========================================================
   LOAD CURRENT PROFILE DATA
   ========================================================= */

$name       = $u['name'] ?? '';
$email      = $u['email'] ?? '';
$education  = $u['education_level'] ?? '';
$field      = $u['field_of_study'] ?? '';
$cgpa       = $u['cgpa'] ?? '';
$income     = $u['income'] ?? '';
$country    = $u['country'] ?? '';

/* Load new profile fields from database. */

$profileResult = $conn->query("
    SELECT
        ielts_score,
        gre_score,
        extracurricular
    FROM users
    WHERE id = $studentId
    LIMIT 1
");

$profileData = $profileResult
    ? $profileResult->fetch_assoc()
    : [];

$ielts = $profileData['ielts_score'] ?? '';

$gre = $profileData['gre_score'] ?? '';

$extracurricular = $profileData['extracurricular'] ?? '';


/* =========================================================
   UPDATE PROFILE
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    /* ---------------------------------------------------------
       GET FORM DATA
       --------------------------------------------------------- */

    $name = trim($_POST['name'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $education = trim($_POST['education_level'] ?? '');

    $field = trim($_POST['field_of_study'] ?? '');

    $cgpa = trim($_POST['cgpa'] ?? '');

    $income = trim($_POST['income'] ?? '');

    $country = trim($_POST['country'] ?? '');

    $ielts = trim($_POST['ielts_score'] ?? '');

    $gre = trim($_POST['gre_score'] ?? '');

    $extracurricular = trim(
        $_POST['extracurricular'] ?? ''
    );


    /* =========================================================
       VALIDATION
       ========================================================= */

    if ($name === '') {

        $error = 'Name is required.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif ($education === '') {

        $error = 'Please select your education level.';

    } elseif ($field === '') {

        $error = 'Field of study is required.';

    } elseif (
        $cgpa === '' ||
        !is_numeric($cgpa) ||
        (float)$cgpa < 0 ||
        (float)$cgpa > 4
    ) {

        $error = 'CGPA must be between 0 and 4.00.';

    } elseif (
        $income !== '' &&
        (
            !is_numeric($income) ||
            (float)$income < 0
        )
    ) {

        $error = 'Income must be a valid positive number.';

    } elseif ($country === '') {

        $error = 'Country is required.';
    }


    /* =========================================================
       IELTS VALIDATION
       ========================================================= */

    if (
        $error === '' &&
        $ielts !== '' &&
        (
            !is_numeric($ielts) ||
            (float)$ielts < 0 ||
            (float)$ielts > 9
        )
    ) {

        $error = 'IELTS score must be between 0 and 9.';
    }


    /* =========================================================
       GRE VALIDATION
       ========================================================= */

    if (
        $error === '' &&
        $gre !== '' &&
        (
            !is_numeric($gre) ||
            (float)$gre < 0 ||
            (float)$gre > 340
        )
    ) {

        $error = 'GRE score must be between 0 and 340.';
    }


    /* =========================================================
       CHECK EMAIL
       ========================================================= */

    if ($error === '') {

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");

        $check->bind_param(
            'si',
            $email,
            $studentId
        );

        $check->execute();

        $emailExists =
            $check->get_result()->fetch_assoc();

        $check->close();


        if ($emailExists) {

            $error =
                'This email address is already being used.';
        }
    }


    /* =========================================================
       SAVE PROFILE
       ========================================================= */

    if ($error === '') {

        $cgpaValue = (float)$cgpa;

        $incomeValue =
            $income === ''
                ? null
                : (float)$income;

        $ieltsValue =
            $ielts === ''
                ? null
                : (float)$ielts;

        $greValue =
            $gre === ''
                ? null
                : (float)$gre;


        $update = $conn->prepare("
            UPDATE users
            SET
                name = ?,
                email = ?,
                education_level = ?,
                field_of_study = ?,
                cgpa = ?,
                income = ?,
                country = ?,
                ielts_score = ?,
                gre_score = ?,
                extracurricular = ?
            WHERE id = ?
        ");


        $update->bind_param(
            'ssssddsddsi',
            $name,
            $email,
            $education,
            $field,
            $cgpaValue,
            $incomeValue,
            $country,
            $ieltsValue,
            $greValue,
            $extracurricular,
            $studentId
        );


        if ($update->execute()) {

            /* Update session data. */

            $_SESSION['user']['name'] = $name;

            $_SESSION['user']['email'] = $email;

            $_SESSION['user']['education_level'] =
                $education;

            $_SESSION['user']['field_of_study'] =
                $field;

            $_SESSION['user']['cgpa'] =
                $cgpaValue;

            $_SESSION['user']['income'] =
                $incomeValue;

            $_SESSION['user']['country'] =
                $country;

            $_SESSION['user']['ielts_score'] =
                $ieltsValue;

            $_SESSION['user']['gre_score'] =
                $greValue;

            $_SESSION['user']['extracurricular'] =
                $extracurricular;

        } else {

            $error =
                'Could not update your profile.';
        }

        $update->close();
    }


    /* =========================================================
       UPLOAD PROFILE DOCUMENTS
       ========================================================= */

    if ($error === '') {

        /*
        |--------------------------------------------------------------------------
        | Upload directory
        |--------------------------------------------------------------------------
        */

        $uploadDir =
            __DIR__ .
            '/uploads/application_documents/';


        /*
        |--------------------------------------------------------------------------
        | Create directory if it does not exist
        |--------------------------------------------------------------------------
        */

        if (!is_dir($uploadDir)) {

            if (!mkdir($uploadDir, 0755, true)) {

                $error =
                    'Could not create upload directory.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Document fields
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
        | Process uploaded documents
        |--------------------------------------------------------------------------
        */

        if ($error === '') {

            foreach (
                $documentFields as $fieldName => $documentType
            ) {

                /*
                |--------------------------------------------------------------------------
                | Skip if no file selected
                |--------------------------------------------------------------------------
                */

                if (
                    !isset($_FILES[$fieldName]) ||
                    $_FILES[$fieldName]['error'] ===
                    UPLOAD_ERR_NO_FILE
                ) {

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Check upload error
                |--------------------------------------------------------------------------
                */

                if (
                    $_FILES[$fieldName]['error'] !==
                    UPLOAD_ERR_OK
                ) {

                    $error =
                        $documentType .
                        ' upload failed.';

                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | Maximum file size = 5 MB
                |--------------------------------------------------------------------------
                */

                if (
                    $_FILES[$fieldName]['size'] >
                    5 * 1024 * 1024
                ) {

                    $error =
                        $documentType .
                        ' must be 5 MB or smaller.';

                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | Temporary file
                |--------------------------------------------------------------------------
                */

                $tmpFile =
                    $_FILES[$fieldName]['tmp_name'];


                /*
                |--------------------------------------------------------------------------
                | Check actual MIME type
                |--------------------------------------------------------------------------
                */

                $finfo =
                    finfo_open(FILEINFO_MIME_TYPE);

                $mimeType =
                    finfo_file(
                        $finfo,
                        $tmpFile
                    );

                finfo_close($finfo);


                /*
                |--------------------------------------------------------------------------
                | Only PDF is allowed
                |--------------------------------------------------------------------------
                */

                if (
                    $mimeType !==
                    'application/pdf'
                ) {

                    $error =
                        $documentType .
                        ' must be a PDF file.';

                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | Generate secure random filename
                |--------------------------------------------------------------------------
                */

                $newFileName =
                    'student_' .
                    $studentId .
                    '_' .
                    bin2hex(
                        random_bytes(16)
                    ) .
                    '.pdf';


                /*
                |--------------------------------------------------------------------------
                | Full physical path
                |--------------------------------------------------------------------------
                */

                $destination =
                    $uploadDir .
                    $newFileName;


                /*
                |--------------------------------------------------------------------------
                | Relative path for database
                |--------------------------------------------------------------------------
                */

                $relativePath =
                    'uploads/application_documents/' .
                    $newFileName;


                /*
                |--------------------------------------------------------------------------
                | Move uploaded file
                |--------------------------------------------------------------------------
                */

                if (
                    !move_uploaded_file(
                        $tmpFile,
                        $destination
                    )
                ) {

                    $error =
                        'Could not save ' .
                        $documentType .
                        '.';

                    break;
                }


                /*
                |--------------------------------------------------------------------------
                | Get existing document
                |--------------------------------------------------------------------------
                */

                $oldStmt = $conn->prepare("
                    SELECT
                        id,
                        file_path
                    FROM student_documents
                    WHERE student_id = ?
                    AND document_type = ?
                    LIMIT 1
                ");


                $oldStmt->bind_param(
                    'is',
                    $studentId,
                    $documentType
                );


                $oldStmt->execute();


                $oldDocument =
                    $oldStmt
                        ->get_result()
                        ->fetch_assoc();


                $oldStmt->close();


                /*
                |--------------------------------------------------------------------------
                | Original filename
                |--------------------------------------------------------------------------
                */

                $originalFileName =
                    basename(
                        $_FILES[$fieldName]['name']
                    );


                /*
                |--------------------------------------------------------------------------
                | Replace existing document
                |--------------------------------------------------------------------------
                */

                if ($oldDocument) {

                    /*
                    | Delete old physical file.
                    */

                    $oldFile =
                        __DIR__ .
                        '/' .
                        $oldDocument['file_path'];


                    if (is_file($oldFile)) {

                        unlink($oldFile);
                    }


                    /*
                    | Update database.
                    */

                    $updateDoc = $conn->prepare("
                        UPDATE student_documents
                        SET
                            file_name = ?,
                            file_path = ?,
                            uploaded_at =
                                CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");


                    $updateDoc->bind_param(
                        'ssi',
                        $originalFileName,
                        $relativePath,
                        $oldDocument['id']
                    );


                    if (!$updateDoc->execute()) {

                        /*
                        | Delete newly uploaded file
                        | if database update fails.
                        */

                        if (is_file($destination)) {

                            unlink($destination);
                        }

                        $error =
                            'Could not update ' .
                            $documentType .
                            '.';

                        $updateDoc->close();

                        break;
                    }


                    $updateDoc->close();


                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Insert new document
                    |--------------------------------------------------------------------------
                    */

                    $insertDoc = $conn->prepare("
                        INSERT INTO student_documents
                        (
                            student_id,
                            user_id,
                            document_type,
                            file_name,
                            file_path
                        )
                        VALUES (?, ?, ?, ?, ?)
                    ");


                    $insertDoc->bind_param(
                        'iisss',
                        $studentId,
                        $studentId,
                        $documentType,
                        $originalFileName,
                        $relativePath
                    );


                    if (!$insertDoc->execute()) {

                        /*
                        | Delete uploaded file if
                        | database insertion fails.
                        */

                        if (is_file($destination)) {

                            unlink($destination);
                        }

                        $error =
                            'Could not save ' .
                            $documentType .
                            ' information.';

                        $insertDoc->close();

                        break;
                    }


                    $insertDoc->close();
                }
            }
        }
    }


    /* =========================================================
       SUCCESS
       ========================================================= */

    if ($error === '') {

        flash(
            'success',
            'Profile and documents updated successfully.'
        );

        header(
            'Location: profile.php'
        );

        exit;
    }
}


/* =========================================================
   LOAD PROFILE DOCUMENTS
   ========================================================= */

$documents = [];


$docResult = $conn->query("
    SELECT *
    FROM student_documents
    WHERE student_id = $studentId
    ORDER BY uploaded_at DESC
");


if ($docResult) {

    while (
        $doc = $docResult->fetch_assoc()
    ) {

        $documents[
            $doc['document_type']
        ] = $doc;
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
        My Profile | ScholarMatch
    </title>

    <?php include 'partials/head.php'; ?>


    <style>

        /* =========================================================
           PROFILE PAGE
           ========================================================= */

        .profile-page {

            max-width: 950px;

            margin: 0 auto;
        }


        /* =========================================================
           HEADER
           ========================================================= */

        .profile-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;
        }


        /* =========================================================
           PROFILE CARD
           ========================================================= */

        .profile-card {

            padding: 28px;

            box-sizing: border-box;
        }


        /* =========================================================
           SECTION
           ========================================================= */

        .profile-section {

            margin-bottom: 32px;

            padding-bottom: 25px;

            border-bottom: 1px solid #e2e8f0;
        }


        .profile-section:last-child {

            border-bottom: none;

            margin-bottom: 0;
        }


        .profile-section h3 {

            margin-bottom: 6px;
        }


        .section-description {

            color: #64748b;

            font-size: 14px;

            margin-bottom: 20px;
        }


        /* =========================================================
           FORM GRID
           ========================================================= */

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
        .form-group select,
        .form-group textarea {

            width: 100%;

            box-sizing: border-box;
        }


        .form-group textarea {

            min-height: 120px;

            resize: vertical;
        }


        /* =========================================================
           HELP TEXT
           ========================================================= */

        .help-text {

            display: block;

            margin-top: 6px;

            color: #64748b;

            font-size: 12px;
        }


        /* =========================================================
           DOCUMENT GRID
           ========================================================= */

        .document-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px;
        }


        .document-card {

            border: 1px solid #e2e8f0;

            border-radius: 14px;

            padding: 18px;

            background: #f8fafc;
        }


        .document-card h4 {

            margin: 0 0 8px;

            color: #172033;
        }


        .document-card p {

            margin: 0 0 12px;

            color: #64748b;

            font-size: 13px;
        }


        .document-card input[type="file"] {

            width: 100%;

            box-sizing: border-box;
        }


        .document-existing {

            margin-top: 10px;

            padding: 8px 10px;

            background: #dcfce7;

            color: #166534;

            border-radius: 8px;

            font-size: 12px;
        }


        /* =========================================================
           PROFILE AVATAR
           ========================================================= */

        .profile-avatar {

            width: 70px;

            height: 70px;

            border-radius: 50%;

            background: #eff6ff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 32px;

            margin-bottom: 15px;
        }


        /* =========================================================
           ACTIONS
           ========================================================= */

        .profile-actions {

            border-top: 1px solid #e2e8f0;

            padding-top: 20px;

            margin-top: 10px;

            display: flex;

            gap: 10px;
        }


        /* =========================================================
           RESPONSIVE
           ========================================================= */

        @media (max-width: 700px) {

            .profile-header {

                display: block;
            }


            .profile-header .btn {

                display: block;

                width: 100%;

                box-sizing: border-box;

                text-align: center;

                margin-top: 15px;
            }


            .profile-card {

                padding: 20px;
            }


            .form-grid,
            .document-grid {

                grid-template-columns: 1fr;
            }


            .form-group.full {

                grid-column: auto;
            }


            .profile-actions {

                display: grid;

                grid-template-columns: 1fr;
            }


            .profile-actions .btn {

                width: 100%;

                box-sizing: border-box;
            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<main class="container">


<div class="profile-page">


    <!-- =====================================================
         HEADER
         ===================================================== -->

    <div class="profile-header">

        <div>

            <span class="badge">
                STUDENT PROFILE
            </span>

            <h1 class="page-title">
                My Profile
            </h1>

            <p class="small">
                Keep your information updated for better
                scholarship recommendations.
            </p>

        </div>


        

    </div>


    <!-- =====================================================
         PROFILE CARD
         ===================================================== -->

    <div class="panel profile-card">


        <div class="profile-avatar">
            👨‍🎓
        </div>


        <?php if ($error): ?>

            <div class="alert error">
                <?=e($error)?>
            </div>

        <?php endif; ?>


        <?php show_flash(); ?>


        <form
            method="post"
            enctype="multipart/form-data"
        >


            <input
                type="hidden"
                name="csrf"
                value="<?=csrf()?>"
            >


            <!-- =================================================
                 PERSONAL INFORMATION
                 ================================================= -->

            <div class="profile-section">

                <h3>
                    👤 Personal Information
                </h3>

                <p class="section-description">
                    Update your basic personal information.
                </p>


                <div class="form-grid">


                    <div class="form-group">

                        <label for="name">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?=e($name)?>"
                            maxlength="120"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?=e($email)?>"
                            maxlength="150"
                            required
                        >

                    </div>


                    <div class="form-group full">

                        <label for="country">
                            Country
                        </label>

                        <input
                            type="text"
                            id="country"
                            name="country"
                            value="<?=e($country)?>"
                            maxlength="100"
                            placeholder="Example: Bangladesh"
                            required
                        >

                    </div>


                </div>

            </div>


            <!-- =================================================
                 ACADEMIC INFORMATION
                 ================================================= -->

            <div class="profile-section">

                <h3>
                    🎓 Academic Information
                </h3>

                <p class="section-description">
                    This information helps ScholarMatch
                    find suitable scholarships.
                </p>


                <div class="form-grid">


                    <div class="form-group">

                        <label for="education_level">
                            Education Level
                        </label>

                        <select
                            id="education_level"
                            name="education_level"
                            required
                        >

                            <option value="">
                                Select education level
                            </option>


                            <option
                                value="High School"
                                <?= $education === 'High School'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                High School
                            </option>


                            <option
                                value="Undergraduate"
                                <?= $education === 'Undergraduate'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Undergraduate
                            </option>


                            <option
                                value="Graduate"
                                <?= $education === 'Graduate'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Graduate
                            </option>


                            <option
                                value="Postgraduate"
                                <?= $education === 'Postgraduate'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Postgraduate
                            </option>


                            <option
                                value="PhD"
                                <?= $education === 'PhD'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                PhD
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label for="field_of_study">
                            Field of Study
                        </label>

                        <input
                            type="text"
                            id="field_of_study"
                            name="field_of_study"
                            value="<?=e($field)?>"
                            maxlength="120"
                            placeholder="Example: Computer Science"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="cgpa">
                            CGPA
                        </label>

                        <input
                            type="number"
                            id="cgpa"
                            name="cgpa"
                            value="<?=e($cgpa)?>"
                            min="0"
                            max="4"
                            step="0.01"
                            placeholder="Example: 3.75"
                            required
                        >

                    </div>


                </div>

            </div>


            <!-- =================================================
                 FINANCIAL INFORMATION
                 ================================================= -->

            <div class="profile-section">

                <h3>
                    💰 Financial Information
                </h3>

                <p class="section-description">
                    Used for scholarships that consider
                    financial need.
                </p>


                <div class="form-grid">


                    <div class="form-group">

                        <label for="income">
                            Annual Income (৳)
                        </label>

                        <input
                            type="number"
                            id="income"
                            name="income"
                            value="<?=e($income)?>"
                            min="0"
                            step="0.01"
                            placeholder="Example: 250000"
                        >

                    </div>


                </div>

            </div>


            <!-- =================================================
                 TEST SCORES
                 ================================================= -->

            <div class="profile-section">

                <h3>
                    📊 Test Scores
                </h3>

                <p class="section-description">
                    Add your English proficiency and
                    graduate admission test scores.
                </p>


                <div class="form-grid">


                    <div class="form-group">

                        <label for="ielts_score">
                            IELTS Score
                        </label>

                        <input
                            type="number"
                            id="ielts_score"
                            name="ielts_score"
                            value="<?=e($ielts)?>"
                            min="0"
                            max="9"
                            step="0.5"
                            placeholder="Example: 7.5"
                        >

                        <span class="help-text">
                            Score range: 0–9
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
                            value="<?=e($gre)?>"
                            min="0"
                            max="340"
                            step="1"
                            placeholder="Example: 315"
                        >

                        <span class="help-text">
                            Score range: 0–340
                        </span>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 EXTRACURRICULAR
                 ================================================= -->

            <div class="profile-section">

                <h3>
                    🏆 Extracurricular Activities
                </h3>

                <p class="section-description">
                    Describe your clubs, volunteering,
                    competitions, leadership activities,
                    awards and other achievements.
                </p>


                <div class="form-group">

                    <label for="extracurricular">
                        Activities & Achievements
                    </label>

                    <textarea
                        id="extracurricular"
                        name="extracurricular"
                        maxlength="5000"
                        placeholder="Example:
Programming Club President
University Hackathon Participant
Blood Donation Volunteer
University Debate Competition"
                    ><?=e($extracurricular)?></textarea>

                </div>

            </div>


            <!-- =================================================
                 PROFILE DOCUMENTS
                 ================================================= -->

            <div class="profile-section">

                <h3>
                    📄 Profile Documents
                </h3>

                <p class="section-description">
                    Upload your documents once. They can
                    later be reused when applying for scholarships.
                    PDF files only. Maximum 5 MB per file.
                </p>


                <div class="document-grid">


                    <!-- Academic Certificate -->

                    <div class="document-card">

                        <h4>
                            🎓 Academic Certificate
                        </h4>

                        <p>
                            Upload your academic certificate
                            or transcript.
                        </p>

                        <input
                            type="file"
                            name="academic_certificate"
                            accept=".pdf,application/pdf"
                        >


                        <?php if (
                            isset(
                                $documents[
                                    'Academic Certificate'
                                ]
                            )
                        ): ?>

                            <div class="document-existing">

                                ✓ Uploaded:
                                <?=e(
                                    $documents[
                                        'Academic Certificate'
                                    ]['file_name']
                                )?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- IELTS Certificate -->

                    <div class="document-card">

                        <h4>
                            🌐 IELTS Certificate
                        </h4>

                        <p>
                            Upload your IELTS certificate.
                        </p>

                        <input
                            type="file"
                            name="ielts_certificate"
                            accept=".pdf,application/pdf"
                        >


                        <?php if (
                            isset(
                                $documents[
                                    'IELTS Certificate'
                                ]
                            )
                        ): ?>

                            <div class="document-existing">

                                ✓ Uploaded:
                                <?=e(
                                    $documents[
                                        'IELTS Certificate'
                                    ]['file_name']
                                )?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- GRE Certificate -->

                    <div class="document-card">

                        <h4>
                            📊 GRE Certificate
                        </h4>

                        <p>
                            Upload your GRE score report.
                        </p>

                        <input
                            type="file"
                            name="gre_certificate"
                            accept=".pdf,application/pdf"
                        >


                        <?php if (
                            isset(
                                $documents[
                                    'GRE Certificate'
                                ]
                            )
                        ): ?>

                            <div class="document-existing">

                                ✓ Uploaded:
                                <?=e(
                                    $documents[
                                        'GRE Certificate'
                                    ]['file_name']
                                )?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- CV -->

                    <div class="document-card">

                        <h4>
                            📄 CV / Resume
                        </h4>

                        <p>
                            Upload your latest CV or resume.
                        </p>

                        <input
                            type="file"
                            name="cv_resume"
                            accept=".pdf,application/pdf"
                        >


                        <?php if (
                            isset(
                                $documents[
                                    'CV / Resume'
                                ]
                            )
                        ): ?>

                            <div class="document-existing">

                                ✓ Uploaded:
                                <?=e(
                                    $documents[
                                        'CV / Resume'
                                    ]['file_name']
                                )?>

                            </div>

                        <?php endif; ?>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 SAVE BUTTON
                 ================================================= -->

            <div class="profile-actions">

                <button
                    type="submit"
                    class="btn"
                >
                    ✓ Save Profile
                </button>


                <a
                    class="btn secondary"
                    href="dashboard.php"
                >
                    Cancel
                </a>

            </div>


        </form>


    </div>


</div>


</main>


</body>

</html>