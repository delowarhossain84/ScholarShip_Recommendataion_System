<?php

require_once 'config.php';

require_login();

$u = user();

$role = $u['role'] ?? 'student';


// ============================================================
// STUDENT DASHBOARD DATA
// ============================================================

if ($role === 'student') {

    $sid = (int)$u['id'];

    // --------------------------------------------------------
    // Active scholarships
    // --------------------------------------------------------

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM scholarships
        WHERE deadline >= CURDATE()
    ");

    $stmt->execute();

    $result = $stmt->get_result();

    $activeScholarships = (int)$result->fetch_assoc()['total'];

    $stmt->close();


    // --------------------------------------------------------
    // Total applications
    // --------------------------------------------------------

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM applications
        WHERE student_id = ?
    ");

    $stmt->bind_param("i", $sid);

    $stmt->execute();

    $result = $stmt->get_result();

    $applications = (int)$result->fetch_assoc()['total'];

    $stmt->close();


    // --------------------------------------------------------
    // Approved applications
    // --------------------------------------------------------

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM applications
        WHERE student_id = ?
        AND status = 'Approved'
    ");

    $stmt->bind_param("i", $sid);

    $stmt->execute();

    $result = $stmt->get_result();

    $approved = (int)$result->fetch_assoc()['total'];

    $stmt->close();


    // --------------------------------------------------------
    // Pending applications
    // --------------------------------------------------------

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM applications
        WHERE student_id = ?
        AND status = 'Pending'
    ");

    $stmt->bind_param("i", $sid);

    $stmt->execute();

    $result = $stmt->get_result();

    $pending = (int)$result->fetch_assoc()['total'];

    $stmt->close();


    // --------------------------------------------------------
    // Student profile data
    // --------------------------------------------------------

    $educationLevel = trim($u['education_level'] ?? '');

    $fieldOfStudy = trim($u['field_of_study'] ?? '');

    $country = trim($u['country'] ?? '');

    $cgpa = (float)($u['cgpa'] ?? 0);


    // --------------------------------------------------------
    // Recommended scholarships
    //
    // Score:
    // Education = 25%
    // Field      = 25%
    // Country    = 20%
    // CGPA       = 30%
    // --------------------------------------------------------

    $stmt = $conn->prepare("
        SELECT
            id,
            title,
            description,
            provider_name,
            amount,
            deadline,
            min_cgpa,
            education_level,
            field_of_study,
            country,

            (
                CASE
                    WHEN education_level = 'Any'
                    OR education_level = ?
                    THEN 25
                    ELSE 0
                END

                +

                CASE
                    WHEN field_of_study = 'Any'
                    OR field_of_study = ?
                    THEN 25
                    ELSE 0
                END

                +

                CASE
                    WHEN country = 'Any'
                    OR country = ?
                    THEN 20
                    ELSE 0
                END

                +

                CASE
                    WHEN min_cgpa <= ?
                    THEN 30
                    ELSE 0
                END

            ) AS match_score

        FROM scholarships

        WHERE deadline >= CURDATE()

        ORDER BY match_score DESC, deadline ASC

        LIMIT 3
    ");

    $stmt->bind_param(
        "sssd",
        $educationLevel,
        $fieldOfStudy,
        $country,
        $cgpa
    );

    $stmt->execute();

    $recommendations = $stmt->get_result();

}


// ============================================================
// PROVIDER DASHBOARD DATA
// ============================================================

elseif ($role === 'provider') {

    $pid = (int)$u['id'];


    // --------------------------------------------------------
    // Provider scholarship count
    // --------------------------------------------------------

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM scholarships
        WHERE provider_id = ?
    ");

    $stmt->bind_param("i", $pid);

    $stmt->execute();

    $result = $stmt->get_result();

    $scholarships = (int)$result->fetch_assoc()['total'];

    $stmt->close();


    // --------------------------------------------------------
    // Applications received
    // --------------------------------------------------------

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total

        FROM applications a

        INNER JOIN scholarships s
            ON a.scholarship_id = s.id

        WHERE s.provider_id = ?
    ");

    $stmt->bind_param("i", $pid);

    $stmt->execute();

    $result = $stmt->get_result();

    $applications = (int)$result->fetch_assoc()['total'];

    $stmt->close();


    // --------------------------------------------------------
    // Pending applications
    // --------------------------------------------------------

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total

        FROM applications a

        INNER JOIN scholarships s
            ON a.scholarship_id = s.id

        WHERE s.provider_id = ?

        AND a.status = 'Pending'
    ");

    $stmt->bind_param("i", $pid);

    $stmt->execute();

    $result = $stmt->get_result();

    $pendingApplications = (int)$result->fetch_assoc()['total'];

    $stmt->close();


    // --------------------------------------------------------
    // Approved applications
    // --------------------------------------------------------

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total

        FROM applications a

        INNER JOIN scholarships s
            ON a.scholarship_id = s.id

        WHERE s.provider_id = ?

        AND a.status = 'Approved'
    ");

    $stmt->bind_param("i", $pid);

    $stmt->execute();

    $result = $stmt->get_result();

    $approvedApplications = (int)$result->fetch_assoc()['total'];

    $stmt->close();

}


// ============================================================
// ADMIN DASHBOARD DATA
// ============================================================

elseif ($role === 'admin') {


    // --------------------------------------------------------
    // Total users
    // --------------------------------------------------------

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM users
    ");

    $users = (int)$result->fetch_assoc()['total'];


    // --------------------------------------------------------
    // Total scholarships
    // --------------------------------------------------------

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM scholarships
    ");

    $scholarships = (int)$result->fetch_assoc()['total'];


    // --------------------------------------------------------
    // Total applications
    // --------------------------------------------------------

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM applications
    ");

    $applications = (int)$result->fetch_assoc()['total'];


    // --------------------------------------------------------
    // Pending applications
    // --------------------------------------------------------

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM applications
        WHERE status = 'Pending'
    ");

    $pendingApplications = (int)$result->fetch_assoc()['total'];


    // --------------------------------------------------------
    // Approved applications
    // --------------------------------------------------------

    $result = $conn->query("
        SELECT COUNT(*) AS total
        FROM applications
        WHERE status = 'Approved'
    ");

    $approvedApplications = (int)$result->fetch_assoc()['total'];

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard | ScholarMatch</title>

    <?php include 'partials/head.php'; ?>


    <style>

        /* =====================================================
           GENERAL
           ===================================================== */

        body {
            background: #f6f8fc;
        }


        .dashboard-container {

            max-width: 1200px;

            margin: 0 auto;

            padding: 35px 20px 60px;

        }


        /* =====================================================
           WELCOME
           ===================================================== */

        .welcome-section {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 30px;

        }


        .welcome-section h1 {

            margin: 0;

            font-size: 32px;

            color: #172033;

        }


        .welcome-section p {

            margin: 8px 0 0;

            color: #64748b;

            font-size: 15px;

        }


        /* =====================================================
           PROFILE BADGE
           ===================================================== */

        .profile-badge {

            display: flex;

            align-items: center;

            gap: 12px;

            background: white;

            padding: 10px 16px;

            border-radius: 14px;

            border: 1px solid #e8edf5;

            box-shadow:
                0 5px 20px rgba(15, 23, 42, 0.06);

        }


        .avatar {

            width: 42px;

            height: 42px;

            border-radius: 50%;

            background: #2563eb;

            color: white;

            display: flex;

            justify-content: center;

            align-items: center;

            font-weight: 700;

            font-size: 18px;

        }


        .profile-link {

            text-decoration: none;

            color: inherit;

        }


        .profile-link:hover {

            opacity: 0.85;

        }


        /* =====================================================
           STATISTICS
           ===================================================== */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 30px;

        }


        .stat-card {

            background: white;

            border: 1px solid #e8edf5;

            border-radius: 18px;

            padding: 22px;

            box-shadow:
                0 6px 25px rgba(15, 23, 42, 0.05);

        }


        .stat-icon {

            width: 44px;

            height: 44px;

            border-radius: 12px;

            background: #eff6ff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;

            margin-bottom: 15px;

        }


        .stat-number {

            display: block;

            font-size: 28px;

            font-weight: 700;

            color: #172033;

        }


        .stat-label {

            color: #64748b;

            font-size: 14px;

            margin-top: 4px;

        }


        /* =====================================================
           SECTION
           ===================================================== */

        .section {

            margin-bottom: 35px;

        }


        .section-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 18px;

        }


        .section-header h2 {

            margin: 0;

            font-size: 22px;

            color: #172033;

        }


        /* =====================================================
           RECOMMENDATIONS
           ===================================================== */

        .recommendation-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

        }


        .scholarship-card {

            background: white;

            border: 1px solid #e8edf5;

            border-radius: 18px;

            padding: 22px;

            box-shadow:
                0 6px 25px rgba(15, 23, 42, 0.05);

            transition: 0.2s;

        }


        .scholarship-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 12px 30px rgba(15, 23, 42, 0.10);

        }


        .scholarship-card h3 {

            margin: 0 0 8px;

            color: #172033;

            font-size: 18px;

        }


        .provider-name {

            color: #64748b;

            font-size: 13px;

            margin-bottom: 15px;

        }


        .match-badge {

            display: inline-block;

            background: #dcfce7;

            color: #15803d;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 700;

            margin-bottom: 15px;

        }


        .scholarship-info {

            display: grid;

            gap: 9px;

            margin-bottom: 18px;

            color: #475569;

            font-size: 14px;

        }


        /* =====================================================
           ACTION GRID
           ===================================================== */

        .action-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

        }


        .action-card {

            background: white;

            border: 1px solid #e8edf5;

            border-radius: 18px;

            padding: 24px;

            transition: 0.2s;

        }


        .action-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 25px rgba(15, 23, 42, 0.08);

        }


        .action-card h3 {

            margin: 0 0 8px;

            color: #172033;

            font-size: 18px;

        }


        .action-card p {

            color: #64748b;

            line-height: 1.6;

            min-height: 55px;

        }


        /* =====================================================
           BUTTONS
           ===================================================== */

        .dashboard-btn {

            display: inline-block;

            text-decoration: none;

            background: #2563eb;

            color: white;

            padding: 10px 16px;

            border-radius: 9px;

            font-size: 14px;

            font-weight: 600;

            border: none;

            cursor: pointer;

        }


        .dashboard-btn.secondary {

            background: #eff6ff;

            color: #2563eb;

        }


        .dashboard-btn:hover {

            opacity: 0.9;

        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .empty-card {

            background: white;

            border: 1px solid #e8edf5;

            border-radius: 18px;

            padding: 35px;

            text-align: center;

            color: #64748b;

            grid-column: 1 / -1;

        }


        .empty-card h3 {

            color: #172033;

            margin-top: 0;

        }


        /* =====================================================
           MOBILE
           ===================================================== */

        @media (max-width: 1000px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .recommendation-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .action-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 700px) {

            .welcome-section {

                flex-direction: column;

                align-items: flex-start;

            }


            .profile-badge {

                width: 100%;

                box-sizing: border-box;

            }


            .stats-grid,

            .recommendation-grid,

            .action-grid {

                grid-template-columns: 1fr;

            }


            .welcome-section h1 {

                font-size: 27px;

            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<main class="dashboard-container">


    <?php show_flash(); ?>


    <!-- =====================================================
         WELCOME SECTION
         ===================================================== -->

    <section class="welcome-section">

        <div>

            <h1>
                Welcome back,
                <?= e($u['name']) ?>
                👋
            </h1>

            <p>
                Manage your scholarship journey from one place.
            </p>

        </div>


       

                
             


    </section>



    <!-- =====================================================
         STUDENT DASHBOARD
         ===================================================== -->

    <?php if ($role === 'student'): ?>


        <!-- Student Statistics -->

        <section class="section">

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon">
                        🎓
                    </div>

                    <span class="stat-number">
                        <?= $activeScholarships ?>
                    </span>

                    <div class="stat-label">
                        Active Scholarships
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        📋
                    </div>

                    <span class="stat-number">
                        <?= $applications ?>
                    </span>

                    <div class="stat-label">
                        My Applications
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ⏳
                    </div>

                    <span class="stat-number">
                        <?= $pending ?>
                    </span>

                    <div class="stat-label">
                        Pending Applications
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        🏆
                    </div>

                    <span class="stat-number">
                        <?= $approved ?>
                    </span>

                    <div class="stat-label">
                        Approved Applications
                    </div>

                </div>


            </div>

        </section>



        <!-- Recommended Scholarships -->

        <section class="section">

            <div class="section-header">

                <h2>
                    Recommended for You ⭐
                </h2>

                <a
                    href="recommendations.php"
                    class="dashboard-btn secondary"
                >
                    View All
                </a>

            </div>


            <div class="recommendation-grid">


                <?php if (
                    $recommendations &&
                    $recommendations->num_rows > 0
                ): ?>


                    <?php while (
                        $s = $recommendations->fetch_assoc()
                    ): ?>


                        <article class="scholarship-card">


                            <span class="match-badge">

                                <?= (int)$s['match_score'] ?>%
                                Match

                            </span>


                            <h3>
                                <?= e($s['title']) ?>
                            </h3>


                            <div class="provider-name">

                                🏢
                                <?= e($s['provider_name']) ?>

                            </div>


                            <div class="scholarship-info">


                                <div>

                                    💰 Amount:

                                    <strong>
                                        ৳<?= number_format(
                                            (float)$s['amount'],
                                            2
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    📅 Deadline:

                                    <?= e($s['deadline']) ?>

                                </div>


                                <div>

                                    🎓
                                    <?= e(
                                        $s['education_level']
                                    ) ?>

                                </div>


                                <div>

                                    📚
                                    <?= e(
                                        $s['field_of_study']
                                    ) ?>

                                </div>


                                <div>

                                    🌍
                                    <?= e(
                                        $s['country']
                                    ) ?>

                                </div>


                            </div>


                            <a
                                href="scholarships.php"
                                class="dashboard-btn"
                            >
                                View Scholarships
                            </a>


                        </article>


                    <?php endwhile; ?>


                <?php else: ?>


                    <div class="empty-card">

                        <h3>
                            No recommendations yet
                        </h3>

                        <p>
                            Complete your profile to receive
                            personalized scholarship recommendations.
                        </p>

                        <a
                            href="profile.php"
                            class="dashboard-btn"
                        >
                            Complete Profile
                        </a>

                    </div>


                <?php endif; ?>


            </div>

        </section>



        <!-- Student Quick Actions -->

        <section class="section">

            <div class="section-header">

                <h2>
                    Quick Actions
                </h2>

            </div>


            <div class="action-grid">


                <div class="action-card">

                    <h3>
                        🎯 Find Scholarships
                    </h3>

                    <p>
                        Discover scholarships that match
                        your academic profile.
                    </p>

                    <a
                        href="recommendations.php"
                        class="dashboard-btn"
                    >
                        View Recommendations
                    </a>

                </div>


                <div class="action-card">

                    <h3>
                        🔎 Browse Scholarships
                    </h3>

                    <p>
                        Search and explore all available
                        scholarship opportunities.
                    </p>

                    <a
                        href="scholarships.php"
                        class="dashboard-btn secondary"
                    >
                        Browse Scholarships
                    </a>

                </div>


                <div class="action-card">

                    <h3>
                        📋 My Applications
                    </h3>

                    <p>
                        Track all your scholarship
                        applications and their status.
                    </p>

                    <a
                        href="applications.php"
                        class="dashboard-btn secondary"
                    >
                        View Applications
                    </a>

                </div>


                <div class="action-card">

                    <h3>
                        👤 My Profile
                    </h3>

                    <p>
                        Update your personal and academic
                        information.
                    </p>

                    <a
                        href="profile.php"
                        class="dashboard-btn secondary"
                    >
                        Edit Profile
                    </a>

                </div>


            </div>

        </section>



    <!-- =====================================================
         PROVIDER DASHBOARD
         ===================================================== -->

    <?php elseif ($role === 'provider'): ?>


        <!-- Provider Statistics -->

        <section class="section">

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon">
                        🎓
                    </div>

                    <span class="stat-number">
                        <?= $scholarships ?>
                    </span>

                    <div class="stat-label">
                        My Scholarships
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        📋
                    </div>

                    <span class="stat-number">
                        <?= $applications ?>
                    </span>

                    <div class="stat-label">
                        Applications Received
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ⏳
                    </div>

                    <span class="stat-number">
                        <?= $pendingApplications ?>
                    </span>

                    <div class="stat-label">
                        Pending Applications
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ✅
                    </div>

                    <span class="stat-number">
                        <?= $approvedApplications ?>
                    </span>

                    <div class="stat-label">
                        Approved Applications
                    </div>

                </div>


            </div>

        </section>



        <!-- Provider Management -->

        <section class="section">

            <div class="section-header">

                <h2>
                    Provider Management
                </h2>

            </div>


            <div class="action-grid">


                <div class="action-card">

                    <h3>
                        ➕ Add Scholarship
                    </h3>

                    <p>
                        Publish a new scholarship opportunity
                        for eligible students.
                    </p>

                    <a
                        href="add_scholarship.php"
                        class="dashboard-btn"
                    >
                        Add Scholarship
                    </a>

                </div>


                <div class="action-card">

                    <h3>
                        📚 My Scholarships
                    </h3>

                    <p>
                        View and manage all scholarships
                        published by you.
                    </p>

                    <a
                        href="provider_scholarships.php"
                        class="dashboard-btn secondary"
                    >
                        Manage Scholarships
                    </a>

                </div>


                <div class="action-card">

                    <h3>
                        📝 Applications
                    </h3>

                    <p>
                        Review student applications and
                        update their status.
                    </p>

                    <a
                        href="provider_applications.php"
                        class="dashboard-btn secondary"
                    >
                        Review Applications
                    </a>

                </div>


            </div>

        </section>



    <!-- =====================================================
         ADMIN DASHBOARD
         ===================================================== -->

    <?php elseif ($role === 'admin'): ?>


        <!-- Admin Statistics -->

        <section class="section">

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon">
                        👥
                    </div>

                    <span class="stat-number">
                        <?= $users ?>
                    </span>

                    <div class="stat-label">
                        Total Users
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        🎓
                    </div>

                    <span class="stat-number">
                        <?= $scholarships ?>
                    </span>

                    <div class="stat-label">
                        Scholarships
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        📋
                    </div>

                    <span class="stat-number">
                        <?= $applications ?>
                    </span>

                    <div class="stat-label">
                        Applications
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        ⏳
                    </div>

                    <span class="stat-number">
                        <?= $pendingApplications ?>
                    </span>

                    <div class="stat-label">
                        Pending Applications
                    </div>

                </div>


            </div>

        </section>



        <!-- Admin Management -->

        <section class="section">

            <div class="section-header">

                <h2>
                    Administration
                </h2>

            </div>


            <div class="action-grid">


                <div class="action-card">

                    <h3>
                        👥 Manage Users
                    </h3>

                    <p>
                        Manage student, provider and
                        administrator accounts.
                    </p>

                    <a
                        href="admin_users.php"
                        class="dashboard-btn"
                    >
                        Manage Users
                    </a>

                </div>


                <div class="action-card">

                    <h3>
                        🎓 Manage Scholarships
                    </h3>

                    <p>
                        View, search and manage all
                        scholarships.
                    </p>

                    <a
                        href="admin_scholarships.php"
                        class="dashboard-btn secondary"
                    >
                        Manage Scholarships
                    </a>

                </div>


                <div class="action-card">

                    <h3>
                        📋 Manage Applications
                    </h3>

                    <p>
                        Review all applications and
                        update their status.
                    </p>

                    <a
                        href="admin_applications.php"
                        class="dashboard-btn secondary"
                    >
                        Manage Applications
                    </a>

                </div>


                <div class="action-card">

                    <h3>
                        📊 Reports
                    </h3>

                    <p>
                        View system statistics,
                        application analytics and reports.
                    </p>

                    <a
                        href="admin_reports.php"
                        class="dashboard-btn secondary"
                    >
                        View Reports
                    </a>

                </div>


            </div>

        </section>


    <?php endif; ?>


</main>


</body>

</html>