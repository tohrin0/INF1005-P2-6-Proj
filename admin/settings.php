<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if the user is logged in and has admin privileges
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Handle form submission for updating settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = $_POST['site_name'] ?? '';
    $siteEmail = $_POST['site_email'] ?? '';
    $sitePhone = $_POST['site_phone'] ?? '';

    // Validate inputs
    if (empty($siteName) || empty($siteEmail) || empty($sitePhone)) {
        $error = "All fields are required.";
    } else {
        // Update settings in the database
        $stmt = $pdo->prepare("UPDATE settings SET site_name = ?, site_email = ?, site_phone = ?");
        if ($stmt->execute([$siteName, $siteEmail, $sitePhone])) {
            $success = "Settings updated successfully.";
        } else {
            $error = "Failed to update settings.";
        }
    }
}

// Fetch current settings from the database
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$currentSettings = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Site Settings</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form action="" method="POST">
            <label for="site_name">Site Name:</label>
            <input type="text" name="site_name" id="site_name" value="<?= htmlspecialchars($currentSettings['site_name']) ?>" required>

            <label for="site_email">Site Email:</label>
            <input type="email" name="site_email" id="site_email" value="<?= htmlspecialchars($currentSettings['site_email']) ?>" required>

            <label for="site_phone">Site Phone:</label>
            <input type="text" name="site_phone" id="site_phone" value="<?= htmlspecialchars($currentSettings['site_phone']) ?>" required>

            <button type="submit">Update Settings</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>