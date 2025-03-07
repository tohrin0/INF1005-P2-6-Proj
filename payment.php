<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/Payment.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$payment = new Payment();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $paymentMethod = $_POST['payment_method'];

    // Validate payment details
    if ($payment->validatePayment($amount, $paymentMethod)) {
        // Process payment
        $paymentResult = $payment->processPayment($userId, $amount, $paymentMethod);

        if ($paymentResult['success']) {
            // Redirect to confirmation page
            header('Location: confirmation.php');
            exit();
        } else {
            $error = $paymentResult['message'];
        }
    } else {
        $error = 'Invalid payment details.';
    }
}

include 'templates/header.php';
?>

<div class="container">
    <h2>Payment</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="payment.php" method="POST">
        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" name="amount" id="amount" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="payment_method">Payment Method</label>
            <select name="payment_method" id="payment_method" class="form-control" required>
                <option value="credit_card">Credit Card</option>
                <option value="paypal">PayPal</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Pay Now</button>
    </form>
</div>

<?php include 'templates/footer.php'; ?>