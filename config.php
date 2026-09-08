<?php

/* =========================================================
   SESSION
   ========================================================= */

session_start();


/* =========================================================
   DATABASE CONFIGURATION
   ========================================================= */

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'scholarship_recommendation_system';


/* =========================================================
   MYSQL CONNECTION
   ========================================================= */

$conn = new mysqli(
    $host,
    $user,
    $pass
);


if ($conn->connect_error) {

    die(
        'MySQL connection failed: ' .
        $conn->connect_error
    );

}


/* =========================================================
   CHARACTER SET
   ========================================================= */

$conn->set_charset('utf8mb4');


/* =========================================================
   CREATE DATABASE
   ========================================================= */

$conn->query(
    "CREATE DATABASE IF NOT EXISTS `$db`
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_unicode_ci"
);


/* =========================================================
   SELECT DATABASE
   ========================================================= */

$conn->select_db($db);


/* =========================================================
   HELPER: ESCAPE OUTPUT
   ========================================================= */

function e($v)
{
    return htmlspecialchars(
        (string)($v ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   CURRENT USER
   ========================================================= */

function user()
{
    return $_SESSION['user'] ?? null;
}


/* =========================================================
   AUTH CHECK
   ========================================================= */

function auth()
{
    return user();
}


/* =========================================================
   REQUIRE LOGIN
   ========================================================= */

function require_login()
{

    if (!user()) {

        header('Location: login.php');

        exit;

    }

}


/* =========================================================
   REQUIRE ROLE
   ========================================================= */

function require_role($role)
{

    require_login();


    if (
        (user()['role'] ?? '') !== $role
    ) {

        header(
            'Location: dashboard.php'
        );

        exit;

    }

}


/* =========================================================
   FLASH MESSAGE
   ========================================================= */

function flash($first, $second)
{
    $types = ['success', 'error', 'warning', 'info'];
    if (in_array($first, $types, true)) {
        $type = $first;
        $msg = $second;
    } else {
        $type = $second;
        $msg = $first;
    }
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}


/* =========================================================
   SHOW FLASH MESSAGE
   ========================================================= */

function show_flash()
{

    if (
        isset($_SESSION['flash'])
    ) {

        $f = $_SESSION['flash'];

        unset(
            $_SESSION['flash']
        );


        echo
        '<div class="alert ' .
        e($f['type']) .
        '">' .
        e($f['msg']) .
        '</div>';

    }

}


/* =========================================================
   CSRF TOKEN
   ========================================================= */

function csrf()
{

    if (
        empty($_SESSION['csrf'])
    ) {

        $_SESSION['csrf'] =
            bin2hex(
                random_bytes(32)
            );

    }


    return $_SESSION['csrf'];

}


/* =========================================================
   CHECK CSRF TOKEN
   ========================================================= */

function check_csrf()
{

    if (
        !isset($_POST['csrf']) ||

        !hash_equals(
            $_SESSION['csrf'] ?? '',
            $_POST['csrf']
        )
    ) {

        die(
            'Invalid request token. Refresh and try again.'
        );

    }

}


/* =========================================================
   USERS TABLE
   ========================================================= */

$conn->query(

    "CREATE TABLE IF NOT EXISTS users (

        id INT AUTO_INCREMENT PRIMARY KEY,

        name VARCHAR(120) NOT NULL,

        email VARCHAR(150) NOT NULL UNIQUE,

        password VARCHAR(255) NOT NULL,

        role ENUM(
            'student',
            'provider',
            'admin'
        ) NOT NULL DEFAULT 'student',

        education_level VARCHAR(80)
            DEFAULT NULL,

        field_of_study VARCHAR(120)
            DEFAULT NULL,

        cgpa DECIMAL(4,2)
            DEFAULT NULL,

        income DECIMAL(12,2)
            DEFAULT NULL,

        country VARCHAR(100)
            DEFAULT NULL,

        ielts_score DECIMAL(3,1) DEFAULT NULL,
        gre_score DECIMAL(5,2) DEFAULT NULL,
        extracurricular TEXT DEFAULT NULL,

        created_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP

    ) ENGINE=InnoDB"

);


/* =========================================================
   SCHOLARSHIPS TABLE
   ========================================================= */

$conn->query(

    "CREATE TABLE IF NOT EXISTS scholarships (

        id INT AUTO_INCREMENT PRIMARY KEY,

        provider_id INT NOT NULL,

        title VARCHAR(200) NOT NULL,

        description TEXT NOT NULL,

        provider_name VARCHAR(150) NOT NULL,

        amount DECIMAL(12,2)
            DEFAULT 0,

        deadline DATE NOT NULL,

        min_cgpa DECIMAL(4,2)
            DEFAULT 0,

        education_level VARCHAR(80)
            DEFAULT 'Any',

        field_of_study VARCHAR(120)
            DEFAULT 'Any',

        country VARCHAR(100)
            DEFAULT 'Any',

        created_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY(provider_id)
            REFERENCES users(id)
            ON DELETE CASCADE

    ) ENGINE=InnoDB"

);


/* =========================================================
   APPLICATIONS TABLE
   ========================================================= */

$conn->query(

    "CREATE TABLE IF NOT EXISTS applications (

        id INT AUTO_INCREMENT PRIMARY KEY,

        scholarship_id INT NOT NULL,

        student_id INT NOT NULL,


        /* IELTS score */

        ielts_score DECIMAL(3,1)
            DEFAULT NULL,


        /* GRE score */

        gre_score DECIMAL(5,2)
            DEFAULT NULL,


        /* Extracurricular activities */

        extracurricular TEXT
            DEFAULT NULL,


        /* Personal statement */

        personal_statement TEXT
            DEFAULT NULL,


        /* Application status */

        status ENUM(
            'Pending',
            'Approved',
            'Rejected'
        ) NOT NULL DEFAULT 'Pending',


        /* Application time */

        applied_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP,


        /* Last update */

        updated_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,


        /* Prevent duplicate application */

        UNIQUE KEY unique_application (
            scholarship_id,
            student_id
        ),


        FOREIGN KEY(scholarship_id)
            REFERENCES scholarships(id)
            ON DELETE CASCADE,


        FOREIGN KEY(student_id)
            REFERENCES users(id)
            ON DELETE CASCADE

    ) ENGINE=InnoDB"

);


/* =========================================================
   APPLICATION DOCUMENTS TABLE
   ========================================================= */

$conn->query(

    "CREATE TABLE IF NOT EXISTS application_documents (

        id INT AUTO_INCREMENT PRIMARY KEY,

        application_id INT NOT NULL,

        document_type ENUM(

            'Academic Certificate',

            'IELTS Certificate',

            'GRE Certificate',

            'CV / Resume'

        ) NOT NULL,

        file_name VARCHAR(255) NOT NULL,

        file_path VARCHAR(500) NOT NULL,

        uploaded_at TIMESTAMP
            DEFAULT CURRENT_TIMESTAMP,


        FOREIGN KEY(application_id)
            REFERENCES applications(id)
            ON DELETE CASCADE

    ) ENGINE=InnoDB"

);


/* =========================================================
   STUDENT DOCUMENTS TABLE
   ========================================================= */

$conn->query(
    "CREATE TABLE IF NOT EXISTS student_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        user_id INT NOT NULL,
        document_type VARCHAR(100) DEFAULT NULL,
        file_name VARCHAR(255) DEFAULT NULL,
        file_path VARCHAR(500) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        file_size INT DEFAULT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        KEY user_id (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB"
);


/* =========================================================
   NOTIFICATIONS TABLE
   ========================================================= */

$conn->query(
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        application_id INT DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'application',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY fk_notifications_user (user_id),
        KEY fk_notifications_application (application_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL
    ) ENGINE=InnoDB"
);


/* =========================================================
   MIGRATION FOR OLD USERS TABLE
   ========================================================= */

foreach ([
    'ielts_score' => "DECIMAL(3,1) DEFAULT NULL",
    'gre_score' => "DECIMAL(5,2) DEFAULT NULL",
    'extracurricular' => "TEXT DEFAULT NULL"
] as $column => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($check && $check->num_rows === 0) {
        @$conn->query("ALTER TABLE users ADD `$column` $definition");
    }
}


/* =========================================================
   MIGRATION FOR OLD STUDENT DOCUMENTS TABLE
   ========================================================= */

$check = $conn->query("SHOW COLUMNS FROM student_documents LIKE 'user_id'");
if ($check && $check->num_rows === 0) {
    @$conn->query("ALTER TABLE student_documents ADD user_id INT NULL AFTER student_id");
}


/* =========================================================
   MIGRATION FOR OLD NOTIFICATIONS TABLE
   ========================================================= */

$check = $conn->query("SHOW COLUMNS FROM notifications LIKE 'application_id'");
if ($check && $check->num_rows === 0) {
    @$conn->query("ALTER TABLE notifications ADD application_id INT NULL AFTER user_id");
}

$check = $conn->query("SHOW COLUMNS FROM notifications LIKE 'type'");
if ($check && $check->num_rows === 0) {
    @$conn->query("ALTER TABLE notifications ADD type VARCHAR(50) DEFAULT 'application' AFTER message");
}


/* =========================================================
   MIGRATION FOR OLD APPLICATION TABLE
   =========================================================
   
   If an older version of the project already has the
   applications table, add the new columns automatically.
   ========================================================= */


/* IELTS */

$col = $conn->query(
    "SHOW COLUMNS
     FROM applications
     LIKE 'ielts_score'"
);

if (
    $col &&
    $col->num_rows === 0
) {

    @$conn->query(
        "ALTER TABLE applications
         ADD ielts_score DECIMAL(3,1)
         DEFAULT NULL
         AFTER student_id"
    );

}


/* GRE */

$col = $conn->query(
    "SHOW COLUMNS
     FROM applications
     LIKE 'gre_score'"
);

if (
    $col &&
    $col->num_rows === 0
) {

    @$conn->query(
        "ALTER TABLE applications
         ADD gre_score DECIMAL(5,2)
         DEFAULT NULL
         AFTER ielts_score"
    );

}


/* Extracurricular */

$col = $conn->query(
    "SHOW COLUMNS
     FROM applications
     LIKE 'extracurricular'"
);

if (
    $col &&
    $col->num_rows === 0
) {

    @$conn->query(
        "ALTER TABLE applications
         ADD extracurricular TEXT
         DEFAULT NULL
         AFTER gre_score"
    );

}


/* Personal statement */

$col = $conn->query(
    "SHOW COLUMNS
     FROM applications
     LIKE 'personal_statement'"
);

if (
    $col &&
    $col->num_rows === 0
) {

    @$conn->query(
        "ALTER TABLE applications
         ADD personal_statement TEXT
         DEFAULT NULL
         AFTER extracurricular"
    );

}


/* Updated_at */

$col = $conn->query(
    "SHOW COLUMNS
     FROM applications
     LIKE 'updated_at'"
);

if (
    $col &&
    $col->num_rows === 0
) {

    @$conn->query(
        "ALTER TABLE applications
         ADD updated_at TIMESTAMP
         DEFAULT CURRENT_TIMESTAMP
         ON UPDATE CURRENT_TIMESTAMP"
    );

}


/* =========================================================
   SEED DEMO DATA
   =========================================================
   
   Only create demo users when users table is empty.
   ========================================================= */

$countResult = $conn->query(
    "SELECT COUNT(*) AS c
     FROM users"
);


$count = (int)(
    $countResult
        ->fetch_assoc()['c']
        ?? 0
);


if ($count === 0) {


    /* =====================================================
       DEMO USERS
       ===================================================== */

    $users = [

        [
            'System Admin',
            'admin@scholarship.com',
            'admin123',
            'admin',
            null,
            null,
            null,
            null,
            null
        ],

        [
            'Demo Student',
            'student@scholarship.com',
            'student123',
            'student',
            'Undergraduate',
            'Computer Science',
            3.65,
            250000,
            'Bangladesh'
        ],

        [
            'Demo Provider',
            'provider@scholarship.com',
            'provider123',
            'provider',
            null,
            null,
            null,
            null,
            'Bangladesh'
        ]

    ];


    /* =====================================================
       INSERT USERS
       ===================================================== */

    $st = $conn->prepare(

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
            country
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"

    );


    foreach ($users as $x) {


        $hash =
            password_hash(
                $x[2],
                PASSWORD_DEFAULT
            );


        $st->bind_param(
            'ssssssdds',
            $x[0],
            $x[1],
            $hash,
            $x[3],
            $x[4],
            $x[5],
            $x[6],
            $x[7],
            $x[8]
        );


        $st->execute();

    }


    /* =====================================================
       GET DEMO PROVIDER ID
       ===================================================== */

    $providerResult = $conn->query(

        "SELECT id
         FROM users
         WHERE email='provider@scholarship.com'
         LIMIT 1"

    );


    $providerId = (int)(
        $providerResult
            ->fetch_assoc()['id']
            ?? 0
    );


    /* =====================================================
       DEMO SCHOLARSHIPS
       ===================================================== */

    $data = [

        [
            'Future Technology Scholarship',

            'Financial support for students studying technology and computing subjects.',

            50000,

            '2030-06-30',

            3.20,

            'Undergraduate',

            'Computer Science',

            'Bangladesh'
        ],


        [
            'Academic Excellence Scholarship',

            'Merit based scholarship for high-performing university students.',

            75000,

            '2030-08-15',

            3.50,

            'Undergraduate',

            'Any',

            'Bangladesh'
        ],


        [
            'Global Graduate Scholarship',

            'Support for graduate students in any academic discipline.',

            100000,

            '2030-10-15',

            3.00,

            'Graduate',

            'Any',

            'Any'
        ],


        [
            'Engineering Innovation Scholarship',

            'Scholarship for students interested in engineering and innovation.',

            60000,

            '2030-09-30',

            3.00,

            'Undergraduate',

            'Engineering',

            'Bangladesh'
        ]

    ];


    /* =====================================================
       INSERT SCHOLARSHIPS
       ===================================================== */

    $st = $conn->prepare(

        "INSERT INTO scholarships
        (
            provider_id,
            title,
            description,
            provider_name,
            amount,
            deadline,
            min_cgpa,
            education_level,
            field_of_study,
            country
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"

    );


    foreach ($data as $x) {


        $providerName =
            'Demo Provider';


        $st->bind_param(
            'isssdsdsss',
            $providerId,
            $x[0],
            $x[1],
            $providerName,
            $x[2],
            $x[3],
            $x[4],
            $x[5],
            $x[6],
            $x[7]
        );


        $st->execute();

    }

}

?>