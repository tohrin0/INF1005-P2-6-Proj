<?php
session_start();
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../classes/Flight.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/ApiClient.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Member.php';


if (!isLoggedIn()) {
	$_SESSION['redirect after login'] = 'membership.php';
	header('Location: login.php');
	exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$member = new Member($pdo, 0, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Privileges & Miles</title>
		<link rel="stylesheet" href="assets/css/main.css">
		<link rel="stylesheet" href="assets/css/responsive.css">
		<link rel="stylesheet" href="assets/css/tailwind.css">
		<!-- Font Awesome for icons -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
	</head>
	<body>
		<?php include 'templates/header.php'; ?>
		<p>hello hi this is my membership point: 
			<?php 
				$member->calculateMiles();
				echo $member->getMiles();
			?>
		</p>
		<?php include 'templates/footer.php'; ?>
	</body>
</html>