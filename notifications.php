<?php

require_once 'config.php';

require_role('student');

$student_id = (int)user()['id'];


// ============================================================
// MARK NOTIFICATION AS READ
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['mark_read'])
) {

    check_csrf();

    $notification_id = (int)(
        $_POST['notification_id'] ?? 0
    );

    if ($notification_id > 0) {

        $st = $conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
              AND user_id = ?
        ");

        $st->bind_param(
            "ii",
            $notification_id,
            $student_id
        );

        $st->execute();

        $st->close();
    }

    header('Location: notifications.php');

    exit;
}


// ============================================================
// MARK ALL AS READ
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['mark_all_read'])
) {

    check_csrf();

    $st = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
          AND is_read = 0
    ");

    $st->bind_param(
        "i",
        $student_id
    );

    $st->execute();

    $st->close();

    header('Location: notifications.php');

    exit;
}


// ============================================================
// GET NOTIFICATIONS
// ============================================================
$st = $conn->prepare("
    SELECT
        id,
        message,
        is_read,
        created_at

    FROM notifications

    WHERE user_id = ?

    ORDER BY created_at DESC

    LIMIT 50
");

$st->bind_param(
    "i",
    $student_id
);

$st->execute();

$notifications = $st->get_result();


// ============================================================
// UNREAD COUNT
// ============================================================

$st = $conn->prepare("
    SELECT COUNT(*) AS total

    FROM notifications

    WHERE user_id = ?

      AND is_read = 0
");

$st->bind_param(
    "i",
    $student_id
);

$st->execute();

$unread = (int)$st
    ->get_result()
    ->fetch_assoc()['total'];

$st->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Notifications - ScholarMatch
    </title>

    <?php include 'partials/head.php'; ?>


    <style>

        /* =====================================================
           PAGE
           ===================================================== */

        .notifications-page {

            max-width: 900px;

            margin: 0 auto;

            padding: 30px 20px 60px;

        }


        /* =====================================================
           HEADER
           ===================================================== */

        .notification-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 25px;

            flex-wrap: wrap;

        }


        .notification-header h1 {

            margin: 0;

            color: #0f172a;

            font-size: 30px;

        }


        .notification-count {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 28px;

            height: 28px;

            padding: 0 8px;

            margin-left: 5px;

            border-radius: 999px;

            background: #dc2626;

            color: #ffffff;

            font-size: 12px;

            font-weight: 800;

        }


        /* =====================================================
           MARK ALL BUTTON
           ===================================================== */

        .mark-all-btn {

            border: none;

            background: #eff6ff;

            color: #1d4ed8;

            padding: 10px 14px;

            border-radius: 9px;

            font-weight: 700;

            cursor: pointer;

            font-size: 14px;

        }


        .mark-all-btn:hover {

            background: #dbeafe;

        }


        /* =====================================================
           NOTIFICATION LIST
           ===================================================== */

        .notification-list {

            display: flex;

            flex-direction: column;

            gap: 12px;

        }


        /* =====================================================
           NOTIFICATION CARD
           ===================================================== */

        .notification-card {

            display: flex;

            gap: 15px;

            padding: 18px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 14px;

            box-shadow:
                0 4px 15px
                rgba(15, 23, 42, 0.04);

        }


        .notification-card.unread {

            border-left: 4px solid #2563eb;

            background: #f8faff;

        }


        /* =====================================================
           ICON
           ===================================================== */

        .notification-icon {

            width: 44px;

            height: 44px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background: #eff6ff;

            font-size: 21px;

        }


        /* =====================================================
           CONTENT
           ===================================================== */

        .notification-content {

            flex: 1;

            min-width: 0;

        }


        .notification-title {

            font-size: 16px;

            font-weight: 800;

            color: #0f172a;

            margin-bottom: 5px;

        }


        .notification-message {

            color: #475569;

            line-height: 1.6;

            font-size: 14px;

        }


        .notification-time {

            margin-top: 8px;

            color: #94a3b8;

            font-size: 12px;

        }


        /* =====================================================
           ACTION
           ===================================================== */

        .notification-actions {

            display: flex;

            align-items: center;

            flex-shrink: 0;

        }


        .read-btn {

            border: none;

            background: #f1f5f9;

            color: #475569;

            padding: 8px 10px;

            border-radius: 8px;

            cursor: pointer;

            font-size: 12px;

            font-weight: 700;

        }


        .read-btn:hover {

            background: #e2e8f0;

        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .empty-state {

            text-align: center;

            padding: 60px 20px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 16px;

            color: #64748b;

        }


        .empty-icon {

            font-size: 45px;

            margin-bottom: 10px;

        }


        .empty-state h2 {

            color: #0f172a;

            margin-bottom: 8px;

        }


        /* =====================================================
           MOBILE
           ===================================================== */

        @media (max-width: 600px) {

            .notifications-page {

                padding: 20px 12px 40px;

            }


            .notification-header {

                align-items: flex-start;

            }


            .notification-header h1 {

                font-size: 26px;

            }


            .notification-card {

                flex-direction: column;

            }


            .notification-actions {

                justify-content: flex-start;

            }


            .mark-all-btn {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<div class="container">

    <div class="notifications-page">


        <!-- =================================================
             HEADER
             ================================================= -->

        <div class="notification-header">

            <div>

                <h1>

                    🔔 Notifications

                    <?php if ($unread > 0): ?>

                        <span class="notification-count">

                            <?= $unread ?>

                        </span>

                    <?php endif; ?>

                </h1>

            </div>


            <?php if ($unread > 0): ?>

                <form
                    method="POST"
                >

                    <!-- CSRF TOKEN -->
                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= csrf() ?>"
                    >

                    <button
                        type="submit"
                        name="mark_all_read"
                        value="1"
                        class="mark-all-btn"
                    >

                        ✓ Mark all as read

                    </button>

                </form>

            <?php endif; ?>


        </div>


        <!-- =================================================
             NOTIFICATIONS
             ================================================= -->

        <?php if (
            $notifications &&
            $notifications->num_rows > 0
        ): ?>


            <div class="notification-list">


                <?php while (
                    $notification =
                    $notifications->fetch_assoc()
                ): ?>


                    <div
                        class="
                            notification-card
                            <?= !$notification['is_read']
                                ? 'unread'
                                : '' ?>
                        "
                    >


                        <!-- =================================================
                             NOTIFICATION ICON
                             ================================================= -->

                        <div class="notification-icon">

                            <?php if (
                                $notification['title']
                                === 'Application Approved'
                            ): ?>

                                ✅

                            <?php elseif (
                                $notification['title']
                                === 'Application Rejected'
                            ): ?>

                                ❌

                            <?php else: ?>

                                🔔

                            <?php endif; ?>

                        </div>


                        <!-- =================================================
                             NOTIFICATION CONTENT
                             ================================================= -->

                        <div class="notification-content">

                            <div class="notification-title">

                                <?= e(
                                    $notification['title']
                                ) ?>

                            </div>


                            <div class="notification-message">

                                <?= e(
                                    $notification['message']
                                ) ?>

                            </div>


                            <div class="notification-time">

                                <?= e(
                                    $notification['created_at']
                                ) ?>

                            </div>

                        </div>


                        <!-- =================================================
                             MARK AS READ
                             ================================================= -->

                        <?php if (
                            !$notification['is_read']
                        ): ?>

                            <div
                                class="notification-actions"
                            >

                                <form
                                    method="POST"
                                >

                                    <!-- CSRF TOKEN -->
                                    <input
                                        type="hidden"
                                        name="csrf"
                                        value="<?= csrf() ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="notification_id"
                                        value="<?= (int)$notification['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="mark_read"
                                        value="1"
                                        class="read-btn"
                                    >

                                        Mark read

                                    </button>

                                </form>

                            </div>

                        <?php endif; ?>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <!-- =================================================
                 EMPTY STATE
                 ================================================= -->

            <div class="empty-state">

                <div class="empty-icon">

                    🔔

                </div>


                <h2>

                    No notifications

                </h2>


                <p>

                    You don't have any notifications yet.

                </p>

            </div>


        <?php endif; ?>


    </div>

</div>


</body>

</html>