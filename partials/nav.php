<?php

require_once 'config.php';

$current_user = user();

$unread_notifications = 0;

/*
|--------------------------------------------------------------------------
| STUDENT UNREAD NOTIFICATIONS
|--------------------------------------------------------------------------
*/

if (
    $current_user &&
    $current_user['role'] === 'student'
) {

    $notification_user_id =
        (int)$current_user['id'];

    $notification_stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM notifications
         WHERE user_id = ?
           AND is_read = 0"
    );

    if ($notification_stmt) {

        $notification_stmt->bind_param(
            "i",
            $notification_user_id
        );

        $notification_stmt->execute();

        $notification_result =
            $notification_stmt->get_result();

        if ($notification_result) {

            $row =
                $notification_result->fetch_assoc();

            $unread_notifications =
                (int)($row['total'] ?? 0);
        }

        $notification_stmt->close();
    }
}

?>


<style>

/* =========================================================
   NAVIGATION
========================================================= */

.main-nav {

    width: 100%;

    background: #ffffff;

    border-bottom: 1px solid #e2e8f0;

    position: sticky;

    top: 0;

    z-index: 1000;
}


.nav-container {

    width: min(1400px, 94%);

    min-height: 68px;

    margin: 0 auto;

    display: flex;

    align-items: center;

    gap: 18px;
}


/* =========================================================
   LOGO
========================================================= */

.nav-logo {

    display: inline-flex;

    align-items: center;

    gap: 9px;

    text-decoration: none;

    color: #0f172a;

    font-size: 20px;

    font-weight: 800;

    white-space: nowrap;

    flex-shrink: 0;
}


.nav-logo-icon {

    width: 36px;

    height: 36px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background: #2563eb;

    color: #ffffff;

    font-size: 18px;
}


.nav-logo:hover {

    color: #2563eb;
}


/* =========================================================
   NAV LINKS
========================================================= */

.nav-links {

    display: flex;

    align-items: center;

    gap: 4px;

    flex-wrap: nowrap;

    margin-left: auto;
}


.nav-link {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 4px;

    padding: 8px 7px;

    border-radius: 8px;

    text-decoration: none;

    color: #475569;

    font-size: 14px;

    font-weight: 600;

    white-space: nowrap;

    transition: 0.2s ease;
}


.nav-link:hover {

    background: #f1f5f9;

    color: #2563eb;
}


/* =========================================================
   RIGHT SIDE
========================================================= */

.nav-right {

    display: flex;

    align-items: center;

    gap: 4px;

    flex-shrink: 0;
}


/* =========================================================
   NOTIFICATION
========================================================= */

.nav-notification {

    position: relative;

    width: 38px;

    height: 38px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    text-decoration: none;

    color: #334155;

    font-size: 18px;

    transition: 0.2s ease;
}


.nav-notification:hover {

    background: #f1f5f9;

    color: #2563eb;
}


.notification-badge {

    position: absolute;

    top: -2px;

    right: -2px;

    min-width: 18px;

    height: 18px;

    padding: 0 4px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 999px;

    background: #dc2626;

    color: #ffffff;

    border: 2px solid #ffffff;

    font-size: 9px;

    line-height: 1;

    font-weight: 800;
}


/* =========================================================
   PROFILE BUTTON
========================================================= */

.userpill {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 8px 10px;

    border-radius: 9px;

    background: #f1f5f9;

    color: #334155;

    text-decoration: none;

    font-size: 13px;

    font-weight: 700;

    white-space: nowrap;

    transition: 0.2s ease;
}


.userpill:hover {

    background: #e2e8f0;

    color: #2563eb;
}


/* =========================================================
   MOBILE TOGGLE
========================================================= */

.nav-toggle {

    display: none;

    border: none;

    background: #f1f5f9;

    color: #334155;

    width: 40px;

    height: 40px;

    border-radius: 9px;

    font-size: 20px;

    cursor: pointer;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 900px) {

    .nav-container {

        min-height: 62px;

        flex-wrap: wrap;

        padding: 10px 0;
    }


    .nav-toggle {

        display: inline-flex;

        align-items: center;

        justify-content: center;

        margin-left: auto;
    }


    .nav-links {

        display: none;

        width: 100%;

        order: 3;

        flex-direction: column;

        align-items: stretch;

        gap: 3px;

        margin-left: 0;

        padding-top: 6px;
    }


    .nav-links.show {

        display: flex;
    }


    .nav-link {

        justify-content: flex-start;

        padding: 10px 12px;
    }


    .nav-right {

        margin-left: 0;
    }
}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 600px) {

    .nav-container {

        width: 92%;
    }


    .nav-logo {

        font-size: 18px;
    }


    .nav-logo-icon {

        width: 34px;

        height: 34px;
    }


    .userpill {

        max-width: 140px;

        overflow: hidden;

        white-space: nowrap;

        text-overflow: ellipsis;
    }
}

</style>


<nav class="main-nav">

    <div class="nav-container">


        <!-- =================================================
             LOGO
        ================================================== -->

        <a
            href="index.php"
            class="nav-logo"
        >

            <span class="nav-logo-icon">
                🎓
            </span>

            <span>
                Scholarship Recommendations
            </span>

        </a>


        <!-- =================================================
             MOBILE BUTTON
        ================================================== -->

        <button
            type="button"
            class="nav-toggle"
            onclick="toggleNavigation()"
            aria-label="Open navigation"
        >
            ☰
        </button>


        <!-- =================================================
             MAIN NAVIGATION
        ================================================== -->

        <div
            class="nav-links"
            id="mainNavLinks"
        >


            <?php if ($current_user): ?>


                <!-- =================================================
                     STUDENT
                ================================================== -->

                <?php if (
                    $current_user['role'] === 'student'
                ): ?>

                    <a
                        href="dashboard.php"
                        class="nav-link"
                    >
                        🏠 Dashboard
                    </a>

                    <a
                        href="scholarships.php"
                        class="nav-link"
                    >
                        🏆 Scholarships
                    </a>

                    <a
                        href="recommendations.php"
                        class="nav-link"
                    >
                        🎯 Recommendations
                    </a>

                    <a
                        href="applications.php"
                        class="nav-link"
                    >
                        📋 Applications
                    </a>


                <!-- =================================================
                     PROVIDER
                ================================================== -->

                <?php elseif (
                    $current_user['role'] === 'provider'
                ): ?>

                    <a
                        href="dashboard.php"
                        class="nav-link"
                    >
                        🏠 Dashboard
                    </a>

                    <a
                        href="provider_scholarships.php"
                        class="nav-link"
                    >
                        🏆 My Scholarships
                    </a>

                    <a
                        href="provider_applications.php"
                        class="nav-link"
                    >
                        📋 Applications
                    </a>


                <!-- =================================================
                     ADMIN
                ================================================== -->

                <?php elseif (
                    $current_user['role'] === 'admin'
                ): ?>

                    <a
                        href="admin.php"
                        class="nav-link"
                    >
                        🛡️ Admin Dashboard
                    </a>

                    <a
                        href="admin_users.php"
                        class="nav-link"
                    >
                        👥 Users
                    </a>

                    <a
                        href="admin_scholarships.php"
                        class="nav-link"
                    >
                        🏆 Scholarships
                    </a>

                    <a
                        href="admin_applications.php"
                        class="nav-link"
                    >
                        📋 Applications
                    </a>

                    <a
                        href="admin_reports.php"
                        class="nav-link"
                    >
                        📊 Reports
                    </a>

                <?php endif; ?>


            <?php else: ?>


                <!-- =================================================
                     GUEST
                ================================================== -->

                <a
                    href="index.php"
                    class="nav-link"
                >
                    🏠 Home
                </a>

                <a
                    href="scholarships.php"
                    class="nav-link"
                >
                    🏆 Scholarships
                </a>

                <a
                    href="login.php"
                    class="nav-link"
                >
                    🔐 Login
                </a>

                <a
                    href="register.php"
                    class="nav-link"
                >
                    📝 Register
                </a>

            <?php endif; ?>


        </div>


        <!-- =================================================
             USER AREA
        ================================================== -->

        <?php if ($current_user): ?>

            <div class="nav-right">


                <!-- =================================================
                     STUDENT NOTIFICATIONS
                ================================================== -->

                <?php if (
                    $current_user['role'] === 'student'
                ): ?>

                    <a
                        href="notifications.php"
                        class="nav-notification"
                        title="Notifications"
                    >

                        🔔

                        <?php if (
                            $unread_notifications > 0
                        ): ?>

                            <span class="notification-badge">

                                <?= $unread_notifications > 99
                                    ? '99+'
                                    : $unread_notifications ?>

                            </span>

                        <?php endif; ?>

                    </a>

                <?php endif; ?>


                <!-- =================================================
                     PROFILE
                ================================================== -->

                <?php if (
                    $current_user['role'] === 'student'
                ): ?>

                    <a
                        href="profile.php"
                        class="userpill"
                        title="View Profile"
                    >
                        👤
                        <?= e($current_user['name']) ?>
                    </a>


                <?php elseif (
                    $current_user['role'] === 'admin'
                ): ?>

                    <!-- ADMIN PROFILE -->

                    <a
                        href="admin_profile.php"
                        class="userpill"
                        title="View Admin Profile"
                    >
                        👤
                        <?= e($current_user['name']) ?>
                    </a>


                <?php elseif (
                    $current_user['role'] === 'provider'
                ): ?>

                    <!-- PROVIDER PROFILE -->

                    <a
                        href="provider_profile.php"
                        class="userpill"
                        title="View Provider Profile"
                    >
                        👤
                        <?= e($current_user['name']) ?>
                    </a>

                <?php endif; ?>


                <!-- =================================================
                     LOGOUT
                ================================================== -->

                <a
                    href="logout.php"
                    class="nav-link"
                >
                    🚪 Logout
                </a>


            </div>

        <?php endif; ?>


    </div>

</nav>


<script>

function toggleNavigation() {

    const nav =
        document.getElementById(
            'mainNavLinks'
        );

    if (!nav) {

        return;
    }

    nav.classList.toggle('show');
}

</script>