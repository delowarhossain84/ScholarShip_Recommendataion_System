<?php
require_once 'config.php';
require_role('student');

$u = user();

$uid = (int)$u['id'];

/*
|--------------------------------------------------------------------------
| STUDENT PROFILE
|--------------------------------------------------------------------------
*/

$cgpa = (float)($u['cgpa'] ?? 0);

$edu = trim((string)($u['education_level'] ?? ''));

$field = trim((string)($u['field_of_study'] ?? ''));

$country = trim((string)($u['country'] ?? ''));


/*
|--------------------------------------------------------------------------
| GET ACTIVE SCHOLARSHIPS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM scholarships
    WHERE deadline >= CURDATE()
    ORDER BY deadline ASC
";

$result = $conn->query($sql);

$items = [];


/*
|--------------------------------------------------------------------------
| CALCULATE RECOMMENDATIONS
|--------------------------------------------------------------------------
*/

while ($s = $result->fetch_assoc()) {

    $score = 0;

    $eligible = true;

    $matched = [];

    $failed = [];


    /*
    |--------------------------------------------------------------------------
    | 1. CGPA - 35%
    |--------------------------------------------------------------------------
    */

    $requiredCgpa = (float)$s['min_cgpa'];

    if ($cgpa >= $requiredCgpa) {

        $score += 35;

        $matched[] = "Your CGPA ($cgpa) meets the required CGPA ($requiredCgpa).";

    } else {

        $eligible = false;

        $failed[] = "Your CGPA ($cgpa) is below the required CGPA ($requiredCgpa).";
    }


    /*
    |--------------------------------------------------------------------------
    | 2. EDUCATION LEVEL - 25%
    |--------------------------------------------------------------------------
    */

    $scholarshipEducation = trim((string)$s['education_level']);

    if (
        $scholarshipEducation === '' ||
        strcasecmp($scholarshipEducation, 'Any') === 0 ||
        strcasecmp($scholarshipEducation, $edu) === 0
    ) {

        $score += 25;

        $matched[] = "Your education level matches the scholarship requirement.";

    } else {

        $eligible = false;

        $failed[] = "Education level mismatch. Required: $scholarshipEducation.";
    }


    /*
    |--------------------------------------------------------------------------
    | 3. FIELD OF STUDY - 25%
    |--------------------------------------------------------------------------
    */

    $scholarshipField = trim((string)$s['field_of_study']);

    $fieldMatch = false;

    if (
        $scholarshipField === '' ||
        strcasecmp($scholarshipField, 'Any') === 0
    ) {

        $fieldMatch = true;

    } elseif (
        $field !== '' &&
        (
            stripos($scholarshipField, $field) !== false ||
            stripos($field, $scholarshipField) !== false
        )
    ) {

        $fieldMatch = true;
    }


    if ($fieldMatch) {

        $score += 25;

        $matched[] = "Your field of study matches the scholarship.";

    } else {

        $eligible = false;

        $failed[] = "Field of study mismatch. Required: $scholarshipField.";
    }


    /*
    |--------------------------------------------------------------------------
    | 4. COUNTRY - 15%
    |--------------------------------------------------------------------------
    */

    $scholarshipCountry = trim((string)$s['country']);

    if (
        $scholarshipCountry === '' ||
        strcasecmp($scholarshipCountry, 'Any') === 0 ||
        (
            $country !== '' &&
            strcasecmp($scholarshipCountry, $country) === 0
        )
    ) {

        $score += 15;

        $matched[] = "Your country matches the scholarship eligibility.";

    } else {

        $eligible = false;

        $failed[] = "Country mismatch. Eligible country: $scholarshipCountry.";
    }


    /*
    |--------------------------------------------------------------------------
    | MATCH LEVEL
    |--------------------------------------------------------------------------
    */

    if ($score >= 90) {

        $matchLevel = 'Excellent Match';

    } elseif ($score >= 75) {

        $matchLevel = 'Strong Match';

    } elseif ($score >= 50) {

        $matchLevel = 'Good Match';

    } else {

        $matchLevel = 'Low Match';
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE RESULT
    |--------------------------------------------------------------------------
    */

    $items[] = [

        's' => $s,

        'score' => $score,

        'eligible' => $eligible,

        'matched' => $matched,

        'failed' => $failed,

        'match_level' => $matchLevel
    ];
}


/*
|--------------------------------------------------------------------------
| SORT
|--------------------------------------------------------------------------
*/

usort(
    $items,
    function ($a, $b) {

        if ($a['eligible'] !== $b['eligible']) {

            return $a['eligible'] ? -1 : 1;
        }

        if ($a['score'] !== $b['score']) {

            return $b['score'] <=> $a['score'];
        }

        return strcmp(
            $a['s']['deadline'],
            $b['s']['deadline']
        );
    }
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

    <title>Recommendations | ScholarMatch</title>

    <?php include 'partials/head.php'; ?>


    <style>

        /*
        |--------------------------------------------------------------------------
        | PAGE
        |--------------------------------------------------------------------------
        */

        body {
            background: #f6f8fc;
        }


        .recommendation-page {

            max-width: 1200px;

            margin: auto;

            padding: 35px 20px 60px;
        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .recommendation-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;
        }


        .recommendation-header h1 {

            margin: 8px 0;

            font-size: 32px;

            color: #172033;
        }


        .recommendation-header p {

            margin: 0;

            color: #64748b;
        }


        .smart-badge {

            display: inline-block;

            background: #dbeafe;

            color: #1d4ed8;

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | PROFILE SUMMARY
        |--------------------------------------------------------------------------
        */

        .profile-box {

            background: white;

            border: 1px solid #e5eaf2;

            border-radius: 18px;

            padding: 22px;

            margin-bottom: 30px;

            box-shadow: 0 6px 25px rgba(15, 23, 42, .05);
        }


        .profile-box h3 {

            margin: 0 0 15px;

            color: #172033;
        }


        .profile-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;
        }


        .profile-item {

            background: #f8fafc;

            border-radius: 12px;

            padding: 14px;
        }


        .profile-label {

            display: block;

            font-size: 12px;

            color: #64748b;

            margin-bottom: 5px;
        }


        .profile-value {

            font-weight: 700;

            color: #172033;
        }


        /*
        |--------------------------------------------------------------------------
        | SECTION HEADER
        |--------------------------------------------------------------------------
        */

        .section-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 18px;
        }


        .section-header h2 {

            margin: 0;

            font-size: 23px;

            color: #172033;
        }


        /*
        |--------------------------------------------------------------------------
        | GRID
        |--------------------------------------------------------------------------
        */

        .scholarship-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;
        }


        /*
        |--------------------------------------------------------------------------
        | SCHOLARSHIP CARD
        |--------------------------------------------------------------------------
        */

        .scholarship-card {

            background: white;

            border: 1px solid #e5eaf2;

            border-radius: 20px;

            padding: 22px;

            box-shadow:
                0 6px 25px rgba(15, 23, 42, .05);

            transition: .2s;
        }


        .scholarship-card:hover {

            transform: translateY(-4px);

            box-shadow:
                0 15px 35px rgba(15, 23, 42, .10);
        }


        /*
        |--------------------------------------------------------------------------
        | TOP ROW
        |--------------------------------------------------------------------------
        */

        .card-top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 8px;

            margin-bottom: 15px;
        }


        /*
        |--------------------------------------------------------------------------
        | MATCH SCORE
        |--------------------------------------------------------------------------
        */

        .match-score {

            width: 70px;

            height: 70px;

            border-radius: 50%;

            display: flex;

            flex-direction: column;

            justify-content: center;

            align-items: center;

            background: #ecfdf5;

            border: 5px solid #bbf7d0;

            color: #15803d;

            flex-shrink: 0;
        }


        .match-score strong {

            font-size: 19px;
        }


        .match-score small {

            font-size: 9px;

            font-weight: 600;
        }


        /*
        |--------------------------------------------------------------------------
        | ELIGIBILITY
        |--------------------------------------------------------------------------
        */

        .eligibility {

            display: inline-block;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;
        }


        .eligible {

            background: #dcfce7;

            color: #15803d;
        }


        .not-eligible {

            background: #fef3c7;

            color: #b45309;
        }


        /*
        |--------------------------------------------------------------------------
        | TITLE
        |--------------------------------------------------------------------------
        */

        .scholarship-card h3 {

            margin: 10px 0 7px;

            color: #172033;

            font-size: 19px;

            line-height: 1.4;
        }


        .provider {

            color: #64748b;

            font-size: 13px;

            margin-bottom: 15px;
        }


        /*
        |--------------------------------------------------------------------------
        | DESCRIPTION
        |--------------------------------------------------------------------------
        */

        .description {

            color: #64748b;

            font-size: 14px;

            line-height: 1.6;

            min-height: 65px;
        }


        /*
        |--------------------------------------------------------------------------
        | SCHOLARSHIP INFO
        |--------------------------------------------------------------------------
        */

        .info-list {

            display: grid;

            gap: 9px;

            margin: 18px 0;
        }


        .info-row {

            display: flex;

            justify-content: space-between;

            gap: 10px;

            font-size: 13px;

            color: #64748b;
        }


        .info-row strong {

            color: #172033;

            text-align: right;
        }


        /*
        |--------------------------------------------------------------------------
        | WHY MATCH
        |--------------------------------------------------------------------------
        */

        .why-match {

            border-top: 1px solid #edf0f5;

            padding-top: 16px;

            margin-top: 15px;
        }


        .why-match h4 {

            margin: 0 0 10px;

            font-size: 14px;

            color: #172033;
        }


        .why-match ul {

            margin: 0;

            padding-left: 18px;
        }


        .why-match li {

            color: #15803d;

            font-size: 12px;

            margin-bottom: 7px;

            line-height: 1.4;
        }


        .why-match li.failed {

            color: #dc2626;
        }


        /*
        |--------------------------------------------------------------------------
        | ACTIONS
        |--------------------------------------------------------------------------
        */

        .card-actions {

            display: flex;

            gap: 10px;

            margin-top: 18px;
        }


        .action-btn {

            flex: 1;

            text-align: center;

            text-decoration: none;

            padding: 11px 12px;

            border-radius: 9px;

            font-size: 13px;

            font-weight: 700;

            background: #2563eb;

            color: white;
        }


        .action-btn.secondary {

            background: #eff6ff;

            color: #2563eb;
        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY
        |--------------------------------------------------------------------------
        */

        .empty-box {

            background: white;

            border: 1px solid #e5eaf2;

            border-radius: 18px;

            padding: 50px 20px;

            text-align: center;

            color: #64748b;
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 950px) {

            .scholarship-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }

            .profile-grid {

                grid-template-columns:
                    repeat(2, 1fr);
            }
        }


        @media (max-width: 650px) {

            .recommendation-header {

                flex-direction: column;

                align-items: flex-start;
            }

            .scholarship-grid {

                grid-template-columns: 1fr;
            }

            .profile-grid {

                grid-template-columns: 1fr;
            }
        }

    </style>

</head>


<body>

<?php include 'partials/nav.php'; ?>


<main class="recommendation-page">


    <?php show_flash(); ?>


    <!-- HEADER -->

    <section class="recommendation-header">

        <div>

            <span class="smart-badge">
                ✨ SMART MATCHING
            </span>

            <h1>
                🎯 Recommended Scholarships
            </h1>

            <p>
                Scholarships ranked according to your academic profile.
            </p>

        </div>


    

    </section>


    <!-- PROFILE -->

    <section class="profile-box">

        <h3>
            👤 Your Matching Profile
        </h3>


        <div class="profile-grid">


            <div class="profile-item">

                <span class="profile-label">
                    CGPA
                </span>

                <span class="profile-value">
                    <?= e($cgpa ?: 'Not set') ?>
                </span>

            </div>


            <div class="profile-item">

                <span class="profile-label">
                    Education
                </span>

                <span class="profile-value">
                    <?= e($edu ?: 'Not set') ?>
                </span>

            </div>


            <div class="profile-item">

                <span class="profile-label">
                    Field of Study
                </span>

                <span class="profile-value">
                    <?= e($field ?: 'Not set') ?>
                </span>

            </div>


            <div class="profile-item">

                <span class="profile-label">
                    Country
                </span>

                <span class="profile-value">
                    <?= e($country ?: 'Not set') ?>
                </span>

            </div>


        </div>

    </section>


    <!-- SCHOLARSHIPS -->

    <section>

        <div class="section-header">

            <h2>
                Best Matches For You
            </h2>

            <span class="small">
                <?= count($items) ?> scholarships found
            </span>

        </div>


        <?php if (count($items) > 0): ?>


            <div class="scholarship-grid">


                <?php foreach ($items as $it): ?>

                    <?php

                    $s = $it['s'];

                    $score = (int)$it['score'];

                    ?>


                    <article class="scholarship-card">


                        <!-- CARD TOP -->

                        <div class="card-top">


                            <div>

                                <?php if ($it['eligible']): ?>

                                    <span class="eligibility eligible">
                                        ✓ Eligible
                                    </span>

                                <?php else: ?>

                                    <span class="eligibility not-eligible">
                                        ⚠ Not Eligible Yet
                                    </span>

                                <?php endif; ?>


                                <h3>
                                    <?= e($s['title']) ?>
                                </h3>

                                <div class="provider">
                                    🏢 <?= e($s['provider_name']) ?>
                                </div>

                            </div>


                            <div class="match-score">

                                <strong>
                                    <?= $score ?>%
                                </strong>

                                <small>
                                    MATCH
                                </small>

                            </div>


                        </div>


                        <!-- MATCH LEVEL -->

                        <div
                            style="
                                font-size:13px;
                                font-weight:700;
                                color:#2563eb;
                                margin-bottom:10px;
                            "
                        >

                            ⭐ <?= e($it['match_level']) ?>

                        </div>


                        <!-- DESCRIPTION -->

                        <p class="description">

                            <?= e($s['description']) ?>

                        </p>


                        <!-- INFORMATION -->

                        <div class="info-list">


                            <div class="info-row">

                                <span>💰 Amount</span>

                                <strong>
                                    ৳<?= number_format((float)$s['amount']) ?>
                                </strong>

                            </div>


                            <div class="info-row">

                                <span>📅 Deadline</span>

                                <strong>
                                    <?= e($s['deadline']) ?>
                                </strong>

                            </div>


                            <div class="info-row">

                                <span>🎓 Education</span>

                                <strong>
                                    <?= e($s['education_level']) ?>
                                </strong>

                            </div>


                            <div class="info-row">

                                <span>📚 Field</span>

                                <strong>
                                    <?= e($s['field_of_study']) ?>
                                </strong>

                            </div>


                            <div class="info-row">

                                <span>🌍 Country</span>

                                <strong>
                                    <?= e($s['country']) ?>
                                </strong>

                            </div>


                        </div>


                        <!-- WHY MATCH -->

                        <div class="why-match">


                            <?php if (count($it['matched']) > 0): ?>

                                <h4>
                                    ✓ Why this matches you
                                </h4>

                                <ul>

                                    <?php foreach ($it['matched'] as $reason): ?>

                                        <li>
                                            <?= e($reason) ?>
                                        </li>

                                    <?php endforeach; ?>

                                </ul>

                            <?php endif; ?>


                            <?php if (count($it['failed']) > 0): ?>

                                <h4
                                    style="
                                        margin-top:15px;
                                    "
                                >
                                    ⚠ What needs improvement
                                </h4>

                                <ul>

                                    <?php foreach ($it['failed'] as $reason): ?>

                                        <li class="failed">
                                            <?= e($reason) ?>
                                        </li>

                                    <?php endforeach; ?>

                                </ul>

                            <?php endif; ?>


                        </div>


                        <!-- ACTION BUTTONS -->

                        <div class="card-actions">


                            <a
                                href="apply.php?id=<?= (int)$s['id'] ?>"
                                class="action-btn"
                            >

                                <?php if ($it['eligible']): ?>

                                    View & Apply

                                <?php else: ?>

                                    Check Eligibility

                                <?php endif; ?>

                            </a>


                            <a
                                href="scholarships.php"
                                class="action-btn secondary"
                            >
                                Details
                            </a>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <div class="empty-box">

                <h3>
                    🔍 No Active Scholarships
                </h3>

                <p>
                    There are currently no active scholarship opportunities.
                </p>

                <a
                    href="scholarships.php"
                    class="action-btn"
                    style="display:inline-block;max-width:200px;"
                >
                    Browse Scholarships
                </a>

            </div>


        <?php endif; ?>


    </section>


</main>


</body>

</html>