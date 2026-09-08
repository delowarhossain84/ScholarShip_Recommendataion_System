<?php

require_once 'config.php';

/*
|--------------------------------------------------------------------------
| ADMIN ONLY
|--------------------------------------------------------------------------
*/

require_role('admin');

$current_user = user();

$admin_id = (int)$current_user['id'];

$error = '';
$success = '';

$name = '';
$email = '';
$created_at = '';


/*
|--------------------------------------------------------------------------
| LOAD ADMIN PROFILE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        id,
        name,
        email,
        role,
        created_at
     FROM users
     WHERE id = ?
       AND role = 'admin'
     LIMIT 1"
);

$stmt->bind_param(
    "i",
    $admin_id
);

$stmt->execute();

$result = $stmt->get_result();

$admin = $result->fetch_assoc();

$stmt->close();


if (!$admin) {

    http_response_code(404);

    die('Admin profile not found.');
}


$name = $admin['name'];

$email = $admin['email'];

$created_at = $admin['created_at'];


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF PROTECTION
    |--------------------------------------------------------------------------
    */

    check_csrf();


    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    $name = trim(
        $_POST['name'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error =
            'Please enter your full name.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE EMAIL
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
               AND id != ?
             LIMIT 1"
        );

        $check->bind_param(
            "si",
            $email,
            $admin_id
        );

        $check->execute();

        $existing =
            $check
                ->get_result()
                ->fetch_assoc();

        $check->close();


        if ($existing) {

            $error =
                'This email address is already being used.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE DATABASE
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $update = $conn->prepare(
            "UPDATE users
             SET
                name = ?,
                email = ?
             WHERE id = ?
               AND role = 'admin'"
        );

        $update->bind_param(
            "ssi",
            $name,
            $email,
            $admin_id
        );


        if ($update->execute()) {

            /*
            |--------------------------------------------------------------------------
            | UPDATE SESSION
            |--------------------------------------------------------------------------
            */

            $_SESSION['user']['name'] =
                $name;

            $_SESSION['user']['email'] =
                $email;


            $success =
                'Admin profile updated successfully.';

        } else {

            $error =
                'Unable to update your profile. Please try again.';
        }


        $update->close();
    }
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

    <title>
        Admin Profile | Scholarship Recommendations
    </title>


    <?php include 'partials/head.php'; ?>


    <style>

        body {

            background: #f8fafc;

        }


        /* =====================================================
           PAGE
        ===================================================== */

        .admin-profile-page {

            max-width: 760px;

            margin: 0 auto;

            padding: 40px 20px 60px;

        }


        /* =====================================================
           HEADER
        ===================================================== */

        .profile-header {

            margin-bottom: 25px;

        }


        .profile-header h1 {

            margin: 0 0 8px;

            color: #0f172a;

            font-size: 32px;

            font-weight: 800;

        }


        .profile-header p {

            margin: 0;

            color: #64748b;

            font-size: 15px;

        }


        /* =====================================================
           CARD
        ===================================================== */

        .profile-card {

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 18px;

            padding: 30px;

            box-shadow:
                0 8px 30px
                rgba(15, 23, 42, 0.06);

        }


        /* =====================================================
           ICON
        ===================================================== */

        .profile-icon {

            width: 70px;

            height: 70px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 14px;

            border-radius: 50%;

            background: #eff6ff;

            font-size: 32px;

        }


        /* =====================================================
           ROLE BADGE
        ===================================================== */

        .role-badge {

            display: inline-block;

            padding: 6px 12px;

            margin-bottom: 25px;

            border-radius: 999px;

            background: #eff6ff;

            color: #2563eb;

            font-size: 12px;

            font-weight: 800;

        }


        /* =====================================================
           INFORMATION BOX
        ===================================================== */

        .account-info {

            margin-bottom: 25px;

            padding: 15px;

            border-radius: 10px;

            background: #f8fafc;

            color: #64748b;

            font-size: 13px;

        }


        .account-info strong {

            color: #334155;

        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-group {

            margin-bottom: 20px;

        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            color: #334155;

            font-size: 14px;

            font-weight: 700;

        }


        .form-group input {

            width: 100%;

            box-sizing: border-box;

            padding: 13px 14px;

            border: 1px solid #cbd5e1;

            border-radius: 10px;

            background: #ffffff;

            color: #0f172a;

            font-size: 14px;

            outline: none;

        }


        .form-group input:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);

        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .save-button {

            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 10px;

            background: #2563eb;

            color: #ffffff;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s ease;

        }


        .save-button:hover {

            background: #1d4ed8;

        }


        /* =====================================================
           ALERTS
        ===================================================== */

        .alert {

            padding: 13px 15px;

            margin-bottom: 20px;

            border-radius: 10px;

            font-size: 14px;

            font-weight: 600;

        }


        .alert-error {

            background: #fef2f2;

            border: 1px solid #fecaca;

            color: #b91c1c;

        }


        .alert-success {

            background: #f0fdf4;

            border: 1px solid #bbf7d0;

            color: #166534;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .admin-profile-page {

                padding: 25px 15px 40px;

            }


            .profile-card {

                padding: 20px;

            }


            .profile-header h1 {

                font-size: 27px;

            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<main class="admin-profile-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="profile-header">

        <h1>
            Admin Profile
        </h1>

        <p>
            Manage your administrator account information.
        </p>

    </div>


    <!-- =====================================================
         ERROR MESSAGE
    ====================================================== -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-error">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         SUCCESS MESSAGE
    ====================================================== -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?= e($success) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         PROFILE CARD
    ====================================================== -->

    <div class="profile-card">


        <!-- ADMIN ICON -->

        <div class="profile-icon">

            🛡️

        </div>


        <!-- ROLE -->

        <span class="role-badge">

            SYSTEM ADMIN

        </span>


        <!-- ACCOUNT INFORMATION -->

        <div class="account-info">

            <strong>
                Account Type:
            </strong>

            Administrator

            <br>

            <strong>
                Account Created:
            </strong>

            <?= e(
                date(
                    'F d, Y',
                    strtotime($created_at)
                )
            ) ?>

        </div>


        <!-- =================================================
             PROFILE FORM
        ================================================== -->

        <form
            method="POST"
            action="admin_profile.php"
        >


            <!-- CSRF TOKEN -->

            <input
                type="hidden"
                name="csrf"
                value="<?= csrf() ?>"
            >


            <!-- NAME -->

            <div class="form-group">

                <label for="name">

                    Full Name

                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= e($name) ?>"
                    maxlength="120"
                    required
                >

            </div>


            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">

                    Email Address

                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($email) ?>"
                    maxlength="150"
                    required
                >

            </div>


            <!-- SAVE -->

            <button
                type="submit"
                class="save-button"
            >

                ✓ Save Changes

            </button>


        </form>


    </div>

</main>


</body>

</html>