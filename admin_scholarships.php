<?php
require_once 'config.php';
require_role('admin');


/*
|--------------------------------------------------------------------------
| Search and Filter
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$country = trim($_GET['country'] ?? '');


/*
|--------------------------------------------------------------------------
| Build Scholarship Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        s.*,
        u.name AS provider_account,
        u.email AS provider_email
    FROM scholarships s
    JOIN users u
        ON s.provider_id = u.id
    WHERE 1=1
";

$params = [];
$types = '';


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            s.title LIKE ?
            OR s.provider_name LIKE ?
            OR s.description LIKE ?
        )
    ";

    $search_value = "%{$search}%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= 'sss';
}


/*
|--------------------------------------------------------------------------
| Country Filter
|--------------------------------------------------------------------------
*/

if ($country !== '') {

    $sql .= "
        AND s.country = ?
    ";

    $params[] = $country;

    $types .= 's';
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY s.created_at DESC
";


$stmt = $conn->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();

$scholarships = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| Country List
|--------------------------------------------------------------------------
*/

$countries = $conn->query("
    SELECT DISTINCT country
    FROM scholarships
    WHERE country IS NOT NULL
      AND country <> ''
      AND country <> 'Any'
    ORDER BY country ASC
");

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Scholarships</title>

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

            margin-bottom: 25px;

            flex-wrap: wrap;

        }


        .page-subtitle {

            color: #64748B;

            margin-top: 6px;

        }


        /*
        |--------------------------------------------------------------------------
        | Filter Box
        |--------------------------------------------------------------------------
        */

        .filter-box {

            background: #FFFFFF;

            border: 1px solid #E2E8F0;

            border-radius: 16px;

            padding: 18px;

            margin-bottom: 25px;

            box-shadow:
                0 5px 18px
                rgba(15, 23, 42, 0.05);

        }


        .filter-form {

            display: flex;

            gap: 12px;

            align-items: center;

            flex-wrap: wrap;

        }


        .filter-form input,
        .filter-form select {

            padding: 11px 13px;

            border: 1px solid #CBD5E1;

            border-radius: 9px;

            font-size: 14px;

            outline: none;

        }


        .filter-form input {

            min-width: 280px;

        }


        .filter-form input:focus,
        .filter-form select:focus {

            border-color: #2563EB;

        }


        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        .btn {

            border: none;

            border-radius: 9px;

            padding: 11px 16px;

            font-weight: 700;

            cursor: pointer;

            text-decoration: none;

            display: inline-block;

        }


        .btn-primary {

            background: #2563EB;

            color: #FFFFFF;

        }


        .btn-secondary {

            background: #E2E8F0;

            color: #334155;

        }


        .btn-danger {

            background: #DC2626;

            color: #FFFFFF;

        }


        /*
        |--------------------------------------------------------------------------
        | Scholarship Cards
        |--------------------------------------------------------------------------
        */

        .scholarship-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 20px;

        }


        .scholarship-card {

            background: #FFFFFF;

            border: 1px solid #E2E8F0;

            border-radius: 16px;

            padding: 22px;

            box-shadow:
                0 5px 18px
                rgba(15, 23, 42, 0.05);

        }


        .scholarship-title {

            font-size: 19px;

            font-weight: 800;

            color: #0F172A;

            margin-bottom: 7px;

        }


        .provider-name {

            color: #2563EB;

            font-weight: 700;

            font-size: 14px;

            margin-bottom: 14px;

        }


        .description {

            color: #475569;

            line-height: 1.6;

            font-size: 14px;

            margin-bottom: 18px;

        }


        /*
        |--------------------------------------------------------------------------
        | Information Grid
        |--------------------------------------------------------------------------
        */

        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 10px;

            margin-bottom: 18px;

        }


        .info-item {

            background: #F8FAFC;

            border: 1px solid #E2E8F0;

            border-radius: 10px;

            padding: 11px;

        }


        .info-label {

            color: #64748B;

            font-size: 12px;

            margin-bottom: 4px;

        }


        .info-value {

            color: #0F172A;

            font-weight: 700;

            font-size: 14px;

        }


        /*
        |--------------------------------------------------------------------------
        | Eligibility
        |--------------------------------------------------------------------------
        */

        .eligibility {

            border-top: 1px solid #E2E8F0;

            padding-top: 15px;

            margin-top: 5px;

        }


        .eligibility-title {

            font-weight: 800;

            color: #0F172A;

            margin-bottom: 10px;

        }


        .eligibility-item {

            color: #475569;

            font-size: 13px;

            margin-bottom: 5px;

        }


        /*
        |--------------------------------------------------------------------------
        | Provider Information
        |--------------------------------------------------------------------------
        */

        .provider-box {

            margin-top: 15px;

            padding: 12px;

            background: #EFF6FF;

            border-radius: 10px;

            font-size: 13px;

            color: #334155;

        }


        .provider-box strong {

            color: #1D4ED8;

        }


        /*
        |--------------------------------------------------------------------------
        | Card Footer
        |--------------------------------------------------------------------------
        */

        .card-footer {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 10px;

            margin-top: 18px;

            padding-top: 16px;

            border-top: 1px solid #E2E8F0;

        }


        .created-date {

            color: #64748B;

            font-size: 12px;

        }


        /*
        |--------------------------------------------------------------------------
        | Empty State
        |--------------------------------------------------------------------------
        */

        .empty-state {

            background: #FFFFFF;

            border: 1px solid #E2E8F0;

            border-radius: 16px;

            padding: 55px 20px;

            text-align: center;

            color: #64748B;

        }


        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 850px) {

            .scholarship-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 650px) {

            .filter-form {

                flex-direction: column;

                align-items: stretch;

            }


            .filter-form input,
            .filter-form select,
            .filter-form .btn {

                width: 100%;

            }


            .info-grid {

                grid-template-columns: 1fr;

            }


            .card-footer {

                flex-direction: column;

                align-items: stretch;

            }


            .card-footer .btn {

                text-align: center;

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
                Manage Scholarships
            </h1>

            <p class="page-subtitle">
                View and manage all scholarships published on ScholarMatch.
            </p>

        </div>


        

    </div>


    <!-- =========================================================
         SEARCH / FILTER
    ========================================================== -->

    <div class="filter-box">

        <form
            method="GET"
            class="filter-form"
        >


            <input
                type="text"
                name="search"
                placeholder="Search scholarship or provider..."
                value="<?= e($search) ?>"
            >


            <select name="country">

                <option value="">
                    All Countries
                </option>


                <?php if ($countries): ?>

                    <?php while ($c = $countries->fetch_assoc()): ?>

                        <option
                            value="<?= e($c['country']) ?>"
                            <?= $country === $c['country'] ? 'selected' : '' ?>
                        >

                            <?= e($c['country']) ?>

                        </option>

                    <?php endwhile; ?>

                <?php endif; ?>

            </select>


            <button
                type="submit"
                class="btn btn-primary"
            >
                Search
            </button>


            <a
                href="admin_scholarships.php"
                class="btn btn-secondary"
            >
                Reset
            </a>


        </form>

    </div>


    <!-- =========================================================
         SCHOLARSHIPS
    ========================================================== -->

    <?php if ($scholarships->num_rows > 0): ?>


        <div class="scholarship-grid">


            <?php while ($s = $scholarships->fetch_assoc()): ?>


                <div class="scholarship-card">


                    <!-- Title -->

                    <div class="scholarship-title">

                        <?= e($s['title']) ?>

                    </div>


                    <!-- Provider -->

                    <div class="provider-name">

                        🏢 <?= e($s['provider_name']) ?>

                    </div>


                    <!-- Description -->

                    <div class="description">

                        <?= nl2br(
                            e($s['description'])
                        ) ?>

                    </div>


                    <!-- Information -->

                    <div class="info-grid">


                        <div class="info-item">

                            <div class="info-label">
                                Scholarship Amount
                            </div>

                            <div class="info-value">

                                <?= number_format(
                                    (float)$s['amount'],
                                    2
                                ) ?>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Deadline
                            </div>

                            <div class="info-value">

                                <?= e($s['deadline']) ?>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Country
                            </div>

                            <div class="info-value">

                                <?= e($s['country']) ?>

                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Minimum CGPA
                            </div>

                            <div class="info-value">

                                <?= e($s['min_cgpa']) ?>

                            </div>

                        </div>


                    </div>


                    <!-- Eligibility -->

                    <div class="eligibility">


                        <div class="eligibility-title">
                            Eligibility
                        </div>


                        <div class="eligibility-item">

                            🎓 Education:
                            <?= e($s['education_level']) ?>

                        </div>


                        <div class="eligibility-item">

                            📚 Field:
                            <?= e($s['field_of_study']) ?>

                        </div>


                        <div class="eligibility-item">

                            🌍 Country:
                            <?= e($s['country']) ?>

                        </div>


                    </div>


                    <!-- Provider Account -->

                    <div class="provider-box">

                        <strong>
                            Provider Account:
                        </strong>

                        <?= e($s['provider_account']) ?>

                        <br>

                        <strong>
                            Email:
                        </strong>

                        <?= e($s['provider_email']) ?>

                    </div>


                    <!-- Footer -->

                    <div class="card-footer">


                        <div class="created-date">

                            Added:
                            <?= e($s['created_at']) ?>

                        </div>


                        <form
                            method="POST"
                            action="delete_admin_scholarship.php"
                            onsubmit="return confirm(
                                'Are you sure you want to delete this scholarship? All related applications may also be deleted. This action cannot be undone.'
                            );"
                        >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int)$s['id'] ?>"
                            >


                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf()) ?>"
                            >


                            <button
                                type="submit"
                                class="btn btn-danger"
                            >
                                Delete Scholarship
                            </button>

                        </form>


                    </div>


                </div>


            <?php endwhile; ?>


        </div>


    <?php else: ?>


        <div class="empty-state">

            <h3>
                No scholarships found
            </h3>

            <p>
                Try changing your search or country filter.
            </p>

        </div>


    <?php endif; ?>


</div>


</body>

</html>