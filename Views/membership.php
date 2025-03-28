<?php
require_once 'inc/session.php';
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

include 'templates/header.php';
?>

<main class="container mx-auto p-4">
    <h1 class="text-2xl font-semibold text-blue-900 mb-4 text-center md:text-left">
        Good Day, <?php echo $_SESSION['username']; ?>!
    </h1>

    <!-- Membership Card -->
    <div class="w-full max-w-sm mx-auto p-4 border-2 rounded-lg shadow-lg <?php echo $cardClass; ?>">
        <h2 class="text-lg font-bold">Membership Level:</h2>
        <p class="text-base font-semibold"><?php echo $level; ?></p>
        <p class="text-sm">Miles: <?php echo number_format($member->getMiles()); ?></p>
    </div>

    <!-- Progress Bar -->
    <div class="w-full max-w-lg mx-auto mt-4">
        <p class="text-lg font-semibold text-gray-700 text-center mb-2">Membership Progress</p>

        <div class="relative w-full bg-gray-200 border border-gray-300 rounded-full h-6 shadow-md">
            <div class="<?php echo $progressBarColor; ?> h-6 rounded-full transition-all" style="width: <?php echo $progress; ?>%;"></div>
        </div>

        <div class="flex justify-between text-xs font-bold text-gray-700 mt-1">
            <span>0</span><span>1,000</span><span>5,000</span><span>10,000+</span>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="bg-white p-4 rounded-lg shadow-md mt-6 w-full">
        <h2 class="text-lg font-semibold mb-2 text-center md:text-left">Transaction History</h2>

        <?php if (empty($transactions)) : ?>
            <p class="text-center">No transactions found.</p>
        <?php else : ?>
            <div class="overflow-x-auto">
				<table class="w-full border-collapse border border-gray-300 text-sm">
					<thead>
						<tr class="bg-gray-200 text-xs md:text-base">
							<th class="border border-gray-300 p-2 w-32">Airline</th>
							<th class="border border-gray-300 p-2 w-24">Flight #</th>
							<th class="border border-gray-300 p-2 w-16">Miles</th>
							<th class="border border-gray-300 p-2 w-28">Booking Date</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($transactions as $transaction) : ?>
							<tr class="border border-gray-300 text-center">
								<td class="border border-gray-300 p-2 truncate"><?php echo htmlspecialchars($transaction['airline']); ?></td>
								<td class="border border-gray-300 p-2 truncate"><?php echo htmlspecialchars($transaction['flight_number']); ?></td>
								<td class="border border-gray-300 p-2"><?php echo number_format($transaction['miles']); ?></td>
								<td class="border border-gray-300 p-2 whitespace-nowrap"><?php echo date("M d, Y", strtotime($transaction['booking_date'])); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
        <?php endif; ?>
    </div>
</main>
<?php include 'templates/footer.php'; ?>
</body>
</html>