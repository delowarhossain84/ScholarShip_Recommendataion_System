<?php

require_once 'config.php';
require_role('student');

$uid = (int)user()['id'];


/* =========================================================
   GET STUDENT APPLICATIONS
   ========================================================= */

$st = $conn->prepare(
    "SELECT
        a.*,
        s.title,
        s.amount,
        s.deadline,
        s.provider_name,
        s.description
     FROM applications a
     JOIN scholarships s
        ON a.scholarship_id = s.id
     WHERE a.student_id = ?
     ORDER BY a.applied_at DESC"
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
   GET APPLICATION DOCUMENTS
   ========================================================= */

$documents = [];

if ($applications) {

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

    $doc_st->close();
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
        My Applications | ScholarMatch
    </title>

    <?php include 'partials/head.php'; ?>


    <style>

        /* =====================================================
           PAGE
           ===================================================== */

        .applications-page {

            max-width: 1150px;

            margin: 0 auto;

        }


        /* =====================================================
           HEADER
           ===================================================== */

        .applications-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;

        }


        .applications-header h1 {

            margin: 8px 0;

        }


        /* =====================================================
           STATISTICS
           ===================================================== */

        .application-stats {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 16px;

            margin-bottom: 28px;

        }


        .stat-card {

            background: white;

            border: 1px solid #e2e8f0;

            border-radius: 14px;

            padding: 20px;

            box-sizing: border-box;

            min-width: 0;

        }


        .stat-label {

            font-size: 13px;

            color: #64748b;

            margin-bottom: 8px;

        }


        .stat-number {

            font-size: 28px;

            font-weight: 800;

            color: #0f172a;

        }


        .stat-icon {

            font-size: 22px;

            margin-bottom: 8px;

        }


        .stat-pending {

            border-left: 4px solid #f59e0b;

        }


        .stat-approved {

            border-left: 4px solid #16a34a;

        }


        .stat-rejected {

            border-left: 4px solid #dc2626;

        }


        .stat-total {

            border-left: 4px solid #2563eb;

        }


        /* =====================================================
           APPLICATION LIST
           ===================================================== */

        .application-list {

            display: grid;

            gap: 18px;

        }


        /* =====================================================
           APPLICATION CARD
           ===================================================== */

        .application-card {

            background: white;

            border: 1px solid #e2e8f0;

            border-radius: 16px;

            padding: 22px;

            box-sizing: border-box;

            transition: .2s ease;

        }


        .application-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 25px rgba(15, 23, 42, .08);

        }


        /* =====================================================
           CARD HEADER
           ===================================================== */

        .application-card-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 15px;

            margin-bottom: 18px;

        }


        .application-card h3 {

            margin: 0 0 7px;

            color: #0f172a;

            font-size: 20px;

            overflow-wrap: anywhere;

        }


        .provider {

            color: #64748b;

            font-size: 14px;

        }


        /* =====================================================
           STATUS
           ===================================================== */

        .application-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 12px;

            border-radius: 999px;

            font-size: 13px;

            font-weight: 700;

            white-space: nowrap;

            flex-shrink: 0;

        }


        .status-pending {

            color: #92400e;

            background: #fef3c7;

        }


        .status-approved {

            color: #166534;

            background: #dcfce7;

        }


        .status-rejected {

            color: #991b1b;

            background: #fee2e2;

        }


        /* =====================================================
           STATUS MESSAGE
           ===================================================== */

        .status-message {

            padding: 13px 15px;

            border-radius: 10px;

            margin-bottom: 18px;

            font-size: 14px;

            line-height: 1.5;

        }


        .message-pending {

            background: #fffbeb;

            color: #92400e;

            border: 1px solid #fde68a;

        }


        .message-approved {

            background: #f0fdf4;

            color: #166534;

            border: 1px solid #bbf7d0;

        }


        .message-rejected {

            background: #fef2f2;

            color: #991b1b;

            border: 1px solid #fecaca;

        }


        /* =====================================================
           SCHOLARSHIP INFO
           ===================================================== */

        .application-info {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 12px;

            margin-bottom: 20px;

        }


        .info-box {

            background: #f8fafc;

            border-radius: 10px;

            padding: 13px;

            min-width: 0;

            box-sizing: border-box;

        }


        .info-label {

            color: #64748b;

            font-size: 11px;

            margin-bottom: 5px;

            text-transform: uppercase;

            letter-spacing: .03em;

        }


        .info-value {

            color: #0f172a;

            font-size: 14px;

            font-weight: 700;

            overflow-wrap: anywhere;

        }


        /* =====================================================
           DETAILS
           ===================================================== */

        .section-title {

            margin: 22px 0 12px;

            color: #0f172a;

            font-size: 17px;

        }


        .details-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 12px;

            margin-bottom: 20px;

        }


        .detail-box {

            border: 1px solid #e2e8f0;

            border-radius: 11px;

            padding: 14px;

            min-width: 0;

            box-sizing: border-box;

        }


        .detail-label {

            color: #64748b;

            font-size: 11px;

            font-weight: 700;

            text-transform: uppercase;

            margin-bottom: 6px;

        }


        .detail-value {

            color: #0f172a;

            font-size: 14px;

            line-height: 1.6;

            overflow-wrap: anywhere;

        }


        .score {

            color: #2563eb;

            font-size: 20px;

            font-weight: 800;

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

            padding: 15px;

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

            background: #f8fafc;

            border: 1px solid #e2e8f0;

            border-radius: 10px;

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

        }


        /* =====================================================
           TIMELINE
           ===================================================== */

        .timeline {

            border-top: 1px solid #e2e8f0;

            padding-top: 16px;

            display: flex;

            justify-content: space-between;

            gap: 15px;

        }


        .timeline-item {

            font-size: 13px;

            color: #64748b;

        }


        .timeline-item strong {

            display: block;

            color: #334155;

            margin-top: 4px;

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

        @media (max-width: 900px) {

            .application-stats {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }


            .application-info {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }

        }


        @media (max-width: 700px) {

            .applications-header {

                display: block;

            }


            .applications-header .btn {

                display: block;

                width: 100%;

                box-sizing: border-box;

                text-align: center;

                margin-top: 15px;

            }


            .application-card-header {

                display: block;

            }


            .application-status {

                margin-top: 12px;

            }


            .details-grid {

                grid-template-columns: 1fr;

            }


            .timeline {

                display: block;

            }


            .timeline-item {

                margin-bottom: 10px;

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

            .application-stats {

                grid-template-columns: 1fr;

            }


            .application-info {

                grid-template-columns: 1fr;

            }


            .application-card {

                padding: 17px;

            }


            .application-card h3 {

                font-size: 18px;

            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<main class="container">


    <div class="applications-page">


        <!-- =================================================
             HEADER
             ================================================= -->

        <div class="applications-header">

            <div>

                <span class="badge">
                    APPLICATION TRACKING
                </span>

                <h1 class="page-title">
                    My Applications
                </h1>

                <p class="small">
                    Track the status and details of all your
                    scholarship applications.
                </p>

            </div>


            <a
                class="btn secondary"
                href="scholarships.php"
            >
                Browse Scholarships
            </a>

        </div>



        <!-- =================================================
             STATISTICS
             ================================================= -->

        <div class="application-stats">


            <div class="stat-card stat-total">

                <div class="stat-icon">
                    📋
                </div>

                <div class="stat-label">
                    Total Applications
                </div>

                <div class="stat-number">
                    <?=$total?>
                </div>

            </div>


            <div class="stat-card stat-pending">

                <div class="stat-icon">
                    🟡
                </div>

                <div class="stat-label">
                    Pending
                </div>

                <div class="stat-number">
                    <?=$pending?>
                </div>

            </div>


            <div class="stat-card stat-approved">

                <div class="stat-icon">
                    🟢
                </div>

                <div class="stat-label">
                    Approved
                </div>

                <div class="stat-number">
                    <?=$approved?>
                </div>

            </div>


            <div class="stat-card stat-rejected">

                <div class="stat-icon">
                    🔴
                </div>

                <div class="stat-label">
                    Rejected
                </div>

                <div class="stat-number">
                    <?=$rejected?>
                </div>

            </div>


        </div>



        <!-- =================================================
             APPLICATION LIST
             ================================================= -->

        <?php if ($applications): ?>


            <div class="application-list">


                <?php foreach ($applications as $a): ?>


                    <?php

                    /* Determine status */

                    if ($a['status'] === 'Approved') {

                        $statusClass = 'status-approved';

                        $statusIcon = '✓';

                        $messageClass =
                            'message-approved';

                        $message =
                            'Congratulations! Your application has been approved.';

                    }

                    elseif ($a['status'] === 'Rejected') {

                        $statusClass = 'status-rejected';

                        $statusIcon = '✕';

                        $messageClass =
                            'message-rejected';

                        $message =
                            'Unfortunately, your application has been rejected.';

                    }

                    else {

                        $statusClass = 'status-pending';

                        $statusIcon = '⏳';

                        $messageClass =
                            'message-pending';

                        $message =
                            'Your application is currently under review by the scholarship provider.';

                    }

                    ?>


                    <!-- =================================================
                         APPLICATION CARD
                         ================================================= -->

                    <div class="application-card">


                        <!-- CARD HEADER -->

                        <div class="application-card-header">

                            <div>

                                <h3>
                                    <?=e($a['title'])?>
                                </h3>


                                <div class="provider">

                                    🏢
                                    <?=e($a['provider_name'])?>

                                </div>

                            </div>


                            <span
                                class="application-status <?=$statusClass?>"
                            >

                                <?=$statusIcon?>

                                <?=e($a['status'])?>

                            </span>

                        </div>



                        <!-- STATUS -->

                        <div
                            class="status-message <?=$messageClass?>"
                        >

                            <?=$message?>

                        </div>



                        <!-- SCHOLARSHIP INFORMATION -->

                        <div class="application-info">


                            <div class="info-box">

                                <div class="info-label">
                                    Amount
                                </div>

                                <div class="info-value">

                                    ৳<?=number_format(
                                        (float)$a['amount']
                                    )?>

                                </div>

                            </div>


                            <div class="info-box">

                                <div class="info-label">
                                    Deadline
                                </div>

                                <div class="info-value">

                                    <?=e($a['deadline'])?>

                                </div>

                            </div>


                            <div class="info-box">

                                <div class="info-label">
                                    Applied
                                </div>

                                <div class="info-value">

                                    <?=e(
                                        date(
                                            'Y-m-d',
                                            strtotime(
                                                $a['applied_at']
                                            )
                                        )
                                    )?>

                                </div>

                            </div>


                            <div class="info-box">

                                <div class="info-label">
                                    Last Updated
                                </div>

                                <div class="info-value">

                                    <?=e(
                                        date(
                                            'Y-m-d',
                                            strtotime(
                                                $a['updated_at']
                                            )
                                        )
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


                        <div class="details-grid">


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

                                        <span class="score">
                                            <?=e($a['ielts_score'])?>
                                        </span>

                                        / 9.0

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

                                        <span class="score">
                                            <?=e($a['gre_score'])?>
                                        </span>

                                        / 340

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
                                        !empty(
                                            $a['extracurricular']
                                        )
                                    ): ?>

                                        <?=nl2br(
                                            e(
                                                $a['extracurricular']
                                            )
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
                                        !empty(
                                            $a['personal_statement']
                                        )
                                    ): ?>

                                        <?=nl2br(
                                            e(
                                                $a['personal_statement']
                                            )
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
                             SUBMITTED DOCUMENTS
                             ================================================= -->

                        <h3 class="section-title">
                            📄 Submitted Documents
                        </h3>


                        <div class="documents-box">


                            <?php
                            $application_id =
                                (int)$a['id'];

                            $application_documents =
                                $documents[$application_id]
                                ?? [];
                            ?>


                            <?php if (
                                $application_documents
                            ): ?>


                                <div class="document-list">


                                    <?php foreach (
                                        $application_documents
                                        as $doc
                                    ): ?>


                                        <div
                                            class="document-item"
                                        >


                                            <div
                                                class="document-info"
                                            >

                                                <div
                                                    class="document-type"
                                                >

                                                    📄
                                                    <?=e(
                                                        $doc[
                                                            'document_type'
                                                        ]
                                                    )?>

                                                </div>


                                                <div
                                                    class="document-name"
                                                >

                                                    <?=e(
                                                        $doc[
                                                            'file_name'
                                                        ]
                                                    )?>

                                                </div>


                                                <div
                                                    class="document-date"
                                                >

                                                    Uploaded:
                                                    <?=e(
                                                        $doc[
                                                            'uploaded_at'
                                                        ]
                                                    )?>

                                                </div>

                                            </div>


                                            <a
                                                class="document-button"
                                                href="download_document.php?id=<?=$doc['id']?>"
                                                target="_blank"
                                                rel="noopener"
                                            >

                                                👁 View PDF

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
                             TIMELINE
                             ================================================= -->

                        <div class="timeline">


                            <div class="timeline-item">

                                Application Submitted

                                <strong>
                                    <?=e(
                                        $a['applied_at']
                                    )?>
                                </strong>

                            </div>


                            <div class="timeline-item">

                                Current Status

                                <strong>
                                    <?=e(
                                        $a['status']
                                    )?>
                                </strong>

                            </div>


                            <div class="timeline-item">

                                Last Updated

                                <strong>
                                    <?=e(
                                        $a['updated_at']
                                    )?>
                                </strong>

                            </div>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- =================================================
                 EMPTY STATE
                 ================================================= -->

            <div class="panel empty-state">


                <div class="empty-icon">
                    📄
                </div>


                <h3>
                    No Applications Yet
                </h3>


                <p>
                    You haven't applied for any scholarships yet.
                    Find a scholarship that matches your profile
                    and start your application.
                </p>


                <a
                    class="btn"
                    href="recommendations.php"
                >

                    🎯 Find Recommended Scholarships

                </a>


            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>