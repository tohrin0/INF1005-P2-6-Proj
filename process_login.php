<?php
session_start(); // Start the session at the very beginning
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
</head>
<body class="container mt-5">
    <?php
    // Initialize variables
    $email = $pwd = $errorMsg = "";
    $success = true;

    // Email validation
    if (empty($_POST["email"])) {
        $errorMsg .= "Email is required.<br>";
        $success = false;
    } else {
        $email = sanitize_input($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg .= "Invalid email format.<br>";
            $success = false;
        }
    }

    // Password validation
    if (empty($_POST["pwd"])) {
        $errorMsg .= "Password is required.<br>";
        $success = false;
    } else {
        $pwd = $_POST["pwd"];
    }

    if ($success) {
        authenticateUser();
        
        if ($success) {
            // Store user data in session
            $_SESSION['loggedin'] = true;
            $_SESSION['fname'] = $fname;
            $_SESSION['email'] = $email;
            
            echo "<div class='alert alert-success'>";
            echo "<h4>Login successful!</h4>";
            echo "<p>Welcome back, " . htmlspecialchars($fname) . "!</p>";
            echo "<a href='index.php' class='btn btn-primary'>Return to Home</a>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<h4>Login failed:</h4>";
            echo "<p>" . $errorMsg . "</p>";
            echo "<a href='login.php' class='btn btn-primary'>Back to Login</a>";
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<h4>The following input errors were detected:</h4>";
        echo "<p>" . $errorMsg . "</p>";
        echo "<a href='login.php' class='btn btn-primary'>Back to Login</a>";
        echo "</div>";
    }

    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    function authenticateUser() {
        global $email, $pwd, $errorMsg, $success, $fname;
        
        // Create database connection.
        $config = parse_ini_file('/var/www/private/db-config.ini');
        if (!$config) {
            $errorMsg = "Failed to read database config file.";
            $success = false;
            return;
        }
        
        $conn = new mysqli(
            $config['servername'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );
        
        // Check connection
        if ($conn->connect_error) {
            $errorMsg = "Connection failed: " . $conn->connect_error;
            $success = false;
            return;
        }
        
        // Prepare the statement:
        $stmt = $conn->prepare("SELECT * FROM world_of_pets_members WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Check if password matches
            if (password_verify($pwd, $row["password"])) {
                // Save user's name to show in welcome message
                $fname = $row["fname"];
            } else {
                $errorMsg = "Email or password is incorrect.";
                $success = false;
            }
        } else {
            $errorMsg = "Email or password is incorrect.";
            $success = false;
        }
        
        $stmt->close();
        $conn->close();
    }
    ?>
</body>
</html>