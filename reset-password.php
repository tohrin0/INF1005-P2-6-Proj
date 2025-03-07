<?php
session_start();
require 'inc/config.php';
require 'inc/db.php';
require 'inc/functions.php';
require 'inc/auth.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $message = "Please enter your email address.";
        $messageType = "error";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        $token = bin2hex(random_bytes(50));
        
        if (emailExists($email)) {
            storeToken($email, $token);
            sendPasswordResetEmail($email, $token);
            $message = "A password reset link has been sent to your email.";
            $messageType = "success";
        } else {
            $message = "Email address not found.";
            $messageType = "error";
        }
    }
}

include 'templates/header.php';
?>

<div class="container">
    <div class="reset-password-form">
        <h2>Reset Your Password</h2>
        <p>Enter the email address associated with your account, and we'll send you a link to reset your password.</p>
        
        <?php if (!empty($message)): ?>
            <div class="<?php echo $messageType === 'error' ? 'error-message' : 'success-message'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="primary-button">Send Reset Link</button>
            </div>
        </form>
        
        <div class="form-footer">
            <p>Remembered your password? <a href="login.php">Back to Login</a></p>
        </div>
    </div>
</div>

<style>
    .reset-password-form {
        max-width: 500px;
        margin: 40px auto;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    
    .reset-password-form h2 {
        margin-top: 0;
        color: #333;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .reset-password-form p {
        color: #666;
        margin-bottom: 20px;
        text-align: center;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #555;
    }
    
    .form-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }
    
    .form-buttons {
        margin-top: 15px;
    }
    
    .primary-button {
        background-color: #4CAF50;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        width: 100%;
        transition: background-color 0.3s;
    }
    
    .primary-button:hover {
        background-color: #45a049;
    }
    
    .error-message {
        color: #f44336;
        background-color: #fee;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 20px;
        border-left: 4px solid #f44336;
    }
    
    .success-message {
        color: #4CAF50;
        background-color: #f1f9f1;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 20px;
        border-left: 4px solid #4CAF50;
    }
    
    .form-footer {
        margin-top: 25px;
        text-align: center;
        color: #666;
    }
    
    .form-footer a {
        color: #4CAF50;
        text-decoration: none;
    }
    
    .form-footer a:hover {
        text-decoration: underline;
    }
</style>

<?php include 'templates/footer.php'; ?>