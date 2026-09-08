<?php
require_once 'config.php';

/*
|--------------------------------------------------------------------------
| Redirect logged-in users
|--------------------------------------------------------------------------
*/

if (auth()) {
    header('Location: dashboard.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$error = '';
$email = '';

/*
|--------------------------------------------------------------------------
| Handle Login
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    */

    check_csrf();

    /*
    |--------------------------------------------------------------------------
    | Get Form Data
    |--------------------------------------------------------------------------
    */

    $email = trim(
        $_POST['email'] ?? ''
    );

    $password =
        $_POST['password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Validate
    |--------------------------------------------------------------------------
    */

    if (!filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )) {

        $error =
            'Please enter a valid email address.';

    } elseif ($password === '') {

        $error =
            'Please enter your password.';
    }

    /*
    |--------------------------------------------------------------------------
    | Find User
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $stmt = $conn->prepare(
            "SELECT
                id,
                name,
                email,
                password,
                role,
                education_level,
                field_of_study,
                cgpa,
                income,
                country,
                ielts_score,
                gre_score,
                extracurricular,
                created_at
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param(
            "s",
            $email
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        $account =
            $result->fetch_assoc();

        $stmt->close();

        /*
        |--------------------------------------------------------------------------
        | Verify Password
        |--------------------------------------------------------------------------
        */

        $valid_password = false;

        if ($account) {

            /*
            |--------------------------------------------------------------------------
            | Modern password_hash() verification
            |--------------------------------------------------------------------------
            */

            if (
                password_verify(
                    $password,
                    $account['password']
                )
            ) {

                $valid_password = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Legacy MD5 support
            |--------------------------------------------------------------------------
            |
            | Some older demo accounts may still have MD5 passwords.
            | If verified, upgrade them automatically.
            |
            */

            elseif (
                hash_equals(
                    $account['password'],
                    md5($password)
                )
            ) {

                $valid_password = true;

                /*
                |--------------------------------------------------------------------------
                | Upgrade MD5 password
                |--------------------------------------------------------------------------
                */

                $new_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $upgrade = $conn->prepare(
                    "UPDATE users
                     SET password = ?
                     WHERE id = ?"
                );

                $upgrade->bind_param(
                    "si",
                    $new_hash,
                    $account['id']
                );

                $upgrade->execute();

                $upgrade->close();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Login Failed
        |--------------------------------------------------------------------------
        */

        if (!$account || !$valid_password) {

            $error =
                'Invalid email or password.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Regenerate Session ID
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);

            /*
            |--------------------------------------------------------------------------
            | Store User Session
            |--------------------------------------------------------------------------
            */

            $_SESSION['user'] = [
                'id' =>
                    (int)$account['id'],

                'name' =>
                    $account['name'],

                'email' =>
                    $account['email'],

                'role' =>
                    $account['role'],

                'education_level' =>
                    $account['education_level'],

                'field_of_study' =>
                    $account['field_of_study'],

                'cgpa' =>
                    $account['cgpa'],

                'income' =>
                    $account['income'],

                'country' =>
                    $account['country'],

                'ielts_score' =>
                    $account['ielts_score'],

                'gre_score' =>
                    $account['gre_score'],

                'extracurricular' =>
                    $account['extracurricular'],

                'created_at' =>
                    $account['created_at']
            ];

            /*
            |--------------------------------------------------------------------------
            | Success Message
            |--------------------------------------------------------------------------
            */

            flash(
                'Login successful.',
                'success'
            );

            /*
            |--------------------------------------------------------------------------
            | Dashboard
            |--------------------------------------------------------------------------
            */

            header('Location: dashboard.php');
            exit;
        }
    }
}
?>

<?php require 'partials/head.php'; ?>

<style>

    body {
        background: #f8fafc;
    }

    .login-wrapper {
        min-height: calc(100vh - 80px);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
    }

    .login-card {
        width: 100%;
        max-width: 430px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 34px;
        box-shadow:
            0 12px 40px rgba(15, 23, 42, 0.08);
    }

    .login-icon {
        width: 58px;
        height: 58px;
        margin: 0 auto 18px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #eff6ff;
        border-radius: 16px;

        font-size: 30px;
    }

    .login-title {
        margin: 0;
        text-align: center;

        font-size: 30px;
        font-weight: 800;

        color: #0f172a;
    }

    .login-subtitle {
        margin: 8px 0 28px;

        text-align: center;

        color: #64748b;
    }

    .error-box {
        background: #fef2f2;
        color: #b91c1c;

        border: 1px solid #fecaca;

        padding: 12px 14px;

        border-radius: 10px;

        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;

        margin-bottom: 7px;

        color: #334155;

        font-weight: 700;
    }

    .form-group input {
        width: 100%;
        box-sizing: border-box;

        padding: 13px 14px;

        border: 1px solid #cbd5e1;
        border-radius: 10px;

        font-size: 15px;

        outline: none;
    }

    .form-group input:focus {
        border-color: #2563eb;

        box-shadow:
            0 0 0 3px
            rgba(37, 99, 235, 0.10);
    }

    .login-btn {
        width: 100%;

        border: none;

        background: #2563eb;

        color: white;

        padding: 14px;

        border-radius: 10px;

        font-size: 16px;
        font-weight: 700;

        cursor: pointer;
    }

    .login-btn:hover {
        background: #1d4ed8;
    }

    .register-link {
        text-align: center;

        margin-top: 22px;

        color: #64748b;
    }

    .register-link a {
        color: #2563eb;

        font-weight: 700;

        text-decoration: none;
    }

    @media (max-width: 500px) {

        .login-card {
            padding: 24px;
        }

        .login-title {
            font-size: 26px;
        }
    }

</style>


<div class="login-wrapper">

    <div class="login-card">

        <div class="login-icon">
            🔐
        </div>

        <h1 class="login-title">
            Welcome Back
        </h1>

        <p class="login-subtitle">
            Login to your Scholarship Recommendations.
        </p>


        <?php if ($error !== ''): ?>

            <div class="error-box">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="login.php"
            autocomplete="on"
        >

            <!-- =================================================
                 CSRF TOKEN
                 IMPORTANT: Hidden so it is NOT visible.
                 ================================================= -->

            <input
                type="hidden"
                name="csrf"
                value="<?= csrf() ?>"
            >


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
                    autocomplete="email"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >

            </div>


            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                class="login-btn"
            >
                Login
            </button>

        </form>


        <div class="register-link">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </div>

    </div>

</div>

<?php require 'partials/foot.php'; ?>