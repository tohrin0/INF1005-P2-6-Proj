<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
</head>
<body class="container mt-5">
    <?php
    // Initialize variables
    $fname = $lname = $email = $pwd = $pwd_confirm = $errorMsg = "";
    $success = true;

    // First name validation (optional)
    if (!empty($_POST["fname"])) {
        $fname = sanitize_input($_POST["fname"]);
    }

    // Last name validation
    if (empty($_POST["lname"])) {
        $errorMsg .= "Last name is required.<br>";
        $success = false;
    } else {
        $lname = sanitize_input($_POST["lname"]);
    }

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
    if (empty($_POST["pwd"]) || empty($_POST["pwd_confirm"])) {
        $errorMsg .= "Password and confirmation are required.<br>";
        $success = false;
    } else {
        if ($_POST["pwd"] !== $_POST["pwd_confirm"]) {
            $errorMsg .= "Passwords do not match.<br>";
            $success = false;
        } else {
            $pwd_hashed = password_hash($_POST["pwd"], PASSWORD_DEFAULT);
        }
    }

    // Terms agreement validation
    if (empty($_POST["agree"])) {
        $errorMsg .= "Please agree to terms and conditions.<br>";
        $success = false;
    }

    if ($success) {
        saveMemberToDB(); // Add this line to save to database
        
        if ($success) { // Only show success message if database operation succeeded
            echo "<div class='alert alert-success'>";
            echo "<h4>Registration successful!</h4>";
            echo "<p>Thank you for registering, " . $fname . " " . $lname . "!</p>";
            echo "<p>You may now <a href='login.php' class='alert-link'>sign in</a> with your email address.</p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<h4>Database Error:</h4>";
            echo "<p>" . $errorMsg . "</p>";
            echo "<a href='register.php' class='btn btn-primary'>Back to Registration</a>";
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<h4>The following input errors were detected:</h4>";
        echo "<p>" . $errorMsg . "</p>";
        echo "<a href='register.php' class='btn btn-primary'>Back to Registration</a>";
        echo "</div>";
    }

    /*
    * Helper function that checks input for malicious or unwanted content.
    */
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    /*
    * Helper function to write the member data to the database.
    */
    function saveMemberToDB()
    {
        global $fname, $lname, $email, $pwd_hashed, $errorMsg, $success;
        // Create database connection.
        $config = parse_ini_file('/var/www/private/db-config.ini');
        if (!$config)
        {
            $errorMsg = "Failed to read database config file.";
            $success = false;
        }
        else
        {
            $conn = new mysqli(
                $config['servername'],
                $config['username'],
                $config['password'],
                $config['dbname']
            );
            // Check connection
            if ($conn->connect_error)
            {
                $errorMsg = "Connection failed: " . $conn->connect_error;
                $success = false;
            }
            else
            {
                // Prepare the statement:
                $stmt = $conn->prepare("INSERT INTO world_of_pets_members 
                    (fname, lname, email, password) VALUES (?, ?, ?, ?)");
                // Bind & execute the query statement:
                $stmt->bind_param("ssss", $fname, $lname, $email, $pwd_hashed);
                if (!$stmt->execute())
                {
                    $errorMsg = "Execute failed: (" . $stmt->errno . ") " . 
                        $stmt->error;
                    $success = false;
                }
                $stmt->close();
            }
            $conn->close();
        }
    }
    ?>
</body>
</html>