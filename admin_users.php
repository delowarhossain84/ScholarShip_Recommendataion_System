<?php

require_once 'config.php';

require_role('admin');


// ============================================================
// SEARCH & FILTER
// ============================================================

$search = trim(
    $_GET['search'] ?? ''
);

$role = trim(
    $_GET['role'] ?? ''
);


// ============================================================
// BUILD QUERY
// ============================================================

$sql = "
    SELECT
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
    WHERE 1 = 1
";

$params = [];

$types = "";


// ============================================================
// SEARCH
// ============================================================

if ($search !== '') {

    $sql .= "
        AND (
            name LIKE ?
            OR email LIKE ?
        )
    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;

    $params[] = $search_value;

    $types .= "ss";
}


// ============================================================
// ROLE FILTER
// ============================================================

if (
    $role !== '' &&
    in_array(
        $role,
        ['student', 'provider', 'admin'],
        true
    )
) {

    $sql .= "
        AND role = ?
    ";

    $params[] = $role;

    $types .= "s";
}


// ============================================================
// ORDER
// ============================================================

$sql .= "
    ORDER BY created_at DESC
";


// ============================================================
// EXECUTE QUERY
// ============================================================

$stmt = $conn->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


$stmt->execute();

$users = $stmt->get_result();

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
        Manage Users | ScholarMatch
    </title>

    <?php include 'partials/head.php'; ?>


    <style>

        /* =====================================================
           PAGE
           ===================================================== */

        .admin-users-page {

            max-width: 1200px;

            margin: 0 auto;

            padding: 30px 20px 60px;

        }


        /* =====================================================
           HEADER
           ===================================================== */

        .page-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;

            flex-wrap: wrap;

        }


        .page-header h1 {

            margin: 0;

            color: #0f172a;

            font-size: 30px;

        }


        .page-header p {

            margin: 7px 0 0;

            color: #64748b;

        }


        /* =====================================================
           FILTER CARD
           ===================================================== */

        .filter-card {

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 16px;

            padding: 20px;

            margin-bottom: 25px;

            box-shadow:
                0 5px 18px
                rgba(15, 23, 42, 0.05);

        }


        .filter-form {

            display: grid;

            grid-template-columns:
                1fr 220px auto auto;

            gap: 12px;

            align-items: end;

        }


        .form-group label {

            display: block;

            font-size: 13px;

            font-weight: 700;

            color: #475569;

            margin-bottom: 7px;

        }


        .form-control {

            width: 100%;

            box-sizing: border-box;

            padding: 11px 12px;

            border: 1px solid #cbd5e1;

            border-radius: 9px;

            background: #ffffff;

            color: #0f172a;

            font-size: 14px;

            outline: none;

        }


        .form-control:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.10);

        }


        /* =====================================================
           BUTTONS
           ===================================================== */

        .btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 11px 16px;

            border: none;

            border-radius: 9px;

            font-size: 14px;

            font-weight: 700;

            text-decoration: none;

            cursor: pointer;

            transition: 0.2s;

        }


        .btn-primary {

            background: #2563eb;

            color: #ffffff;

        }


        .btn-primary:hover {

            background: #1d4ed8;

        }


        .btn-secondary {

            background: #e2e8f0;

            color: #334155;

        }


        .btn-secondary:hover {

            background: #cbd5e1;

        }


        .btn-danger {

            background: #fee2e2;

            color: #b91c1c;

        }


        .btn-danger:hover {

            background: #fecaca;

        }


        /* =====================================================
           TABLE CARD
           ===================================================== */

        .table-card {

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 5px 18px
                rgba(15, 23, 42, 0.05);

        }


        .table-wrap {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 950px;

        }


        th {

            background: #f8fafc;

            color: #475569;

            font-size: 13px;

            font-weight: 800;

            padding: 14px;

            text-align: left;

            border-bottom: 1px solid #e2e8f0;

        }


        td {

            padding: 14px;

            border-bottom: 1px solid #e2e8f0;

            color: #334155;

            font-size: 14px;

            vertical-align: middle;

        }


        tr:last-child td {

            border-bottom: none;

        }


        /* =====================================================
           USER INFO
           ===================================================== */

        .user-name {

            font-weight: 800;

            color: #0f172a;

        }


        .user-email {

            color: #64748b;

            font-size: 13px;

            margin-top: 3px;

        }


        /* =====================================================
           ROLE BADGES
           ===================================================== */

        .badge {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 800;

        }


        .role-student {

            background: #dcfce7;

            color: #15803d;

        }


        .role-provider {

            background: #dbeafe;

            color: #1d4ed8;

        }


        .role-admin {

            background: #ede9fe;

            color: #6d28d9;

        }


        /* =====================================================
           ACTIONS
           ===================================================== */

        .action-form {

            display: inline;

            margin: 0;

        }


        .delete-btn {

            border: none;

            background: #fee2e2;

            color: #b91c1c;

            padding: 8px 11px;

            border-radius: 8px;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;

        }


        .delete-btn:hover {

            background: #fecaca;

        }


        .protected-text {

            color: #94a3b8;

            font-size: 12px;

        }


        /* =====================================================
           EMPTY
           ===================================================== */

        .empty-state {

            text-align: center;

            padding: 50px 20px;

            color: #64748b;

        }


        .empty-state h2 {

            margin: 0 0 8px;

            color: #0f172a;

        }


        /* =====================================================
           MOBILE
           ===================================================== */

        @media (max-width: 800px) {

            .filter-form {

                grid-template-columns: 1fr;

            }


            .filter-form .btn {

                width: 100%;

            }


            .page-header {

                align-items: flex-start;

            }

        }

    </style>

</head>


<body>


<?php include 'partials/nav.php'; ?>


<main class="admin-users-page">


    <!-- =====================================================
         HEADER
         ===================================================== -->

    <div class="page-header">

        <div>

            <h1>
                👥 Manage Users
            </h1>

            <p>
                View, search and manage ScholarMatch users.
            </p>

        </div>


       

    </div>


    <!-- =====================================================
         FLASH MESSAGE
         ===================================================== -->

    <?php show_flash(); ?>


    <!-- =====================================================
         FILTER
         ===================================================== -->

    <div class="filter-card">

        <form
            method="GET"
            class="filter-form"
        >


            <div class="form-group">

                <label for="search">
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    value="<?= e($search) ?>"
                    placeholder="Search by name or email"
                >

            </div>


            <div class="form-group">

                <label for="role">
                    Role
                </label>

                <select
                    id="role"
                    name="role"
                    class="form-control"
                >

                    <option value="">
                        All Roles
                    </option>

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
                        Provider
                    </option>

                    <option
                        value="admin"
                        <?= $role === 'admin'
                            ? 'selected'
                            : '' ?>
                    >
                        Admin
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >
                🔎 Search
            </button>


            <a
                href="admin_users.php"
                class="btn btn-secondary"
            >
                Reset
            </a>


        </form>

    </div>


    <!-- =====================================================
         USERS TABLE
         ===================================================== -->

    <div class="table-card">

        <div class="table-wrap">


            <?php if (
                $users &&
                $users->num_rows > 0
            ): ?>


                <table>

                    <thead>

                        <tr>

                            <th>
                                User
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

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $row = $users->fetch_assoc()
                    ): ?>


                        <?php

                        if (
                            $row['role']
                            === 'student'
                        ) {

                            $role_class =
                                'role-student';

                        } elseif (
                            $row['role']
                            === 'provider'
                        ) {

                            $role_class =
                                'role-provider';

                        } else {

                            $role_class =
                                'role-admin';

                        }

                        ?>


                        <tr>


                            <!-- USER -->

                            <td>

                                <div class="user-name">

                                    <?= e(
                                        $row['name']
                                    ) ?>

                                </div>

                                <div class="user-email">

                                    <?= e(
                                        $row['email']
                                    ) ?>

                                </div>

                            </td>


                            <!-- ROLE -->

                            <td>

                                <span
                                    class="
                                        badge
                                        <?= $role_class ?>
                                    "
                                >

                                    <?= e(
                                        ucfirst(
                                            $row['role']
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <!-- EDUCATION -->

                            <td>

                                <?= e(
                                    $row['education_level']
                                    ?: 'Not provided'
                                ) ?>

                            </td>


                            <!-- FIELD -->

                            <td>

                                <?= e(
                                    $row['field_of_study']
                                    ?: 'Not provided'
                                ) ?>

                            </td>


                            <!-- CGPA -->

                            <td>

                                <?php if (
                                    $row['cgpa']
                                    !== null
                                ): ?>

                                    <?= e(
                                        $row['cgpa']
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <!-- COUNTRY -->

                            <td>

                                <?= e(
                                    $row['country']
                                    ?: 'Not provided'
                                ) ?>

                            </td>


                            <!-- REGISTERED -->

                            <td>

                                <?= e(
                                    $row['created_at']
                                ) ?>

                            </td>


                            <!-- ACTION -->

                            <td>


                                <?php if (
                                    (int)$row['id']
                                    ===
                                    (int)user()['id']
                                ): ?>


                                    <span
                                        class="protected-text"
                                    >
                                        Current Admin
                                    </span>


                                <?php elseif (
                                    $row['role']
                                    === 'admin'
                                ): ?>


                                    <span
                                        class="protected-text"
                                    >
                                        Protected
                                    </span>


                                <?php else: ?>


                                    <form
                                        method="POST"
                                        action="delete_user.php"
                                        class="action-form"
                                        onsubmit="
                                            return confirm(
                                                'Are you sure you want to delete this user? This action cannot be undone.'
                                            );
                                        "
                                    >

                                        <!-- CSRF TOKEN -->

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?= csrf() ?>"
                                        >


                                        <!-- USER ID -->

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int)$row['id'] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="delete-btn"
                                        >
                                            🗑 Delete
                                        </button>

                                    </form>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>


            <?php else: ?>


                <div class="empty-state">

                    <h2>
                        No users found
                    </h2>

                    <p>
                        Try changing your search or role filter.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


</main>


</body>

</html>