<?php
// Security headers
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data:;");
?>
<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sky International Travels</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/accessibility.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            color: #333;
            /* Changed from white to dark color */
            text-decoration: none;
            padding: 10px 15px;
            display: inline-block;
        }


        nav a:hover {
            background: #e9ecef;
            /* Changed from dark blue to light gray */
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
            color: #2e7d32;
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

        /* Compact footer styling */
        .site-footer {
            background-color: #1a2b49;
            color: #fff;
            padding: 30px 0 15px;
            margin-top: 30px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
        }
        
        .footer-content {
            max-width: 900px !important; /* Reduced from default container width */
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .footer-main {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 20px;
        }
        
        .footer-brand {
            flex: 0 0 220px;
        }
        
        .footer-brand h3 {
            font-size: 20px;
            margin: 0 0 8px;
            font-weight: 600;
        }
        
        .footer-brand .tagline {
            color: #b3c0d1;
            margin: 0;
            font-size: 13px;
            margin-bottom: 12px;
        }
        
        /* Clock styling */
        .footer-clock {
            margin-top: 15px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            display: inline-block;
        }
        
        .clock {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            font-family: "Courier New", monospace;
        }
        
        .timezone {
            font-size: 11px;
            color: #b3c0d1;
            margin-top: 2px;
        }
        
        .footer-nav {
            flex: 0 0 220px;
        }
        
        .footer-contact {
            flex: 0 0 220px;
        }
        
        .footer-contact p {
            margin: 5px 0;
            color: #b3c0d1;
            font-size: 13px;
        }
        
        .footer-contact i {
            width: 15px;
            margin-right: 5px;
            color: #4CAF50;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 5px;
        }
        
        .footer-links a {
            color: #b3c0d1;
            text-decoration: none;
            transition: color 0.3s;
            font-size: 13px;
        }
        
        .footer-links a:hover {
            color: #4CAF50;
        }
        
        h4 {
            font-size: 16px;
            margin-top: 0;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .footer-bottom {
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-bottom p {
            margin: 0;
            font-size: 12px;
            color: #b3c0d1;
        }
        
        .social-links {
            display: flex;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            margin-left: 8px;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        
        .social-links a:hover {
            background-color: #4CAF50;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .footer-main {
                justify-content: flex-start;
            }
            
            .footer-brand, .footer-nav, .footer-contact {
                flex: 0 0 100%;
                margin-bottom: 15px;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
            
            .social-links {
                margin-top: 10px;
                justify-content: center;
            }
            
            .social-links a {
                margin: 0 4px;
            }
        }
        </style>
</head>


<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">
                Sky International Travels
            </a>


            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</div>
            <?php endif; ?>

           

            <nav>
                <ul>
                    
                    <li><a href="globe.php">World Map</a></li>
                    <li><a href="search2.php">Search Flights</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="my-bookings.php">Bookings</a></li>
                    <?php if (isLoggedIn()) : ?>
                        <li><a href="membership.php">Privileges & Miles</a></li>
                    <?php endif; ?>
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
