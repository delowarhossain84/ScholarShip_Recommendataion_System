<?php
require_once 'config.php';

require_role('provider');

$u = user();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    check_csrf();

    // Get form values
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');
    $min_cgpa = trim($_POST['min_cgpa'] ?? '');
    $education_level = trim($_POST['education_level'] ?? 'Any');
    $field_of_study = trim($_POST['field_of_study'] ?? 'Any');
    $country = trim($_POST['country'] ?? 'Any');

    // Validation
    if ($title === '') {
        $error = 'Scholarship title is required.';
    } elseif ($description === '') {
        $error = 'Description is required.';
    } elseif ($amount === '' || !is_numeric($amount) || $amount < 0) {
        $error = 'Please enter a valid scholarship amount.';
    } elseif ($deadline === '') {
        $error = 'Deadline is required.';
    } elseif (
        $min_cgpa === '' ||
        !is_numeric($min_cgpa) ||
        $min_cgpa < 0 ||
        $min_cgpa > 4
    ) {
        $error = 'Minimum CGPA must be between 0 and 4.';
    } else {

        $provider_id = (int)$u['id'];
        $provider_name = $u['name'];
        $amount_value = (float)$amount;
        $cgpa_value = (float)$min_cgpa;

        // Prepare query
        $stmt = $conn->prepare("
            INSERT INTO scholarships
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
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {

            $error = 'Prepare Error: ' . $conn->error;

        } else {

            $stmt->bind_param(
                "isssdsdsss",
                $provider_id,
                $title,
                $description,
                $provider_name,
                $amount_value,
                $deadline,
                $cgpa_value,
                $education_level,
                $field_of_study,
                $country
            );

            if (!$stmt->execute()) {

                $error = 'Database Error: ' . $stmt->error;

            } else {

                flash(
                    'success',
                    'Scholarship published successfully.'
                );

                header(
                    'Location: provider_scholarships.php'
                );

                exit;
            }

            $stmt->close();
        }
    }
}

include 'partials/head.php';
include 'partials/nav.php';
?>

<style>

.page-wrap {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}

.form-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 32px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}

.form-title {
    text-align: center;
    margin-bottom: 30px;
    color: #111827;
    font-size: 28px;
    font-weight: 700;
}

.form-group {
    margin-bottom: 22px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #1f2937;
}

.form-control {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 15px;
    box-sizing: border-box;
    outline: none;
    background: #ffffff;
    transition: 0.2s;
}

.form-control:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

textarea.form-control {
    min-height: 140px;
    resize: vertical;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 28px;
}

.btn {
    display: inline-block;
    padding: 12px 22px;
    border-radius: 10px;
    border: none;
    text-decoration: none;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.btn-primary {
    background: #4f46e5;
    color: #ffffff;
}

.btn-primary:hover {
    background: #4338ca;
}

.btn-secondary {
    background: #e5e7eb;
    color: #111827;
}

.btn-secondary:hover {
    background: #d1d5db;
}

.error-box {
    background: #fee2e2;
    color: #991b1b;
    padding: 13px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid #fecaca;
}

.required {
    color: #dc2626;
}

@media (max-width: 700px) {

    .page-wrap {
        margin: 20px auto;
        padding: 0 12px;
    }

    .form-card {
        padding: 22px;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        text-align: center;
        box-sizing: border-box;
    }
}

</style>

<div class="page-wrap">

    <div class="form-card">

        <h1 class="form-title">
            Publish Scholarship
        </h1>

        <?php if ($error): ?>

            <div class="error-box">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <!-- CSRF -->
            <input
                type="hidden"
                name="csrf"
                value="<?= csrf() ?>"
            >

            <!-- Scholarship Title -->
            <div class="form-group">

                <label for="title">
                    Scholarship Title
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    class="form-control"
                    value="<?= e($_POST['title'] ?? '') ?>"
                    placeholder="Enter scholarship title"
                    required
                >

            </div>

            <!-- Description -->
            <div class="form-group">

                <label for="description">
                    Description
                    <span class="required">*</span>
                </label>

                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    placeholder="Enter scholarship description"
                    required
                ><?= e($_POST['description'] ?? '') ?></textarea>

            </div>

            <!-- Amount + Deadline -->
            <div class="form-row">

                <div class="form-group">

                    <label for="amount">
                        Amount
                        <span class="required">*</span>
                    </label>

                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        class="form-control"
                        value="<?= e($_POST['amount'] ?? '') ?>"
                        placeholder="e.g. 5000"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="deadline">
                        Deadline
                        <span class="required">*</span>
                    </label>

                    <input
                        type="date"
                        id="deadline"
                        name="deadline"
                        class="form-control"
                        value="<?= e($_POST['deadline'] ?? '') ?>"
                        required
                    >

                </div>

            </div>

            <!-- CGPA + Education -->
            <div class="form-row">

                <div class="form-group">

                    <label for="min_cgpa">
                        Minimum CGPA
                        <span class="required">*</span>
                    </label>

                    <input
                        type="number"
                        id="min_cgpa"
                        name="min_cgpa"
                        class="form-control"
                        value="<?= e($_POST['min_cgpa'] ?? '0') ?>"
                        placeholder="e.g. 3.00"
                        min="0"
                        max="4"
                        step="0.01"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="education_level">
                        Education Level
                    </label>

                    <select
                        id="education_level"
                        name="education_level"
                        class="form-control"
                    >

                        <?php

                        $education_options = [
                            'Any',
                            'Undergraduate',
                            'Graduate',
                            'Postgraduate',
                            'Diploma'
                        ];

                        $selected_education =
                            $_POST['education_level'] ?? 'Any';

                        foreach (
                            $education_options as $option
                        ):
                        ?>

                            <option
                                value="<?= e($option) ?>"
                                <?= $selected_education === $option
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($option) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

            <!-- Field + Country -->
            <div class="form-row">

                <div class="form-group">

                    <label for="field_of_study">
                        Field of Study
                    </label>

                    <select
                        id="field_of_study"
                        name="field_of_study"
                        class="form-control"
                    >

                        <?php

                        $field_options = [
                            'Any',
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

                        $selected_field =
                            $_POST['field_of_study'] ?? 'Any';

                        foreach (
                            $field_options as $option
                        ):
                        ?>

                            <option
                                value="<?= e($option) ?>"
                                <?= $selected_field === $option
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($option) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label for="country">
                        Country
                    </label>

                    <select
                        id="country"
                        name="country"
                        class="form-control"
                    >

                        <?php

                        $country_options = [
                            'Any',
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

                        $selected_country =
                            $_POST['country'] ?? 'Any';

                        foreach (
                            $country_options as $option
                        ):
                        ?>

                            <option
                                value="<?= e($option) ?>"
                                <?= $selected_country === $option
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($option) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>

            <!-- Buttons -->
            <div class="form-actions">

                <a
                    href="provider_scholarships.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Publish Scholarship
                </button>

            </div>

        </form>

    </div>

</div>

<?php include 'partials/foot.php'; ?>