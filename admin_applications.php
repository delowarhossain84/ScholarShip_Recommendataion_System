<?php
require_once 'config.php';
require_role('admin');


/*
|--------------------------------------------------------------------------
| Search and Filter
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$status = $_GET['status'] ?? '';


/*
|--------------------------------------------------------------------------
| Build Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        a.*,

        u.name AS student_name,
        u.email AS student_email,
        u.education_level,
        u.field_of_study,
        u.cgpa,
        u.income,
        u.country,

        s.title AS scholarship_title,
        s.provider_name,
        s.amount,
        s.deadline,

        p.name AS provider_account,
        p.email AS provider_email

    FROM applications a

    JOIN users u
        ON a.student_id = u.id

    JOIN scholarships s
        ON a.scholarship_id = s.id

    JOIN users p
        ON s.provider_id = p.id

    WHERE 1=1
";


$params = [];

$types = '';


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
            OR s.title LIKE ?
            OR s.provider_name LIKE ?
        )
    ";

    $search_value = "%{$search}%";

    $params[] = $search_value;

    $params[] = $search_value;

    $params[] = $search_value;

    $params[] = $search_value;

    $types .= 'ssss';

}


/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if (
    in_array(
        $status,
        ['Pending', 'Approved', 'Rejected'],
        true
    )
) {

    $sql .= "
        AND a.status = ?
    ";

    $params[] = $status;

    $types .= 's';

}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY a.applied_at DESC
";


$stmt = $conn->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();

$applications = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications"
);

$total_applications =
    (int)$result->fetch_assoc()['total'];


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE status='Pending'"
);

$pending =
    (int)$result->fetch_assoc()['total'];


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE status='Approved'"
);

$approved =
    (int)$result->fetch_assoc()['total'];


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE status='Rejected'"
);

$rejected =
    (int)$result->fetch_assoc()['total'];

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Applications</title>

    <?php include 'partials/head.php'; ?>


    <style>

        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 25px;

            flex-wrap: wrap;

        }


        .page-subtitle {

            color: #64748B;

            margin-top: 6px;

        }


        .btn {

            border: none;

            border-radius: 9px;

            padding: 10px 15px;

            font-weight: 700;

            cursor: pointer;

            text-decoration: none;

            display: inline-block;

        }


        .btn-secondary {

            background: #E2E8F0;

            color: #334155;

        }


        .btn-primary {

            background: #2563EB;

            color: #FFFFFF;

        }


        .btn-success {

            background: #16A34A;

            color: #FFFFFF;

        }


        .btn-danger {

            background: #DC2626;

            color: #FFFFFF;

        }


        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 16px;

            margin-bottom: 25px;

        }


        .stat-card {

            background: #FFFFFF;

            border: 1px solid #E2E8F0;

            border-radius: 15px;

            padding: 20px;

        }


        .stat-label {

            color: #64748B;

            font-size: 13px;

            margin-bottom: 6px;

        }


        .stat-number {

            color: #0F172A;

            font-size: 28px;

            font-weight: 800;

        }


        .filter-box {

            background: #FFFFFF;

            border: 1px solid #E2E8F0;

            border-radius: 15px;

            padding: 18px;

            margin-bottom: 25px;

        }


        .filter-form {

            display: flex;

            gap: 12px;

            align-items: center;

            flex-wrap: wrap;

        }


        .filter-form input,
        .filter-form select {

            padding: 11px 13px;

            border: 1px solid #CBD5E1;

            border-radius: 9px;

            font-size: 14px;

            outline: none;

        }


        .filter-form input {

            min-width: 300px;

        }


        .application-list {

            display: grid;

            gap: 20px;

        }


        .application-card {

            background: #FFFFFF;

            border: 1px solid #E2E8F0;

            border-radius: 16px;

            padding: 22px;

            box-shadow:
                0 5px 18px
                rgba(15, 23, 42, 0.05);

        }


        .application-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 15px;

            margin-bottom: 18px;

        }


        .student-name {

            font-size: 19px;

            font-weight: 800;

            color: #0F172A;

        }


        .student-email {

            color: #64748B;

            font-size: 13px;

            margin-top: 4px;

        }


        .scholarship-name {

            color: #2563EB;

            font-size: 15px;

            font-weight: 750;

            margin-top: 9px;

        }


        .status-badge {

            display: inline-block;

            padding: 6px 11px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 800;

        }


        .status-pending {

            background: #FEF3C7;

            color: #92400E;

        }


        .status-approved {

            background: #DCFCE7;

            color: #166534;

        }


        .status-rejected {

            background: #FEE2E2;

            color: #991B1B;

        }


        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 10px;

            margin-bottom: 18px;

        }


        .info-item {

            background: #F8FAFC;

            border: 1px solid #E2E8F0;

            border-radius: 10px;

            padding: 11px;

        }


        .info-label {

            color: #64748B;

            font-size: 11px;

            margin-bottom: 5px;

        }


        .info-value {

            color: #0F172A;

            font-weight: 750;

            font-size: 14px;

            word-break: break-word;

        }


        .detail-section {

            border-top: 1px solid #E2E8F0;

            padding-top: 17px;

            margin-top: 17px;

        }


        .detail-title {

            color: #0F172A;

            font-weight: 800;

            margin-bottom: 9px;

        }


        .detail-text {

            color: #475569;

            font-size: 14px;

            line-height: 1.7;

            white-space: pre-wrap;

        }


        .provider-box {

            background: #EFF6FF;

            border-radius: 10px;

            padding: 13px;

            color: #334155;

            font-size: 13px;

            line-height: 1.7;

        }


        .provider-box strong {

            color: #1D4ED8;

        }


        .documents {

            display: flex;

            gap: 8px;

            flex-wrap: wrap;

        }


        .document-link {

            display: inline-block;

            padding: 8px 11px;

            background: #F1F5F9;

            border: 1px solid #CBD5E1;

            border-radius: 8px;

            color: #1D4ED8;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

        }


        .review-box {

            border-top: 1px solid #E2E8F0;

            margin-top: 20px;

            padding-top: 18px;

        }


        .review-title {

            font-weight: 800;

            color: #0F172A;

            margin-bottom: 10px;

        }


        .review-actions {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }


        .review-actions form {

            margin: 0;

        }


        .application-footer {

            border-top: 1px solid #E2E8F0;

            margin-top: 18px;

            padding-top: 15px;

            display: flex;

            justify-content: space-between;

            gap: 10px;

            color: #64748B;

            font-size: 12px;

            flex-wrap: wrap;

        }


        .empty-state {

            background: #FFFFFF;

            border: 1px solid #E2E8F0;

            border-radius: 16px;

            padding: 55px 20px;

            text-align: center;

            color: #64748B;

        }


        @media (max-width: 1000px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .info-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 650px) {

            .stats-grid {

                grid-template-columns: 1fr;

            }


            .info-grid {

                grid-template-columns: 1fr;

            }


            .filter-form {

                flex-direction: column;

                align-items: stretch;

            }


            .filter-form input,
            .filter-form select,
            .filter-form .btn {

                width: 100%;

            }


            .application-header {

                flex-direction: column;

            }


            .review-actions {

                flex-direction: column;

            }


            .review-actions .btn {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<div class="container">


    <!-- HEADER -->

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Application Management
            </h1>

            <p class="page-subtitle">
                Review and manage all scholarship applications.
            </p>

        </div>


      

    </div>


    <!-- STATISTICS -->

    <div class="stats-grid">


        <div class="stat-card">

            <div class="stat-label">
                Total Applications
            </div>

            <div class="stat-number">
                <?= $total_applications ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Pending
            </div>

            <div class="stat-number">
                <?= $pending ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Approved
            </div>

            <div class="stat-number">
                <?= $approved ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Rejected
            </div>

            <div class="stat-number">
                <?= $rejected ?>
            </div>

        </div>

    </div>


    <!-- FILTER -->

    <div class="filter-box">

        <form
            method="GET"
            class="filter-form"
        >


            <input
                type="text"
                name="search"
                placeholder="Search student, scholarship or provider..."
                value="<?= e($search) ?>"
            >


            <select name="status">

                <option value="">
                    All Statuses
                </option>

                <option
                    value="Pending"
                    <?= $status === 'Pending'
                        ? 'selected'
                        : '' ?>
                >
                    Pending
                </option>

                <option
                    value="Approved"
                    <?= $status === 'Approved'
                        ? 'selected'
                        : '' ?>
                >
                    Approved
                </option>

                <option
                    value="Rejected"
                    <?= $status === 'Rejected'
                        ? 'selected'
                        : '' ?>
                >
                    Rejected
                </option>

            </select>


            <button
                type="submit"
                class="btn btn-primary"
            >
                Search
            </button>


            <a
                href="admin_applications.php"
                class="btn btn-secondary"
            >
                Reset
            </a>


        </form>

    </div>


    <!-- APPLICATIONS -->

    <?php if ($applications->num_rows > 0): ?>


        <div class="application-list">


            <?php while (
                $a = $applications->fetch_assoc()
            ): ?>


                <?php

                if (
                    $a['status'] === 'Pending'
                ) {

                    $status_class =
                        'status-pending';

                } elseif (
                    $a['status'] === 'Approved'
                ) {

                    $status_class =
                        'status-approved';

                } else {

                    $status_class =
                        'status-rejected';

                }

                ?>


                <div class="application-card">


                    <!-- APPLICATION HEADER -->

                    <div class="application-header">


                        <div>

                            <div class="student-name">

                                <?= e(
                                    $a['student_name']
                                ) ?>

                            </div>


                            <div class="student-email">

                                <?= e(
                                    $a['student_email']
                                ) ?>

                            </div>


                            <div class="scholarship-name">

                                🎓
                                <?= e(
                                    $a['scholarship_title']
                                ) ?>

                            </div>

                        </div>


                        <span
                            class="status-badge
                            <?= $status_class ?>"
                        >

                            <?= e(
                                $a['status']
                            ) ?>

                        </span>


                    </div>


                    <!-- STUDENT INFORMATION -->

                    <div class="info-grid">


                        <div class="info-item">

                            <div class="info-label">
                                CGPA
                            </div>

                            <div class="info-value">
                                <?= e(
                                    $a['cgpa'] ?? '-'
                                ) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                IELTS
                            </div>

                            <div class="info-value">
                                <?= e(
                                    $a['ielts_score'] ?? '-'
                                ) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                GRE
                            </div>

                            <div class="info-value">
                                <?= e(
                                    $a['gre_score'] ?? '-'
                                ) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Education
                            </div>

                            <div class="info-value">
                                <?= e(
                                    $a['education_level'] ?? '-'
                                ) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Field
                            </div>

                            <div class="info-value">
                                <?= e(
                                    $a['field_of_study'] ?? '-'
                                ) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Country
                            </div>

                            <div class="info-value">
                                <?= e(
                                    $a['country'] ?? '-'
                                ) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Income
                            </div>

                            <div class="info-value">
                                <?= e(
                                    $a['income'] ?? '-'
                                ) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Scholarship Amount
                            </div>

                            <div class="info-value">

                                <?= number_format(
                                    (float)$a['amount'],
                                    2
                                ) ?>

                            </div>

                        </div>


                    </div>


                    <!-- PROVIDER -->

                    <div class="detail-section">

                        <div class="detail-title">
                            Scholarship Provider
                        </div>


                        <div class="provider-box">

                            <strong>
                                Organization:
                            </strong>

                            <?= e(
                                $a['provider_name']
                            ) ?>

                            <br>


                            <strong>
                                Account:
                            </strong>

                            <?= e(
                                $a['provider_account']
                            ) ?>

                            <br>


                            <strong>
                                Email:
                            </strong>

                            <?= e(
                                $a['provider_email']
                            ) ?>

                        </div>

                    </div>


                    <!-- EXTRACURRICULAR -->

                    <?php if (
                        !empty(
                            $a['extracurricular']
                        )
                    ): ?>

                        <div class="detail-section">

                            <div class="detail-title">
                                Extracurricular Activities
                            </div>

                            <div class="detail-text">

                                <?= e(
                                    $a['extracurricular']
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- PERSONAL STATEMENT -->

                    <?php if (
                        !empty(
                            $a['personal_statement']
                        )
                    ): ?>

                        <div class="detail-section">

                            <div class="detail-title">
                                Personal Statement
                            </div>

                            <div class="detail-text">

                                <?= e(
                                    $a['personal_statement']
                                ) ?>

                            </div>

                        </div>

                    <?php endif; ?>


                    <!-- DOCUMENTS -->

                    <?php

                    $doc_stmt = $conn->prepare(
                        "SELECT
                            id,
                            document_type,
                            file_name,
                            uploaded_at

                         FROM application_documents

                         WHERE application_id = ?

                         ORDER BY document_type ASC"
                    );


                    $doc_stmt->bind_param(
                        "i",
                        $a['id']
                    );


                    $doc_stmt->execute();


                    $documents =
                        $doc_stmt->get_result();

                    ?>


                    <?php if (
                        $documents->num_rows > 0
                    ): ?>


                        <div class="detail-section">


                            <div class="detail-title">
                                Application Documents
                            </div>


                            <div class="documents">


                                <?php while (
                                    $doc =
                                    $documents->fetch_assoc()
                                ): ?>


                                    <a
                                        href="download_document.php?id=<?= (int)$doc['id'] ?>"
                                        target="_blank"
                                        class="document-link"
                                    >

                                        📄
                                        <?= e(
                                            $doc['document_type']
                                        ) ?>

                                    </a>


                                <?php endwhile; ?>


                            </div>


                        </div>


                    <?php endif; ?>


                    <!-- ADMIN REVIEW -->

                    <?php if (
                        $a['status'] === 'Pending'
                    ): ?>


                        <div class="review-box">


                            <div class="review-title">

                                Admin Review

                            </div>


                            <div class="review-actions">


                                <!-- APPROVE -->

                                <form
                                    method="POST"
                                    action="admin_update_application.php"
                                    onsubmit="return confirm(
                                        'Are you sure you want to approve this application?'
                                    );"
                                >


                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$a['id'] ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="status"
                                        value="Approved"
                                    >


                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf()) ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="btn btn-success"
                                    >
                                        ✓ Approve Application
                                    </button>


                                </form>


                                <!-- REJECT -->

                                <form
                                    method="POST"
                                    action="admin_update_application.php"
                                    onsubmit="return confirm(
                                        'Are you sure you want to reject this application?'
                                    );"
                                >


                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$a['id'] ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="status"
                                        value="Rejected"
                                    >


                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e(csrf()) ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                    >
                                        ✕ Reject Application
                                    </button>


                                </form>


                            </div>


                        </div>


                    <?php endif; ?>


                    <!-- FOOTER -->

                    <div class="application-footer">


                        <span>

                            Applied:
                            <?= e(
                                $a['applied_at']
                            ) ?>

                        </span>


                        <span>

                            Updated:
                            <?= e(
                                $a['updated_at']
                            ) ?>

                        </span>


                        <span>

                            Deadline:
                            <?= e(
                                $a['deadline']
                            ) ?>

                        </span>


                    </div>


                </div>


            <?php endwhile; ?>


        </div>


    <?php else: ?>


        <div class="empty-state">

            <h3>
                No applications found
            </h3>

            <p>
                Try changing your search or status filter.
            </p>

        </div>


    <?php endif; ?>


</div>


</body>

</html>