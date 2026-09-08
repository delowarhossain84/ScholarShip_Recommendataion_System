<?php

require_once 'config.php';
require_role('provider');

$uid = (int)user()['id'];


/* =========================================================
   GET PROVIDER APPLICATIONS
   ========================================================= */

$st = $conn->prepare(
    "SELECT
        a.*,
        s.title,
        s.amount,
        s.deadline,
        s.provider_name,

        u.name AS student_name,
        u.email,
        u.cgpa,
        u.field_of_study,
        u.education_level,
        u.income,
        u.country

     FROM applications a

     JOIN scholarships s
        ON a.scholarship_id = s.id

     JOIN users u
        ON a.student_id = u.id

     WHERE s.provider_id = ?

     ORDER BY
        CASE
            WHEN a.status = 'Pending' THEN 1
            WHEN a.status = 'Approved' THEN 2
            ELSE 3
        END,
        a.applied_at DESC"
);

$st->bind_param("i", $uid);
$st->execute();

$rs = $st->get_result();


/* =========================================================
   STATISTICS
   ========================================================= */

$total = 0;
$pending = 0;
$approved = 0;
$rejected = 0;

$applications = [];

while ($a = $rs->fetch_assoc()) {

    $applications[] = $a;

    $total++;

    if ($a['status'] === 'Pending') {
        $pending++;
    }

    if ($a['status'] === 'Approved') {
        $approved++;
    }

    if ($a['status'] === 'Rejected') {
        $rejected++;
    }
}


/* =========================================================
   LOAD APPLICATION DOCUMENTS
   ========================================================= */

$documents = [];

$doc_st = $conn->prepare(
    "SELECT
        id,
        application_id,
        document_type,
        file_name,
        uploaded_at
     FROM application_documents
     WHERE application_id = ?
     ORDER BY document_type ASC"
);

foreach ($applications as $application) {

    $application_id = (int)$application['id'];

    $doc_st->bind_param("i", $application_id);

    $doc_st->execute();

    $doc_result = $doc_st->get_result();

    $documents[$application_id] = [];

    while ($doc = $doc_result->fetch_assoc()) {

        $documents[$application_id][] = $doc;
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
        Review Applications | ScholarMatch
    </title>

    <?php include 'partials/head.php'; ?>


    <style>

        /* =====================================================
           PAGE
           ===================================================== */

        .provider-applications-page {

            max-width: 1200px;

            margin: 0 auto;

        }


        /* =====================================================
           HEADER
           ===================================================== */

        .provider-page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;

        }


        .provider-page-header h1 {

            margin: 8px 0;

        }


        /* =====================================================
           STATISTICS
           ===================================================== */

        .provider-stats {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 16px;

            margin-bottom: 28px;

        }


        .provider-stat {

            background: white;

            border: 1px solid #e2e8f0;

            border-radius: 14px;

            padding: 20px;

            box-sizing: border-box;

            min-width: 0;

        }


        .provider-stat-icon {

            font-size: 22px;

            margin-bottom: 8px;

        }


        .provider-stat-label {

            color: #64748b;

            font-size: 13px;

            margin-bottom: 7px;

        }


        .provider-stat-number {

            font-size: 28px;

            font-weight: 800;

            color: #0f172a;

        }


        .provider-stat-total {

            border-left: 4px solid #2563eb;

        }


        .provider-stat-pending {

            border-left: 4px solid #f59e0b;

        }


        .provider-stat-approved {

            border-left: 4px solid #16a34a;

        }


        .provider-stat-rejected {

            border-left: 4px solid #dc2626;

        }


        /* =====================================================
           APPLICATION LIST
           ===================================================== */

        .provider-application-list {

            display: grid;

            gap: 18px;

        }


        /* =====================================================
           APPLICATION CARD
           ===================================================== */

        .provider-application-card {

            background: white;

            border: 1px solid #e2e8f0;

            border-radius: 16px;

            padding: 24px;

            box-sizing: border-box;

            min-width: 0;

        }


        .provider-application-card:hover {

            box-shadow:
                0 8px 25px rgba(15, 23, 42, .08);

        }


        /* =====================================================
           CARD HEADER
           ===================================================== */

        .provider-card-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 20px;

        }


        .student-name {

            font-size: 21px;

            font-weight: 800;

            color: #0f172a;

            margin-bottom: 5px;

        }


        .student-email {

            color: #64748b;

            font-size: 14px;

            overflow-wrap: anywhere;

        }


        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 13px;

            border-radius: 999px;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

            flex-shrink: 0;

        }


        .status-pending {

            background: #fef3c7;

            color: #92400e;

        }


        .status-approved {

            background: #dcfce7;

            color: #166534;

        }


        .status-rejected {

            background: #fee2e2;

            color: #991b1b;

        }


        /* =====================================================
           SCHOLARSHIP
           ===================================================== */

        .scholarship-section {

            background: #f8fafc;

            border-radius: 12px;

            padding: 17px;

            margin-bottom: 20px;

        }


        .scholarship-label {

            color: #64748b;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: .04em;

            margin-bottom: 5px;

        }


        .scholarship-name {

            color: #0f172a;

            font-size: 16px;

            font-weight: 700;

            overflow-wrap: anywhere;

        }


        .scholarship-meta {

            margin-top: 8px;

            color: #64748b;

            font-size: 13px;

        }


        /* =====================================================
           SECTION TITLE
           ===================================================== */

        .section-title {

            margin-top: 22px;

            margin-bottom: 12px;

            color: #0f172a;

            font-size: 17px;

        }


        /* =====================================================
           STUDENT INFORMATION
           ===================================================== */

        .student-info-grid {

            display: grid;

            grid-template-columns:
                repeat(5, minmax(0, 1fr));

            gap: 12px;

            margin-bottom: 20px;

        }


        .student-info {

            border: 1px solid #e2e8f0;

            border-radius: 10px;

            padding: 13px;

            min-width: 0;

            box-sizing: border-box;

        }


        .student-info-label {

            color: #64748b;

            font-size: 11px;

            margin-bottom: 5px;

            text-transform: uppercase;

        }


        .student-info-value {

            color: #0f172a;

            font-weight: 700;

            font-size: 14px;

            overflow-wrap: anywhere;

        }


        /* =====================================================
           APPLICATION DETAILS
           ===================================================== */

        .application-details {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 14px;

            margin-bottom: 20px;

        }


        .detail-box {

            border: 1px solid #e2e8f0;

            border-radius: 12px;

            padding: 15px;

            background: #ffffff;

            min-width: 0;

            box-sizing: border-box;

        }


        .detail-label {

            color: #64748b;

            font-size: 12px;

            font-weight: 700;

            text-transform: uppercase;

            margin-bottom: 7px;

        }


        .detail-value {

            color: #0f172a;

            font-size: 14px;

            line-height: 1.6;

            overflow-wrap: anywhere;

        }


        .score-value {

            font-size: 20px;

            font-weight: 800;

            color: #2563eb;

        }


        .not-provided {

            color: #94a3b8;

            font-style: italic;

        }


        .full-width {

            grid-column: 1 / -1;

        }


        /* =====================================================
           DOCUMENTS
           ===================================================== */

        .documents-box {

            border: 1px solid #e2e8f0;

            border-radius: 12px;

            padding: 16px;

            margin-bottom: 20px;

        }


        .document-list {

            display: grid;

            gap: 10px;

        }


        .document-item {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 12px;

            border: 1px solid #e2e8f0;

            border-radius: 10px;

            background: #f8fafc;

        }


        .document-info {

            min-width: 0;

        }


        .document-type {

            font-weight: 700;

            color: #0f172a;

        }


        .document-name {

            color: #64748b;

            font-size: 13px;

            margin-top: 3px;

            overflow-wrap: anywhere;

        }


        .document-date {

            color: #94a3b8;

            font-size: 11px;

            margin-top: 3px;

        }


        .document-button {

            flex-shrink: 0;

            background: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            padding: 8px 13px;

            font-size: 13px;

            font-weight: 700;

        }


        .document-button:hover {

            background: #1d4ed8;

        }


        .no-documents {

            color: #94a3b8;

            font-size: 14px;

            padding: 5px 0;

        }


        /* =====================================================
           APPLICATION META
           ===================================================== */

        .application-meta {

            border-top: 1px solid #e2e8f0;

            padding-top: 16px;

            display: flex;

            gap: 30px;

            flex-wrap: wrap;

            color: #64748b;

            font-size: 13px;

        }


        .application-meta strong {

            color: #334155;

        }


        /* =====================================================
           ACTIONS
           ===================================================== */

        .provider-actions {

            border-top: 1px solid #e2e8f0;

            margin-top: 18px;

            padding-top: 18px;

            display: flex;

            gap: 10px;

        }


        .provider-actions form {

            margin: 0;

        }


        .approve-button {

            background: #16a34a;

            color: white;

            border: 0;

            cursor: pointer;

        }


        .approve-button:hover {

            background: #15803d;

        }


        .reject-button {

            background: #dc2626;

            color: white;

            border: 0;

            cursor: pointer;

        }


        .reject-button:hover {

            background: #b91c1c;

        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .empty-state {

            text-align: center;

            padding: 55px 25px;

        }


        .empty-icon {

            font-size: 48px;

            margin-bottom: 12px;

        }


        .empty-state h3 {

            margin-bottom: 8px;

        }


        .empty-state p {

            color: #64748b;

            margin-bottom: 22px;

        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1000px) {

            .student-info-grid {

                grid-template-columns:
                    repeat(3, minmax(0, 1fr));

            }

        }


        @media (max-width: 800px) {

            .provider-stats {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }


            .student-info-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }


            .application-details {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 600px) {

            .provider-page-header {

                display: block;

            }


            .provider-page-header .btn {

                display: block;

                width: 100%;

                box-sizing: border-box;

                text-align: center;

                margin-top: 15px;

            }


            .provider-card-header {

                display: block;

            }


            .status-badge {

                margin-top: 12px;

            }


            .provider-actions {

                display: grid;

                grid-template-columns: 1fr;

            }


            .provider-actions form {

                width: 100%;

            }


            .provider-actions .btn {

                width: 100%;

                box-sizing: border-box;

            }


            .document-item {

                display: block;

            }


            .document-button {

                display: inline-block;

                margin-top: 10px;

            }

        }


        @media (max-width: 500px) {

            .provider-stats {

                grid-template-columns: 1fr;

            }


            .student-info-grid {

                grid-template-columns: 1fr;

            }


            .provider-application-card {

                padding: 18px;

            }


            .application-meta {

                display: block;

            }


            .application-meta div {

                margin-bottom: 8px;

            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<main class="container">


    <div class="provider-applications-page">


        <!-- =================================================
             HEADER
             ================================================= -->

        <div class="provider-page-header">

            <div>

                <span class="badge">
                    APPLICATION MANAGEMENT
                </span>

                <h1 class="page-title">
                    Student Applications
                </h1>

                <p class="small">
                    Review and manage applications submitted
                    for your scholarships.
                </p>

            </div>


            

        </div>



        <!-- =================================================
             STATISTICS
             ================================================= -->

        <div class="provider-stats">


            <div class="provider-stat provider-stat-total">

                <div class="provider-stat-icon">
                    📋
                </div>

                <div class="provider-stat-label">
                    Total Applications
                </div>

                <div class="provider-stat-number">
                    <?=$total?>
                </div>

            </div>


            <div class="provider-stat provider-stat-pending">

                <div class="provider-stat-icon">
                    🟡
                </div>

                <div class="provider-stat-label">
                    Pending Review
                </div>

                <div class="provider-stat-number">
                    <?=$pending?>
                </div>

            </div>


            <div class="provider-stat provider-stat-approved">

                <div class="provider-stat-icon">
                    🟢
                </div>

                <div class="provider-stat-label">
                    Approved
                </div>

                <div class="provider-stat-number">
                    <?=$approved?>
                </div>

            </div>


            <div class="provider-stat provider-stat-rejected">

                <div class="provider-stat-icon">
                    🔴
                </div>

                <div class="provider-stat-label">
                    Rejected
                </div>

                <div class="provider-stat-number">
                    <?=$rejected?>
                </div>

            </div>


        </div>



        <!-- =================================================
             APPLICATIONS
             ================================================= -->

        <?php if ($applications): ?>


            <div class="provider-application-list">


                <?php foreach ($applications as $a): ?>


                    <?php

                    /* Determine status */

                    if ($a['status'] === 'Approved') {

                        $statusClass = 'status-approved';

                        $statusIcon = '✓';

                    }

                    elseif ($a['status'] === 'Rejected') {

                        $statusClass = 'status-rejected';

                        $statusIcon = '✕';

                    }

                    else {

                        $statusClass = 'status-pending';

                        $statusIcon = '⏳';

                    }

                    ?>


                    <div class="provider-application-card">


                        <!-- =================================================
                             CARD HEADER
                             ================================================= -->

                        <div class="provider-card-header">

                            <div>

                                <div class="student-name">

                                    👨‍🎓
                                    <?=e($a['student_name'])?>

                                </div>


                                <div class="student-email">

                                    📧
                                    <?=e($a['email'])?>

                                </div>

                            </div>


                            <span
                                class="status-badge <?=$statusClass?>"
                            >

                                <?=$statusIcon?>

                                <?=e($a['status'])?>

                            </span>

                        </div>



                        <!-- =================================================
                             SCHOLARSHIP
                             ================================================= -->

                        <div class="scholarship-section">

                            <div class="scholarship-label">
                                Applied Scholarship
                            </div>


                            <div class="scholarship-name">

                                <?=e($a['title'])?>

                            </div>


                            <div class="scholarship-meta">

                                Amount:

                                <strong>
                                    ৳<?=number_format(
                                        (float)$a['amount']
                                    )?>
                                </strong>

                                &nbsp; · &nbsp;

                                Deadline:

                                <strong>
                                    <?=e($a['deadline'])?>
                                </strong>

                            </div>

                        </div>



                        <!-- =================================================
                             STUDENT PROFILE
                             ================================================= -->

                        <h3 class="section-title">
                            👤 Student Profile
                        </h3>


                        <div class="student-info-grid">


                            <div class="student-info">

                                <div class="student-info-label">
                                    CGPA
                                </div>

                                <div class="student-info-value">

                                    <?=e(
                                        $a['cgpa']
                                        ?: 'Not provided'
                                    )?>

                                </div>

                            </div>


                            <div class="student-info">

                                <div class="student-info-label">
                                    Education
                                </div>

                                <div class="student-info-value">

                                    <?=e(
                                        $a['education_level']
                                        ?: 'Not provided'
                                    )?>

                                </div>

                            </div>


                            <div class="student-info">

                                <div class="student-info-label">
                                    Field
                                </div>

                                <div class="student-info-value">

                                    <?=e(
                                        $a['field_of_study']
                                        ?: 'Not provided'
                                    )?>

                                </div>

                            </div>


                            <div class="student-info">

                                <div class="student-info-label">
                                    Income
                                </div>

                                <div class="student-info-value">

                                    <?php if (
                                        $a['income'] !== null
                                    ): ?>

                                        ৳<?=number_format(
                                            (float)$a['income']
                                        )?>

                                    <?php else: ?>

                                        Not provided

                                    <?php endif; ?>

                                </div>

                            </div>


                            <div class="student-info">

                                <div class="student-info-label">
                                    Country
                                </div>

                                <div class="student-info-value">

                                    <?=e(
                                        $a['country']
                                        ?: 'Not provided'
                                    )?>

                                </div>

                            </div>


                        </div>



                        <!-- =================================================
                             APPLICATION DETAILS
                             ================================================= -->

                        <h3 class="section-title">
                            📝 Application Details
                        </h3>


                        <div class="application-details">


                            <!-- IELTS -->

                            <div class="detail-box">

                                <div class="detail-label">
                                    IELTS Score
                                </div>

                                <div class="detail-value">

                                    <?php if (
                                        $a['ielts_score'] !== null
                                        && $a['ielts_score'] !== ''
                                    ): ?>

                                        <span class="score-value">
                                            <?=e($a['ielts_score'])?>
                                        </span>

                                        <span>
                                            / 9.0
                                        </span>

                                    <?php else: ?>

                                        <span class="not-provided">
                                            Not provided
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>



                            <!-- GRE -->

                            <div class="detail-box">

                                <div class="detail-label">
                                    GRE Score
                                </div>

                                <div class="detail-value">

                                    <?php if (
                                        $a['gre_score'] !== null
                                        && $a['gre_score'] !== ''
                                    ): ?>

                                        <span class="score-value">
                                            <?=e($a['gre_score'])?>
                                        </span>

                                        <span>
                                            / 340
                                        </span>

                                    <?php else: ?>

                                        <span class="not-provided">
                                            Not provided
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>



                            <!-- EXTRACURRICULAR -->

                            <div class="detail-box full-width">

                                <div class="detail-label">
                                    Extracurricular Activities
                                </div>

                                <div class="detail-value">

                                    <?php if (
                                        !empty($a['extracurricular'])
                                    ): ?>

                                        <?=nl2br(
                                            e($a['extracurricular'])
                                        )?>

                                    <?php else: ?>

                                        <span class="not-provided">
                                            Not provided
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>



                            <!-- PERSONAL STATEMENT -->

                            <div class="detail-box full-width">

                                <div class="detail-label">
                                    Personal Statement
                                </div>

                                <div class="detail-value">

                                    <?php if (
                                        !empty($a['personal_statement'])
                                    ): ?>

                                        <?=nl2br(
                                            e($a['personal_statement'])
                                        )?>

                                    <?php else: ?>

                                        <span class="not-provided">
                                            Not provided
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>


                        </div>



                        <!-- =================================================
                             DOCUMENTS
                             ================================================= -->

                        <h3 class="section-title">
                            📄 Submitted Documents
                        </h3>


                        <div class="documents-box">


                            <?php
                            $application_id = (int)$a['id'];

                            $application_documents =
                                $documents[$application_id] ?? [];
                            ?>


                            <?php if ($application_documents): ?>


                                <div class="document-list">


                                    <?php foreach (
                                        $application_documents
                                        as $doc
                                    ): ?>


                                        <div class="document-item">


                                            <div class="document-info">

                                                <div class="document-type">

                                                    📄
                                                    <?=e(
                                                        $doc['document_type']
                                                    )?>

                                                </div>


                                                <div class="document-name">

                                                    <?=e(
                                                        $doc['file_name']
                                                    )?>

                                                </div>


                                                <div class="document-date">

                                                    Uploaded:
                                                    <?=e(
                                                        $doc['uploaded_at']
                                                    )?>

                                                </div>

                                            </div>


                                            <a
                                                class="document-button"
                                                href="download_document.php?id=<?=$doc['id']?>"
                                                target="_blank"
                                                rel="noopener"
                                            >

                                                View PDF

                                            </a>


                                        </div>


                                    <?php endforeach; ?>


                                </div>


                            <?php else: ?>


                                <div class="no-documents">

                                    No documents were submitted
                                    with this application.

                                </div>


                            <?php endif; ?>


                        </div>



                        <!-- =================================================
                             APPLICATION META
                             ================================================= -->

                        <div class="application-meta">


                            <div>

                                Applied:

                                <strong>
                                    <?=e($a['applied_at'])?>
                                </strong>

                            </div>


                            <div>

                                Last Updated:

                                <strong>

                                    <?=e(
                                        $a['updated_at']
                                        ?? $a['applied_at']
                                    )?>

                                </strong>

                            </div>


                        </div>



                        <!-- =================================================
                             ACTIONS
                             ================================================= -->

                        <?php if ($a['status'] === 'Pending'): ?>


                            <div class="provider-actions">


                                <!-- APPROVE -->

                                <form
                                    method="post"
                                    action="update_application.php"
                                    onsubmit="return confirm(
                                        'Are you sure you want to approve this application?'
                                    );"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?=csrf()?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?=$a['id']?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="status"
                                        value="Approved"
                                    >


                                    <button
                                        type="submit"
                                        class="btn approve-button"
                                    >

                                        ✓ Approve Application

                                    </button>

                                </form>



                                <!-- REJECT -->

                                <form
                                    method="post"
                                    action="update_application.php"
                                    onsubmit="return confirm(
                                        'Are you sure you want to reject this application?'
                                    );"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?=csrf()?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?=$a['id']?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="status"
                                        value="Rejected"
                                    >


                                    <button
                                        type="submit"
                                        class="btn reject-button"
                                    >

                                        ✕ Reject Application

                                    </button>

                                </form>


                            </div>


                        <?php else: ?>


                            <div
                                class="small"
                                style="margin-top:18px"
                            >

                                This application has already been
                                <?=strtolower(
                                    e($a['status'])
                                )?>.

                            </div>


                        <?php endif; ?>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- =================================================
                 EMPTY STATE
                 ================================================= -->

            <div class="panel empty-state">


                <div class="empty-icon">
                    📋
                </div>


                <h3>
                    No Applications Yet
                </h3>


                <p>
                    Students have not applied to your scholarships yet.
                </p>


                <a
                    class="btn"
                    href="provider_scholarships.php"
                >

                    Manage Scholarships

                </a>


            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>