<?php
require_once 'config.php';
require_role('admin');


/*
|--------------------------------------------------------------------------
| BASIC STATISTICS
|--------------------------------------------------------------------------
*/

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM users"
);

$total_users = (int)$result->fetch_assoc()['total'];


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'"
);

$total_students = (int)$result->fetch_assoc()['total'];


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'provider'"
);

$total_providers = (int)$result->fetch_assoc()['total'];


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM scholarships"
);

$total_scholarships = (int)$result->fetch_assoc()['total'];


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications"
);

$total_applications = (int)$result->fetch_assoc()['total'];


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE status = 'Pending'"
);

$pending = (int)$result->fetch_assoc()['total'];


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE status = 'Approved'"
);

$approved = (int)$result->fetch_assoc()['total'];


$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE status = 'Rejected'"
);

$rejected = (int)$result->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| APPROVAL RATE
|--------------------------------------------------------------------------
*/

$reviewed = $approved + $rejected;

$approval_rate = $reviewed > 0
    ? round(($approved / $reviewed) * 100, 1)
    : 0;


/*
|--------------------------------------------------------------------------
| SCHOLARSHIPS BY COUNTRY
|--------------------------------------------------------------------------
*/

$country_data = $conn->query(
    "SELECT
        country,
        COUNT(*) AS total

     FROM scholarships

     GROUP BY country

     ORDER BY total DESC

     LIMIT 10"
);


/*
|--------------------------------------------------------------------------
| APPLICATION STATUS
|--------------------------------------------------------------------------
*/

$status_data = $conn->query(
    "SELECT
        status,
        COUNT(*) AS total

     FROM applications

     GROUP BY status"
);


/*
|--------------------------------------------------------------------------
| MONTHLY APPLICATIONS
|--------------------------------------------------------------------------
*/

$monthly_data = $conn->query(
    "SELECT
        DATE_FORMAT(applied_at, '%Y-%m') AS month,
        COUNT(*) AS total

     FROM applications

     GROUP BY month

     ORDER BY month DESC

     LIMIT 12"
);


/*
|--------------------------------------------------------------------------
| MONTHLY USERS
|--------------------------------------------------------------------------
*/

$monthly_users = $conn->query(
    "SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month,
        COUNT(*) AS total

     FROM users

     GROUP BY month

     ORDER BY month DESC

     LIMIT 12"
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

    <title>Admin Reports</title>

    <?php include 'partials/head.php'; ?>


    <style>

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 28px;

            flex-wrap: wrap;

        }


        .page-subtitle {

            color: #64748B;

            margin-top: 6px;

        }


        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        .btn {

            display: inline-block;

            padding: 10px 15px;

            border-radius: 9px;

            text-decoration: none;

            font-weight: 700;

            font-size: 14px;

        }


        .btn-secondary {

            background: #E2E8F0;

            color: #334155;

        }


        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;

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

            font-size: 13px;

            margin-bottom: 6px;

        }


        .stat-number {

            color: #0F172A;

            font-size: 29px;

            font-weight: 800;

        }


        /*
        |--------------------------------------------------------------------------
        | Report Grid
        |--------------------------------------------------------------------------
        */

        .report-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 20px;

            margin-bottom: 25px;

        }


        .report-card {

            background: #FFFFFF;

            border: 1px solid #E2E8F0;

            border-radius: 16px;

            padding: 22px;

            box-shadow:
                0 5px 18px
                rgba(15, 23, 42, 0.05);

        }


        .report-title {

            font-size: 19px;

            font-weight: 800;

            color: #0F172A;

            margin-bottom: 5px;

        }


        .report-description {

            color: #64748B;

            font-size: 13px;

            margin-bottom: 20px;

        }


        /*
        |--------------------------------------------------------------------------
        | Progress Bars
        |--------------------------------------------------------------------------
        */

        .bar-item {

            margin-bottom: 16px;

        }


        .bar-header {

            display: flex;

            justify-content: space-between;

            gap: 10px;

            margin-bottom: 6px;

            font-size: 13px;

        }


        .bar-label {

            color: #334155;

            font-weight: 700;

        }


        .bar-value {

            color: #64748B;

        }


        .bar-track {

            width: 100%;

            height: 9px;

            background: #E2E8F0;

            border-radius: 999px;

            overflow: hidden;

        }


        .bar-fill {

            height: 100%;

            background: #2563EB;

            border-radius: 999px;

        }


        /*
        |--------------------------------------------------------------------------
        | Status Cards
        |--------------------------------------------------------------------------
        */

        .status-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 12px;

        }


        .status-item {

            padding: 17px;

            border-radius: 12px;

            text-align: center;

        }


        .status-item.pending {

            background: #FEF3C7;

        }


        .status-item.approved {

            background: #DCFCE7;

        }


        .status-item.rejected {

            background: #FEE2E2;

        }


        .status-number {

            font-size: 27px;

            font-weight: 800;

            margin-bottom: 4px;

        }


        .status-label {

            font-size: 12px;

            font-weight: 700;

        }


        /*
        |--------------------------------------------------------------------------
        | Approval Rate
        |--------------------------------------------------------------------------
        */

        .approval-box {

            display: flex;

            align-items: center;

            gap: 25px;

        }


        .approval-circle {

            width: 120px;

            height: 120px;

            border-radius: 50%;

            border: 10px solid #2563EB;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

        }


        .approval-percent {

            font-size: 23px;

            font-weight: 800;

            color: #0F172A;

        }


        .approval-text {

            color: #475569;

            line-height: 1.7;

            font-size: 14px;

        }


        /*
        |--------------------------------------------------------------------------
        | Tables
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

            padding: 12px;

            text-align: left;

            border-bottom: 1px solid #E2E8F0;

        }


        .table th {

            background: #F8FAFC;

            color: #475569;

            font-size: 13px;

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
        | Empty State
        |--------------------------------------------------------------------------
        */

        .empty-state {

            text-align: center;

            padding: 30px;

            color: #64748B;

        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1000px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .report-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 650px) {

            .stats-grid {

                grid-template-columns: 1fr;

            }


            .status-grid {

                grid-template-columns: 1fr;

            }


            .approval-box {

                flex-direction: column;

                align-items: flex-start;

            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<div class="container">


    <!-- =========================================================
         HEADER
    ========================================================== -->

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Reports & Analytics
            </h1>

            <p class="page-subtitle">
                Overview of ScholarMatch platform performance.
            </p>

        </div>


      

    </div>


    <!-- =========================================================
         STATISTICS
    ========================================================== -->

    <div class="stats-grid">


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


        <div class="stat-card">

            <div class="stat-icon">
                📋
            </div>

            <div class="stat-label">
                Applications
            </div>

            <div class="stat-number">
                <?= $total_applications ?>
            </div>

        </div>


    </div>


    <!-- =========================================================
         APPLICATION STATUS
    ========================================================== -->

    <div class="report-card" style="margin-bottom:25px;">

        <div class="report-title">
            Application Status
        </div>

        <div class="report-description">
            Current distribution of scholarship applications.
        </div>


        <div class="status-grid">


            <div class="status-item pending">

                <div class="status-number">
                    <?= $pending ?>
                </div>

                <div class="status-label">
                    Pending
                </div>

            </div>


            <div class="status-item approved">

                <div class="status-number">
                    <?= $approved ?>
                </div>

                <div class="status-label">
                    Approved
                </div>

            </div>


            <div class="status-item rejected">

                <div class="status-number">
                    <?= $rejected ?>
                </div>

                <div class="status-label">
                    Rejected
                </div>

            </div>


        </div>

    </div>


    <!-- =========================================================
         REPORT GRID
    ========================================================== -->

    <div class="report-grid">


        <!-- COUNTRY REPORT -->

        <div class="report-card">

            <div class="report-title">
                Scholarships by Country
            </div>

            <div class="report-description">
                Top countries by number of scholarships.
            </div>


            <?php if (
                $country_data &&
                $country_data->num_rows > 0
            ): ?>


                <?php

                $country_rows = [];

                $max_country = 1;


                while (
                    $row =
                    $country_data->fetch_assoc()
                ) {

                    $country_rows[] = $row;

                    $max_country = max(
                        $max_country,
                        (int)$row['total']
                    );

                }

                ?>


                <?php foreach (
                    $country_rows as $row
                ): ?>


                    <div class="bar-item">


                        <div class="bar-header">

                            <span class="bar-label">

                                <?= e(
                                    $row['country']
                                ) ?>

                            </span>


                            <span class="bar-value">

                                <?= (int)$row['total'] ?>

                            </span>

                        </div>


                        <div class="bar-track">

                            <div
                                class="bar-fill"
                                style="width:<?= (
                                    (int)$row['total']
                                    / $max_country
                                ) * 100 ?>%;"
                            ></div>

                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="empty-state">
                    No country data available.
                </div>


            <?php endif; ?>


        </div>


        <!-- APPROVAL RATE -->

        <div class="report-card">

            <div class="report-title">
                Approval Rate
            </div>

            <div class="report-description">
                Percentage of reviewed applications that were approved.
            </div>


            <div class="approval-box">


                <div class="approval-circle">

                    <div class="approval-percent">

                        <?= $approval_rate ?>%

                    </div>

                </div>


                <div class="approval-text">

                    <?= $approved ?>
                    applications have been approved.

                    <br>

                    <?= $rejected ?>
                    applications have been rejected.

                    <br>

                    <?= $pending ?>
                    applications are still pending.

                </div>


            </div>


        </div>


    </div>


    <!-- =========================================================
         MONTHLY APPLICATIONS
    ========================================================== -->

    <div class="report-card" style="margin-bottom:25px;">

        <div class="report-title">
            Monthly Applications
        </div>

        <div class="report-description">
            Application activity for the most recent months.
        </div>


        <?php if (
            $monthly_data &&
            $monthly_data->num_rows > 0
        ): ?>


            <div class="table-wrap">

                <table class="table">


                    <thead>

                        <tr>

                            <th>
                                Month
                            </th>

                            <th>
                                Applications
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $row =
                        $monthly_data->fetch_assoc()
                    ): ?>


                        <tr>

                            <td>

                                <?= e(
                                    $row['month']
                                ) ?>

                            </td>


                            <td>

                                <?= (int)$row['total'] ?>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-state">
                No application activity available.
            </div>


        <?php endif; ?>


    </div>


    <!-- =========================================================
         MONTHLY USERS
    ========================================================== -->

    <div class="report-card" style="margin-bottom:25px;">

        <div class="report-title">
            Monthly User Registrations
        </div>

        <div class="report-description">
            Number of new users registered each month.
        </div>


        <?php if (
            $monthly_users &&
            $monthly_users->num_rows > 0
        ): ?>


            <div class="table-wrap">

                <table class="table">


                    <thead>

                        <tr>

                            <th>
                                Month
                            </th>

                            <th>
                                New Users
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $row =
                        $monthly_users->fetch_assoc()
                    ): ?>


                        <tr>

                            <td>

                                <?= e(
                                    $row['month']
                                ) ?>

                            </td>


                            <td>

                                <?= (int)$row['total'] ?>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-state">
                No registration data available.
            </div>


        <?php endif; ?>


    </div>


    <!-- =========================================================
         QUICK ADMIN LINKS
    ========================================================== -->

    <div class="report-card">

        <div class="report-title">
            Admin Tools
        </div>

        <div class="report-description">
            Quickly access platform management sections.
        </div>


        <div
            style="
                display:flex;
                gap:10px;
                flex-wrap:wrap;
            "
        >


            <a
                href="admin_users.php"
                class="btn btn-secondary"
            >
                👥 Manage Users
            </a>


            <a
                href="admin_scholarships.php"
                class="btn btn-secondary"
            >
                🏆 Manage Scholarships
            </a>


            <a
                href="admin_applications.php"
                class="btn btn-secondary"
            >
                📋 Manage Applications
            </a>


        </div>

    </div>


</div>


</body>

</html>