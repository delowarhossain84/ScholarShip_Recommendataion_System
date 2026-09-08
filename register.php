<?php

require_once 'config.php';


/*
|--------------------------------------------------------------------------
| Redirect Logged-in Users
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

$name = '';
$email = '';

$role = 'student';

$education_level = '';
$field_of_study = '';

$cgpa = '';
$income = '';

$country = '';

$ielts_score = '';
$gre_score = '';

$extracurricular = '';


/*
|--------------------------------------------------------------------------
| Handle Registration
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
    | Get Basic Information
    |--------------------------------------------------------------------------
    */

    $name =
        trim(
            $_POST['name'] ?? ''
        );

    $email =
        trim(
            $_POST['email'] ?? ''
        );

    $password =
        $_POST['password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';

    $role =
        $_POST['role'] ?? 'student';


    /*
    |--------------------------------------------------------------------------
    | Get Student Information
    |--------------------------------------------------------------------------
    */

    $education_level =
        trim(
            $_POST['education_level'] ?? ''
        );

    $field_of_study =
        trim(
            $_POST['field_of_study'] ?? ''
        );

    $cgpa =
        trim(
            $_POST['cgpa'] ?? ''
        );

    $income =
        trim(
            $_POST['income'] ?? ''
        );

    $country =
        trim(
            $_POST['country'] ?? ''
        );

    $ielts_score =
        trim(
            $_POST['ielts_score'] ?? ''
        );

    $gre_score =
        trim(
            $_POST['gre_score'] ?? ''
        );

    $extracurricular =
        trim(
            $_POST['extracurricular'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | Validate Basic Information
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $error =
            'Please enter your full name.';

    }

    elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';

    }

    elseif (
        strlen($password) < 8
    ) {

        $error =
            'Password must be at least 8 characters long.';

    }

    elseif (
        $password !== $confirm_password
    ) {

        $error =
            'Passwords do not match.';

    }

    elseif (
        !in_array(
            $role,
            ['student', 'provider'],
            true
        )
    ) {

        $error =
            'Invalid account type.';

    }


    /*
    |--------------------------------------------------------------------------
    | Provider Account
    |--------------------------------------------------------------------------
    |
    | Providers do not need student academic information.
    |
    */

    if (
        $error === '' &&
        $role === 'provider'
    ) {

        $education_level = '';

        $field_of_study = '';

        $cgpa = '';

        $income = '';

        $country = '';

        $ielts_score = '';

        $gre_score = '';

        $extracurricular = '';
    }


    /*
    |--------------------------------------------------------------------------
    | Student Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === '' &&
        $role === 'student'
    ) {


        /*
        |--------------------------------------------------------------------------
        | Education Level
        |--------------------------------------------------------------------------
        */

        $allowed_education = [

            'Undergraduate',

            'Graduate',

            'Postgraduate',

            'Diploma'

        ];


        if (
            !in_array(
                $education_level,
                $allowed_education,
                true
            )
        ) {

            $error =
                'Please select a valid education level.';
        }


        /*
        |--------------------------------------------------------------------------
        | Field of Study
        |--------------------------------------------------------------------------
        */

        $allowed_fields = [

            'CSE',

            'EEE',

            'Civil Engineering',

            'Mechanical Engineering',

            'Architecture',

            'BBA',

            'Economics',

            'English',

            'Law',

            'Pharmacy',

            'Medicine',

            'Accounting',

            'Finance',

            'Data Science',

            'Artificial Intelligence'

        ];


        if (
            $error === '' &&
            !in_array(
                $field_of_study,
                $allowed_fields,
                true
            )
        ) {

            $error =
                'Please select a valid field of study.';
        }


        /*
        |--------------------------------------------------------------------------
        | Country
        |--------------------------------------------------------------------------
        */

        $allowed_countries = [

            'Bangladesh',

            'India',

            'Pakistan',

            'Nepal',

            'Sri Lanka',

            'United States',

            'Canada',

            'United Kingdom',

            'Australia',

            'Germany',

            'France',

            'Japan',

            'China',

            'South Korea',

            'Malaysia',

            'Saudi Arabia',

            'United Arab Emirates'

        ];


        if (
            $error === '' &&
            !in_array(
                $country,
                $allowed_countries,
                true
            )
        ) {

            $error =
                'Please select a valid country.';
        }


        /*
        |--------------------------------------------------------------------------
        | CGPA Validation
        |--------------------------------------------------------------------------
        */

        if (
            $error === '' &&
            $cgpa !== '' &&
            (
                !is_numeric($cgpa) ||
                (float)$cgpa < 0 ||
                (float)$cgpa > 4
            )
        ) {

            $error =
                'CGPA must be between 0 and 4.00.';
        }


        /*
        |--------------------------------------------------------------------------
        | Income Validation
        |--------------------------------------------------------------------------
        */

        elseif (
            $error === '' &&
            $income !== '' &&
            (
                !is_numeric($income) ||
                (float)$income < 0
            )
        ) {

            $error =
                'Income cannot be negative.';
        }


        /*
        |--------------------------------------------------------------------------
        | IELTS Validation
        |--------------------------------------------------------------------------
        */

        elseif (
            $error === '' &&
            $ielts_score !== '' &&
            (
                !is_numeric($ielts_score) ||
                (float)$ielts_score < 0 ||
                (float)$ielts_score > 9
            )
        ) {

            $error =
                'IELTS score must be between 0 and 9.';
        }


        /*
        |--------------------------------------------------------------------------
        | GRE Validation
        |--------------------------------------------------------------------------
        */

        elseif (
            $error === '' &&
            $gre_score !== '' &&
            (
                !is_numeric($gre_score) ||
                (float)$gre_score < 0 ||
                (float)$gre_score > 340
            )
        ) {

            $error =
                'GRE score must be between 0 and 340.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check Existing Email
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $check->bind_param(
            "s",
            $email
        );

        $check->execute();

        $existing =
            $check
                ->get_result()
                ->fetch_assoc();

        $check->close();


        if ($existing) {

            $error =
                'An account with this email already exists.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create Account
    |--------------------------------------------------------------------------
    */

    if ($error === '') {


        /*
        |--------------------------------------------------------------------------
        | Secure Password Hash
        |--------------------------------------------------------------------------
        */

        $hashed_password =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        /*
        |--------------------------------------------------------------------------
        | Convert Numeric Values
        |--------------------------------------------------------------------------
        */

        $cgpa_value =
            $cgpa === ''
                ? null
                : (float)$cgpa;


        $income_value =
            $income === ''
                ? null
                : (float)$income;


        $ielts_value =
            $ielts_score === ''
                ? null
                : (float)$ielts_score;


        $gre_value =
            $gre_score === ''
                ? null
                : (float)$gre_score;


        /*
        |--------------------------------------------------------------------------
        | Insert User
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "INSERT INTO users
            (
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
                extracurricular
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )"
        );


        /*
        |--------------------------------------------------------------------------
        | Bind Values
        |--------------------------------------------------------------------------
        |
        | s = string
        | d = decimal / float
        |
        */

        $stmt->bind_param(
            "ssssssddsdds",
            $name,
            $email,
            $hashed_password,
            $role,
            $education_level,
            $field_of_study,
            $cgpa_value,
            $income_value,
            $country,
            $ielts_value,
            $gre_value,
            $extracurricular
        );


        /*
        |--------------------------------------------------------------------------
        | Execute
        |--------------------------------------------------------------------------
        */

        if ($stmt->execute()) {

            flash(
                'Registration successful. Please login.',
                'success'
            );

            header(
                'Location: login.php'
            );

            exit;

        } else {

            $error =
                'Registration failed. Please try again.';
        }


        $stmt->close();
    }
}

?>

<?php require 'partials/head.php'; ?>


<style>

    body {

        background: #f8fafc;

    }


    .auth-wrapper {

        min-height:
            calc(100vh - 80px);

        display: flex;

        justify-content: center;

        align-items: center;

        padding: 40px 20px;

    }


    .auth-card {

        width: 100%;

        max-width: 720px;

        background: #ffffff;

        border: 1px solid #e2e8f0;

        border-radius: 18px;

        padding: 32px;

        box-shadow:
            0 12px 40px
            rgba(15, 23, 42, 0.08);

    }


    .auth-title {

        margin: 0 0 8px;

        text-align: center;

        font-size: 30px;

        font-weight: 800;

        color: #0f172a;

    }


    .auth-subtitle {

        margin: 0 0 28px;

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


    .form-grid {

        display: grid;

        grid-template-columns:
            repeat(2, 1fr);

        gap: 18px;

    }


    .form-group {

        display: flex;

        flex-direction: column;

        gap: 7px;

    }


    .form-group.full {

        grid-column: 1 / -1;

    }


    .form-group label {

        font-weight: 700;

        color: #334155;

    }


    .form-group input,

    .form-group select,

    .form-group textarea {

        width: 100%;

        box-sizing: border-box;

        padding: 12px 14px;

        border: 1px solid #cbd5e1;

        border-radius: 10px;

        font-size: 15px;

        outline: none;

        background: #ffffff;

    }


    .form-group textarea {

        min-height: 110px;

        resize: vertical;

    }


    .form-group input:focus,

    .form-group select:focus,

    .form-group textarea:focus {

        border-color: #2563eb;

        box-shadow:
            0 0 0 3px
            rgba(37, 99, 235, 0.10);

    }


    .section-title {

        grid-column: 1 / -1;

        margin-top: 10px;

        padding-bottom: 9px;

        border-bottom:
            1px solid #e2e8f0;

        color: #0f172a;

        font-size: 18px;

        font-weight: 800;

    }


    .student-only {

        display: contents;

    }


    .register-btn {

        width: 100%;

        border: none;

        background: #2563eb;

        color: white;

        padding: 14px;

        border-radius: 10px;

        font-size: 16px;

        font-weight: 700;

        cursor: pointer;

        margin-top: 8px;

    }


    .register-btn:hover {

        background: #1d4ed8;

    }


    .login-link {

        text-align: center;

        margin-top: 20px;

        color: #64748b;

    }


    .login-link a {

        color: #2563eb;

        font-weight: 700;

        text-decoration: none;

    }


    .role-help {

        font-size: 13px;

        color: #64748b;

        margin-top: 4px;

    }


    @media (max-width: 700px) {

        .form-grid {

            grid-template-columns: 1fr;

        }


        .form-group.full {

            grid-column: auto;

        }


        .section-title {

            grid-column: auto;

        }


        .auth-card {

            padding: 22px;

        }


        .auth-title {

            font-size: 25px;

        }

    }

</style>


<div class="auth-wrapper">


    <div class="auth-card">


        <h1 class="auth-title">

            Create Account

        </h1>


        <p class="auth-subtitle">

            Join ScholarMatch and find the right opportunities.

        </p>


        <?php if ($error !== ''): ?>

            <div class="error-box">

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="register.php"
            autocomplete="on"
        >


            <!-- CSRF TOKEN -->

            <input
                type="hidden"
                name="csrf"
                value="<?= csrf() ?>"
            >


            <div class="form-grid">


                <!-- =================================================
                     ACCOUNT INFORMATION
                     ================================================= -->

                <div class="section-title">

                    Account Information

                </div>


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
                        placeholder="Enter your full name"
                        required
                    >

                </div>


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
                        placeholder="example@email.com"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">

                        Password

                    </label>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        placeholder="Minimum 8 characters"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">

                        Confirm Password

                    </label>


                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="8"
                        placeholder="Confirm your password"
                        required
                    >

                </div>


                <div class="form-group full">

                    <label for="role">

                        Account Type

                    </label>


                    <select
                        id="role"
                        name="role"
                    >

                        <option
                            value="student"
                            <?= $role === 'student'
                                ? 'selected'
                                : '' ?>
                        >

                            Student

                        </option>


                        <option
                            value="provider"
                            <?= $role === 'provider'
                                ? 'selected'
                                : '' ?>
                        >

                            Scholarship Provider

                        </option>

                    </select>


                    <div class="role-help">

                        Students receive personalized
                        scholarship recommendations.

                        Providers can publish scholarships.

                    </div>

                </div>


                <!-- =================================================
                     STUDENT INFORMATION
                     ================================================= -->

                <div
                    id="studentFields"
                    class="student-only"
                >


                    <div class="section-title">

                        Academic Profile

                    </div>


                    <!-- EDUCATION LEVEL -->

                    <div class="form-group">

                        <label for="education_level">

                            Education Level

                        </label>


                        <select
                            id="education_level"
                            name="education_level"
                        >

                            <option value="">

                                Select education level

                            </option>


                            <option
                                value="Undergraduate"
                                <?= $education_level === 'Undergraduate'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Undergraduate

                            </option>


                            <option
                                value="Graduate"
                                <?= $education_level === 'Graduate'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Graduate

                            </option>


                            <option
                                value="Postgraduate"
                                <?= $education_level === 'Postgraduate'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Postgraduate

                            </option>


                            <option
                                value="Diploma"
                                <?= $education_level === 'Diploma'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Diploma

                            </option>

                        </select>

                    </div>


                    <!-- FIELD OF STUDY -->

                    <div class="form-group">

                        <label for="field_of_study">

                            Field of Study

                        </label>


                        <select
                            id="field_of_study"
                            name="field_of_study"
                        >

                            <option value="">

                                Select field of study

                            </option>


                            <option
                                value="CSE"
                                <?= $field_of_study === 'CSE'
                                    ? 'selected'
                                    : '' ?>
                            >

                                CSE

                            </option>


                            <option
                                value="EEE"
                                <?= $field_of_study === 'EEE'
                                    ? 'selected'
                                    : '' ?>
                            >

                                EEE

                            </option>


                            <option
                                value="Civil Engineering"
                                <?= $field_of_study === 'Civil Engineering'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Civil Engineering

                            </option>


                            <option
                                value="Mechanical Engineering"
                                <?= $field_of_study === 'Mechanical Engineering'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Mechanical Engineering

                            </option>


                            <option
                                value="Architecture"
                                <?= $field_of_study === 'Architecture'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Architecture

                            </option>


                            <option
                                value="BBA"
                                <?= $field_of_study === 'BBA'
                                    ? 'selected'
                                    : '' ?>
                            >

                                BBA

                            </option>


                            <option
                                value="Economics"
                                <?= $field_of_study === 'Economics'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Economics

                            </option>


                            <option
                                value="English"
                                <?= $field_of_study === 'English'
                                    ? 'selected'
                                    : '' ?>
                            >

                                English

                            </option>


                            <option
                                value="Law"
                                <?= $field_of_study === 'Law'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Law

                            </option>


                            <option
                                value="Pharmacy"
                                <?= $field_of_study === 'Pharmacy'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Pharmacy

                            </option>


                            <option
                                value="Medicine"
                                <?= $field_of_study === 'Medicine'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Medicine

                            </option>


                            <option
                                value="Accounting"
                                <?= $field_of_study === 'Accounting'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Accounting

                            </option>


                            <option
                                value="Finance"
                                <?= $field_of_study === 'Finance'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Finance

                            </option>


                            <option
                                value="Data Science"
                                <?= $field_of_study === 'Data Science'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Data Science

                            </option>


                            <option
                                value="Artificial Intelligence"
                                <?= $field_of_study === 'Artificial Intelligence'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Artificial Intelligence

                            </option>

                        </select>

                    </div>


                    <!-- CGPA -->

                    <div class="form-group">

                        <label for="cgpa">

                            CGPA

                        </label>


                        <input
                            type="number"
                            id="cgpa"
                            name="cgpa"
                            value="<?= e($cgpa) ?>"
                            min="0"
                            max="4"
                            step="0.01"
                            placeholder="3.50"
                        >

                    </div>


                    <!-- INCOME -->

                    <div class="form-group">

                        <label for="income">

                            Annual Family Income

                        </label>


                        <input
                            type="number"
                            id="income"
                            name="income"
                            value="<?= e($income) ?>"
                            min="0"
                            step="0.01"
                            placeholder="500000"
                        >

                    </div>


                    <!-- COUNTRY -->

                    <div class="form-group full">

                        <label for="country">

                            Country

                        </label>


                        <select
                            id="country"
                            name="country"
                        >

                            <option value="">

                                Select country

                            </option>


                            <option
                                value="Bangladesh"
                                <?= $country === 'Bangladesh'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Bangladesh

                            </option>


                            <option
                                value="India"
                                <?= $country === 'India'
                                    ? 'selected'
                                    : '' ?>
                            >

                                India

                            </option>


                            <option
                                value="Pakistan"
                                <?= $country === 'Pakistan'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Pakistan

                            </option>


                            <option
                                value="Nepal"
                                <?= $country === 'Nepal'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Nepal

                            </option>


                            <option
                                value="Sri Lanka"
                                <?= $country === 'Sri Lanka'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Sri Lanka

                            </option>


                            <option
                                value="United States"
                                <?= $country === 'United States'
                                    ? 'selected'
                                    : '' ?>
                            >

                                United States

                            </option>


                            <option
                                value="Canada"
                                <?= $country === 'Canada'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Canada

                            </option>


                            <option
                                value="United Kingdom"
                                <?= $country === 'United Kingdom'
                                    ? 'selected'
                                    : '' ?>
                            >

                                United Kingdom

                            </option>


                            <option
                                value="Australia"
                                <?= $country === 'Australia'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Australia

                            </option>


                            <option
                                value="Germany"
                                <?= $country === 'Germany'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Germany

                            </option>


                            <option
                                value="France"
                                <?= $country === 'France'
                                    ? 'selected'
                                    : '' ?>
                            >

                                France

                            </option>


                            <option
                                value="Japan"
                                <?= $country === 'Japan'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Japan

                            </option>


                            <option
                                value="China"
                                <?= $country === 'China'
                                    ? 'selected'
                                    : '' ?>
                            >

                                China

                            </option>


                            <option
                                value="South Korea"
                                <?= $country === 'South Korea'
                                    ? 'selected'
                                    : '' ?>
                            >

                                South Korea

                            </option>


                            <option
                                value="Malaysia"
                                <?= $country === 'Malaysia'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Malaysia

                            </option>


                            <option
                                value="Saudi Arabia"
                                <?= $country === 'Saudi Arabia'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Saudi Arabia

                            </option>


                            <option
                                value="United Arab Emirates"
                                <?= $country === 'United Arab Emirates'
                                    ? 'selected'
                                    : '' ?>
                            >

                                United Arab Emirates

                            </option>

                        </select>

                    </div>


                    <!-- =================================================
                         ADDITIONAL QUALIFICATIONS
                         ================================================= -->

                    <div class="section-title">

                        Additional Qualifications

                    </div>


                    <!-- IELTS -->

                    <div class="form-group">

                        <label for="ielts_score">

                            IELTS Score

                        </label>


                        <input
                            type="number"
                            id="ielts_score"
                            name="ielts_score"
                            value="<?= e($ielts_score) ?>"
                            min="0"
                            max="9"
                            step="0.5"
                            placeholder="7.5"
                        >

                    </div>


                    <!-- GRE -->

                    <div class="form-group">

                        <label for="gre_score">

                            GRE Score

                        </label>


                        <input
                            type="number"
                            id="gre_score"
                            name="gre_score"
                            value="<?= e($gre_score) ?>"
                            min="0"
                            max="340"
                            step="1"
                            placeholder="320"
                        >

                    </div>


                    <!-- EXTRACURRICULAR -->

                    <div class="form-group full">

                        <label for="extracurricular">

                            Extracurricular Activities

                        </label>


                        <textarea
                            id="extracurricular"
                            name="extracurricular"
                            maxlength="5000"
                            placeholder="Clubs, volunteering, competitions, leadership activities..."
                        ><?= e($extracurricular) ?></textarea>

                    </div>


                </div>


                <!-- =================================================
                     SUBMIT
                     ================================================= -->

                <div class="form-group full">

                    <button
                        type="submit"
                        class="register-btn"
                    >

                        Create Account

                    </button>

                </div>


            </div>

        </form>


        <div class="login-link">

            Already have an account?

            <a href="login.php">

                Login

            </a>

        </div>


    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Show / Hide Student Fields
|--------------------------------------------------------------------------
*/

function updateRegistrationFields() {

    const role =
        document.getElementById('role').value;

    const studentFields =
        document.getElementById('studentFields');


    if (role === 'student') {

        studentFields.style.display =
            'contents';

    } else {

        studentFields.style.display =
            'none';
    }
}


/*
|--------------------------------------------------------------------------
| Listen for Account Type Changes
|--------------------------------------------------------------------------
*/

document
    .getElementById('role')
    .addEventListener(
        'change',
        updateRegistrationFields
    );


/*
|--------------------------------------------------------------------------
| Set Initial State
|--------------------------------------------------------------------------
*/

updateRegistrationFields();

</script>


<?php require 'partials/foot.php'; ?>