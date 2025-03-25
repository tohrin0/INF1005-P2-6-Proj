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
$member = new Member($pdo, $user_id, 0);

$member->calculateMiles();
$miles = $member->getMiles();
$level = $member->getLevel();
$transactions = $member->getTransactions();
$progress = min(($miles / 10000) * 100, 100);

$cardClasses = [
    "Normal" => "bg-white border-gray-300 text-gray-700",
    "Bronze" => "bg-yellow-600 border-yellow-700 text-white",
    "Silver" => "bg-gray-400 border-gray-500 text-black",
    "Gold"   => "bg-yellow-400 border-yellow-500 text-black"
];

$progressBarColors = [
    "Normal" => "bg-gray-300",
    "Bronze" => "bg-yellow-600",
    "Silver" => "bg-gray-400",
    "Gold"   => "bg-yellow-400"
];

$cardClass = $cardClasses[$level] ?? "bg-white border-gray-300 text-gray-700";
$progressBarColor = $progressBarColors[$membershipLevel] ?? "bg-gray-300";
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
		<main class="container mx-auto p-4">
			<h1 class="text-2xl font-semibold text-blue-900 mb-4">Good Day, <?php echo $_SESSION['username']; ?>!</h1>

			<div class="max-w-sm mx-auto p-6 border-2 rounded-lg shadow-lg <?php echo $cardClass; ?>">
				<h2 class="text-xl font-bold">Membership Level:</h2>
				<p class="text-lg font-semibold"><?php echo $level; ?></p>
				<p class="text-sm">Miles: <?php echo number_format($member->getMiles()); ?></p>
			</div>

			<!-- Progress Bar Section (Separated from Card) -->
			<div class="max-w-lg mx-auto mt-6 relative">
				<p class="text-lg font-semibold text-gray-700 text-center mb-2">Membership Progress</p>

				<div class="relative w-full bg-gray-200 border border-gray-300 rounded-full h-8 shadow-md">
					<div class="<?php echo $progressBarColor; ?> h-8 rounded-full transition-all" style="width: <?php echo $progress; ?>%;"></div>

					<!-- Labels for thresholds (Precise positioning) -->
					<div class="absolute top-full w-full text-sm font-bold text-gray-700 mt-1">
						<span class="absolute left-0 transform -translate-x-1/2">0</span>
						<span class="absolute" style="left: 10%;">1,000</span>
						<span class="absolute" style="left: 50%;">5,000</span>
						<span class="absolute right-0 transform translate-x-1/2">10,000+</span>
					</div>
				</div>
			</div>

			<div class="bg-white p-6 rounded-lg shadow-md mt-6">
				<h2 class="text-xl font-semibold mb-2">Transaction History</h2>
				<?php if (empty($transactions)) : ?>
					<p>No transactions found.</p>
				<?php else : ?>
					<table class="w-full border-collapse border border-gray-300 mt-2">
						<thead>
							<tr class="bg-gray-200">
								<th class="border border-gray-300 p-2">Airline</th>
								<th class="border border-gray-300 p-2">Flight Number</th>
								<th class="border border-gray-300 p-2">Departure</th>
								<th class="border border-gray-300 p-2">Arrival</th>
								<th class="border border-gray-300 p-2">Miles Earned</th>
								<th class="border border-gray-300 p-2">Booking Date</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($transactions as $transaction) : ?>
								<tr class="border border-gray-300">
									<td class="border border-gray-300 p-2"><?php echo htmlspecialchars($transaction['airline']); ?></td>
									<td class="border border-gray-300 p-2"><?php echo htmlspecialchars($transaction['flight_number']); ?></td>
									<td class="border border-gray-300 p-2"><?php echo htmlspecialchars($transaction['departure']); ?></td>
									<td class="border border-gray-300 p-2"><?php echo htmlspecialchars($transaction['arrival']); ?></td>
									<td class="border border-gray-300 p-2"><?php echo number_format($transaction['miles']); ?></td>
									<td class="border border-gray-300 p-2"><?php echo date("M d, Y", strtotime($transaction['booking_date'])); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</main>
		<?php include 'templates/footer.php'; ?>
	</body>
</html>