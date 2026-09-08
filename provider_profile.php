<?php
require_once 'config.php';

require_role('provider');

$u = user();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '') {
        $error = 'Name is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } else {

        $stmt = $conn->prepare("
            UPDATE users
            SET name = ?, email = ?
            WHERE id = ? AND role = 'provider'
        ");

        $id = (int)$u['id'];

        $stmt->bind_param(
            "ssi",
            $name,
            $email,
            $id
        );

        if ($stmt->execute()) {
            $success = 'Profile updated successfully.';

            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;

            $u = user();
        } else {
            $error = 'Unable to update profile: ' . $stmt->error;
        }

        $stmt->close();
    }
}

include 'partials/head.php';
include 'partials/nav.php';
?>

<style>
.profile-wrap {
    max-width: 700px;
    margin: 40px auto;
    padding: 0 20px;
}

.profile-card {
    background: #fff;
    border-radius: 18px;
    padding: 32px;
    box-shadow: 0 8px 30px rgba(0,0,0,.08);
}

.profile-title {
    text-align: center;
    margin-bottom: 30px;
    color: #111827;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #1f2937;
}

.form-control {
    width: 100%;
    padding: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    box-sizing: border-box;
    font-size: 15px;
}

.btn {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 10px;
    background: #4f46e5;
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
}

.btn:hover {
    background: #4338ca;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 20px;
}
</style>

<div class="profile-wrap">

    <div class="profile-card">

        <h1 class="profile-title">Provider Profile</h1>

        <?php if ($success): ?>
            <div class="alert-success">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf"
                value="<?= csrf() ?>"
            >

            <div class="form-group">
                <label for="name">Provider Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control"
                    value="<?= e($u['name'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    value="<?= e($u['email'] ?? '') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label>Role</label>

                <input
                    type="text"
                    class="form-control"
                    value="Scholarship Provider"
                    readonly
                >
            </div>

            <button
                type="submit"
                class="btn"
            >
                Update Profile
            </button>

        </form>

    </div>

</div>

<?php include 'partials/foot.php'; ?>