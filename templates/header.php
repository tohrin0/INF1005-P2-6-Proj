<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Booking Website</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <style>
        /* Fix navigation styling */
        nav ul {
            list-style: none;
            display: flex;
            padding: 0;
            margin: 0;
        }

        nav li {
            margin: 0 10px;
        }

        nav a {
            color: #333;
            /* Changed from white to dark color */
            text-decoration: none;
            padding: 10px 15px;
            display: inline-block;
        }

        nav a:hover {
            background: #e9ecef;
            /* Changed from dark blue to light gray */
            color: #007bff;
            border-radius: 4px;
        }

        /* Repositioned welcome message */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            cursor: pointer;
            text-decoration: none;
        }

        .logo:hover {
            color: #0056b3;
        }

        .welcome-message {
            color: #4CAF50;
            font-weight: bold;
            padding: 10px 15px;
            background-color: #f1f9f1;
            border-radius: 4px;
            margin-left: auto;
            margin-right: 20px;
        }

        /* Admin link styling */
        .admin-link {
            background-color: #dc3545;
            color: white !important;
            border-radius: 4px;
        }

        .admin-link:hover {
            background-color: #c82333;
            color: white !important;
        }
    </style>
</head>

<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">
                Flight Booking
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</div>
            <?php endif; ?>

            <nav>
                <ul>
                    <!-- Removed "Home" menu item since logo now links to home -->
                    <li><a href="index.php">Home</a></li>
                    <li><a href="globe.php">World Map</a></li>
                    <li><a href="search.php">Search Flights</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="my-bookings.php">Bookings</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a href="admin/dashboard.php" class="admin-link">Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="account.php">My Account</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>