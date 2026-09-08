<?php
require_once 'config.php';
require_role('admin');


/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

// Total users
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM users"
);
$total_users = (int)$result->fetch_assoc()['total'];


// Total students
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'"
);
$total_students = (int)$result->fetch_assoc()['total'];


// Total providers
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'provider'"
);
$total_providers = (int)$result->fetch_assoc()['total'];


// Total admins
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'admin'"
);
$total_admins = (int)$result->fetch_assoc()['total'];


// Total scholarships
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM scholarships"
);
$total_scholarships = (int)$result->fetch_assoc()['total'];


// Total applications
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications"
);
$total_applications = (int)$result->fetch_assoc()['total'];


// Pending applications
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE status = 'Pending'"
);
$pending_applications = (int)$result->fetch_assoc()['total'];


// Approved applications
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE status = 'Approved'"
);
$approved_applications = (int)$result->fetch_assoc()['total'];


// Rejected applications
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE status = 'Rejected'"
);
$rejected_applications = (int)$result->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| RECENT APPLICATIONS
|--------------------------------------------------------------------------
*/

$recent_applications = $conn->query(
    "SELECT
        a.id,
        a.status,
        a.applied_at,

        u.name AS student_name,
        u.email AS student_email,

        s.title AS scholarship_title,
        s.provider_name

     FROM applications a

     JOIN users u
        ON a.student_id = u.id

     JOIN scholarships s
        ON a.scholarship_id = s.id

     ORDER BY a.applied_at DESC

     LIMIT 10"
);


/*
|--------------------------------------------------------------------------
| RECENT USERS
|--------------------------------------------------------------------------
*/

$recent_users = $conn->query(
    "SELECT
        id,
        name,
        email,
        role,
        education_level,
        field_of_study,
        cgpa,
        country,
        created_at

     FROM users

     ORDER BY created_at DESC

     LIMIT 10"
);


/*
|--------------------------------------------------------------------------
| RECENT SCHOLARSHIPS
|--------------------------------------------------------------------------
*/

$recent_scholarships = $conn->query(
    "SELECT
        s.id,
        s.title,
        s.provider_name,
        s.amount,
        s.deadline,
        s.education_level,
        s.field_of_study,
        s.country,
        s.created_at,
        u.name AS provider_account

     FROM scholarships s

     JOIN users u
        ON s.provider_id = u.id

     ORDER BY s.created_at DESC

     LIMIT 10"
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard</title>

    <?php include 'partials/head.php'; ?>


    <style>

        /*
        |--------------------------------------------------------------------------
        | ADMIN HEADER
        |--------------------------------------------------------------------------
        */

        .admin-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 28px;

            flex-wrap: wrap;

        }


        .admin-subtitle {

            color: #64748B;

            margin-top: 7px;

        }


        /*
        |--------------------------------------------------------------------------
        | ADMIN ACTION BUTTONS
        |--------------------------------------------------------------------------
        */

        .admin-actions {

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }


        .admin-action {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding: 11px 16px;

            border-radius: 10px;

            text-decoration: none;

            font-weight: 700;

            font-size: 14px;

            background: #2563EB;

            color: #FFFFFF;

            transition: 0.2s ease;

        }


        .admin-action:hover {

            opacity: 0.9;

            transform: translateY(-1px);

        }


        .admin-action.secondary {

            background: #E2E8F0;

            color: #334155;

        }


        /*
        |--------------------------------------------------------------------------
        | STATISTICS
        |--------------------------------------------------------------------------
        */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 28px;

        }


        .stat-card {

            background: #FFFFFF;

            border: 1px solid #E2E8F0;

            border-radius: 16px;

            padding: 22px;

            box-shadow:
                0 5px 18px
                rgba(15, 23, 42, 0.05);

        }


        .stat-icon {

            font-size: 25px;

            margin-bottom: 10px;

        }


        .stat-label {

            color: #64748B;

            font-size: 14px;

            margin-bottom: 7px;

        }


        .stat-number {

            font-size: 30px;

            font-weight: 800;

            color: #0F172A;

        }


        /*
        |--------------------------------------------------------------------------
        | SECTION CARD
        |--------------------------------------------------------------------------
        */

        .section-card {

            background: #FFFFFF;

            border: 1px solid #E2E8F0;

            border-radius: 16px;

            padding: 22px;

            margin-bottom: 25px;

            box-shadow:
                0 5px 18px
                rgba(15, 23, 42, 0.05);

        }


        .section-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;

            flex-wrap: wrap;

        }


        .section-title {

            font-size: 20px;

            font-weight: 800;

            color: #0F172A;

        }


        .section-description {

            color: #64748B;

            font-size: 14px;

            margin-top: 5px;

        }


        /*
        |--------------------------------------------------------------------------
        | APPLICATION SUMMARY
        |--------------------------------------------------------------------------
        */

        .summary-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;

        }


        .summary-item {

            padding: 18px;

            border-radius: 12px;

            background: #F8FAFC;

            border: 1px solid #E2E8F0;

        }


        .summary-label {

            font-size: 13px;

            color: #64748B;

            margin-bottom: 7px;

        }


        .summary-number {

            font-size: 25px;

            font-weight: 800;

            color: #0F172A;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .table-wrap {

            overflow-x: auto;

        }


        .table {

            width: 100%;

            border-collapse: collapse;

        }


        .table th,
        .table td {

            padding: 13px 12px;

            text-align: left;

            border-bottom: 1px solid #E2E8F0;

            white-space: nowrap;

        }


        .table th {

            color: #475569;

            font-size: 13px;

            background: #F8FAFC;

        }


        .table td {

            color: #334155;

            font-size: 14px;

        }


        .table tr:last-child td {

            border-bottom: none;

        }


        /*
        |--------------------------------------------------------------------------
        | USER / SCHOLARSHIP
        |--------------------------------------------------------------------------
        */

        .user-name {

            font-weight: 700;

            color: #0F172A;

        }


        .user-email {

            color: #64748B;

            font-size: 13px;

        }


        .scholarship-title {

            font-weight: 700;

            color: #0F172A;

        }


        /*
        |--------------------------------------------------------------------------
        | BADGES
        |--------------------------------------------------------------------------
        */

        .badge {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 700;

        }


        .role-student {

            background: #DCFCE7;

            color: #15803D;

        }


        .role-provider {

            background: #DBEAFE;

            color: #1D4ED8;

        }


        .role-admin {

            background: #EDE9FE;

            color: #6D28D9;

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


        /*
        |--------------------------------------------------------------------------
        | VIEW BUTTON
        |--------------------------------------------------------------------------
        */

        .view-btn {

            display: inline-block;

            padding: 7px 11px;

            border-radius: 8px;

            background: #EFF6FF;

            color: #1D4ED8;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .empty-state {

            text-align: center;

            padding: 40px 20px;

            color: #64748B;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1100px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .summary-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 700px) {

            .stats-grid {

                grid-template-columns: 1fr;

            }


            .summary-grid {

                grid-template-columns: 1fr;

            }


            .section-card {

                padding: 16px;

            }


            .admin-header {

                align-items: flex-start;

            }


            .admin-actions {

                width: 100%;

            }


            .admin-action {

                flex: 1;

            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<div class="container">


    <!-- =========================================================
         ADMIN HEADER
    ========================================================== -->

    <div class="admin-header">


        <div>

            <h1 class="page-title">

                Admin Dashboard

            </h1>


            <p class="admin-subtitle">

                Manage and monitor the ScholarMatch platform.

            </p>

        </div>


        <!-- ADMIN MANAGEMENT BUTTONS -->

        <div class="admin-actions">


            <a
                href="admin_users.php"
                class="admin-action"
            >

                👥 Manage Users

            </a>


            <a
                href="admin_scholarships.php"
                class="admin-action secondary"
            >

                🏆 Manage Scholarships

            </a>


            <a
                href="admin_applications.php"
                class="admin-action secondary"
            >

                📋 Manage Applications

            </a>


            <!-- NEW REPORTS BUTTON -->

            <a
                href="admin_reports.php"
                class="admin-action secondary"
            >

                📊 Reports

            </a>


        </div>

    </div>


    <!-- =========================================================
         MAIN STATISTICS
    ========================================================== -->

    <div class="stats-grid">


        <!-- TOTAL USERS -->

        <div class="stat-card">

            <div class="stat-icon">

                👥

            </div>


            <div class="stat-label">

                Total Users

            </div>


            <div class="stat-number">

                <?= $total_users ?>

            </div>

        </div>


        <!-- STUDENTS -->

        <div class="stat-card">

            <div class="stat-icon">

                🎓

            </div>


            <div class="stat-label">

                Students

            </div>


            <div class="stat-number">

                <?= $total_students ?>

            </div>

        </div>


        <!-- PROVIDERS -->

        <div class="stat-card">

            <div class="stat-icon">

                🏢

            </div>


            <div class="stat-label">

                Providers

            </div>


            <div class="stat-number">

                <?= $total_providers ?>

            </div>

        </div>


        <!-- SCHOLARSHIPS -->

        <div class="stat-card">

            <div class="stat-icon">

                🏆

            </div>


            <div class="stat-label">

                Scholarships

            </div>


            <div class="stat-number">

                <?= $total_scholarships ?>

            </div>

        </div>


    </div>


    <!-- =========================================================
         APPLICATION OVERVIEW
    ========================================================== -->

    <div class="section-card">


        <div class="section-header">


            <div>

                <div class="section-title">

                    Application Overview

                </div>


                <div class="section-description">

                    Current application statistics across the platform.

                </div>

            </div>


            <a
                href="admin_applications.php"
                class="view-btn"
            >

                View All Applications →

            </a>


        </div>


        <div class="summary-grid">


            <!-- TOTAL -->

            <div class="summary-item">

                <div class="summary-label">

                    Total Applications

                </div>


                <div class="summary-number">

                    <?= $total_applications ?>

                </div>

            </div>


            <!-- PENDING -->

            <div class="summary-item">

                <div class="summary-label">

                    Pending

                </div>


                <div class="summary-number">

                    <?= $pending_applications ?>

                </div>

            </div>


            <!-- APPROVED -->

            <div class="summary-item">

                <div class="summary-label">

                    Approved

                </div>


                <div class="summary-number">

                    <?= $approved_applications ?>

                </div>

            </div>


            <!-- REJECTED -->

            <div class="summary-item">

                <div class="summary-label">

                    Rejected

                </div>


                <div class="summary-number">

                    <?= $rejected_applications ?>

                </div>

            </div>


        </div>

    </div>


    <!-- =========================================================
         RECENT APPLICATIONS
    ========================================================== -->

    <div class="section-card">


        <div class="section-header">


            <div>

                <div class="section-title">

                    Recent Applications

                </div>


                <div class="section-description">

                    Latest scholarship applications submitted by students.

                </div>

            </div>


            <a
                href="admin_applications.php"
                class="view-btn"
            >

                View All →

            </a>


        </div>


        <?php if (
            $recent_applications &&
            $recent_applications->num_rows > 0
        ): ?>


            <div class="table-wrap">


                <table class="table">


                    <thead>

                        <tr>

                            <th>
                                Student
                            </th>

                            <th>
                                Scholarship
                            </th>

                            <th>
                                Provider
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Applied
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $a = $recent_applications->fetch_assoc()
                    ): ?>


                        <?php

                        if ($a['status'] === 'Pending') {

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


                        <tr>


                            <td>

                                <div class="user-name">

                                    <?= e(
                                        $a['student_name']
                                    ) ?>

                                </div>


                                <div class="user-email">

                                    <?= e(
                                        $a['student_email']
                                    ) ?>

                                </div>

                            </td>


                            <td>

                                <div class="scholarship-title">

                                    <?= e(
                                        $a['scholarship_title']
                                    ) ?>

                                </div>

                            </td>


                            <td>

                                <?= e(
                                    $a['provider_name']
                                ) ?>

                            </td>


                            <td>

                                <span
                                    class="badge <?= $status_class ?>"
                                >

                                    <?= e(
                                        $a['status']
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?= e(
                                    $a['applied_at']
                                ) ?>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-state">

                No applications found.

            </div>


        <?php endif; ?>


    </div>


    <!-- =========================================================
         RECENT USERS
    ========================================================== -->

    <div class="section-card">


        <div class="section-header">


            <div>

                <div class="section-title">

                    Recent Users

                </div>


                <div class="section-description">

                    Recently registered users on ScholarMatch.

                </div>

            </div>


            <a
                href="admin_users.php"
                class="view-btn"
            >

                Manage Users →

            </a>


        </div>


        <?php if (
            $recent_users &&
            $recent_users->num_rows > 0
        ): ?>


            <div class="table-wrap">


                <table class="table">


                    <thead>

                        <tr>

                            <th>
                                Name
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Education
                            </th>

                            <th>
                                Field
                            </th>

                            <th>
                                CGPA
                            </th>

                            <th>
                                Country
                            </th>

                            <th>
                                Registered
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $u = $recent_users->fetch_assoc()
                    ): ?>


                        <?php

                        if ($u['role'] === 'student') {

                            $role_class =
                                'role-student';

                        } elseif (
                            $u['role'] === 'provider'
                        ) {

                            $role_class =
                                'role-provider';

                        } else {

                            $role_class =
                                'role-admin';

                        }

                        ?>


                        <tr>


                            <td>

                                <div class="user-name">

                                    <?= e(
                                        $u['name']
                                    ) ?>

                                </div>

                            </td>


                            <td>

                                <div class="user-email">

                                    <?= e(
                                        $u['email']
                                    ) ?>

                                </div>

                            </td>


                            <td>

                                <span
                                    class="badge <?= $role_class ?>"
                                >

                                    <?= e(
                                        ucfirst(
                                            $u['role']
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?= e(
                                    $u['education_level']
                                    ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    $u['field_of_study']
                                    ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    $u['cgpa']
                                    ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    $u['country']
                                    ?? '-'
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    $u['created_at']
                                ) ?>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-state">

                No users found.

            </div>


        <?php endif; ?>


    </div>


    <!-- =========================================================
         RECENT SCHOLARSHIPS
    ========================================================== -->

    <div class="section-card">


        <div class="section-header">


            <div>

                <div class="section-title">

                    Recent Scholarships

                </div>


                <div class="section-description">

                    Latest scholarships published by providers.

                </div>

            </div>


            <a
                href="admin_scholarships.php"
                class="view-btn"
            >

                Manage Scholarships →

            </a>


        </div>


        <?php if (
            $recent_scholarships &&
            $recent_scholarships->num_rows > 0
        ): ?>


            <div class="table-wrap">


                <table class="table">


                    <thead>

                        <tr>

                            <th>
                                Scholarship
                            </th>

                            <th>
                                Provider
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Deadline
                            </th>

                            <th>
                                Education
                            </th>

                            <th>
                                Field
                            </th>

                            <th>
                                Country
                            </th>

                            <th>
                                Created
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $s = $recent_scholarships->fetch_assoc()
                    ): ?>


                        <tr>


                            <td>

                                <div class="scholarship-title">

                                    <?= e(
                                        $s['title']
                                    ) ?>

                                </div>

                            </td>


                            <td>

                                <?= e(
                                    $s['provider_name']
                                ) ?>

                            </td>


                            <td>

                                <?= number_format(
                                    (float)$s['amount'],
                                    2
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    $s['deadline']
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    $s['education_level']
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    $s['field_of_study']
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    $s['country']
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    $s['created_at']
                                ) ?>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-state">

                No scholarships found.

            </div>


        <?php endif; ?>


    </div>


    <!-- =========================================================
         PLATFORM SUMMARY
    ========================================================== -->

    <div class="section-card">


        <div class="section-title">

            Platform Summary

        </div>


        <div class="section-description">

            Current ScholarMatch platform distribution.

        </div>


        <div class="summary-grid">


            <!-- STUDENTS -->

            <div class="summary-item">

                <div class="summary-label">

                    Students

                </div>


                <div class="summary-number">

                    <?= $total_students ?>

                </div>

            </div>


            <!-- PROVIDERS -->

            <div class="summary-item">

                <div class="summary-label">

                    Providers

                </div>


                <div class="summary-number">

                    <?= $total_providers ?>

                </div>

            </div>


            <!-- ADMINS -->

            <div class="summary-item">

                <div class="summary-label">

                    Administrators

                </div>


                <div class="summary-number">

                    <?= $total_admins ?>

                </div>

            </div>


            <!-- SCHOLARSHIPS -->

            <div class="summary-item">

                <div class="summary-label">

                    Scholarships

                </div>


                <div class="summary-number">

                    <?= $total_scholarships ?>

                </div>

            </div>


        </div>

    </div>


</div>


</body>

</html>