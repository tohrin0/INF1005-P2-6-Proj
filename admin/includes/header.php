\includes\header.php
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Flight Booking</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Admin Panel Header Styling */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            position: fixed;
            height: 100%;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 100;
            transition: all 0.3s;
        }

        .sidebar-brand {
            padding: 20px 25px;
            display: flex;
            align-items: center;
            background-color: #2c3136;
            border-bottom: 1px solid #454d55;
        }

        .sidebar-brand img {
            height: 35px;
            margin-right: 10px;
        }

        .sidebar-brand h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-category {
            color: #adb5bd;
            font-size: 12px;
            text-transform: uppercase;
            padding: 15px 25px 10px;
            letter-spacing: 1px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: #e9ecef;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .menu-item:hover,
        .menu-item.active {
            background-color: #2c3136;
            border-left-color: #007bff;
            color: white;
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .content-wrapper {
            flex: 1;
            margin-left: 250px;
            transition: all 0.3s;
        }

        .admin-topbar {
            background-color: white;
            height: 60px;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .topbar-left {
            display: flex;
            align-items: center;
        }

        #sidebar-toggle {
            background: transparent;
            border: none;
            color: #6c757d;
            font-size: 20px;
            cursor: pointer;
            margin-right: 20px;
        }

        .page-title {
            font-weight: 600;
            font-size: 18px;
            color: #343a40;
        }

        .topbar-right {
            display: flex;
            align-items: center;
        }

        .admin-user {
            display: flex;
            align-items: center;
            margin-left: 20px;
            cursor: pointer;
            position: relative;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
        }

        .user-role {
            color: #6c757d;
            font-size: 12px;
        }

        .dropdown {
            background: transparent;
            border: none;
            color: #6c757d;
            cursor: pointer;
            margin-left: 10px;
        }

        .admin-user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 180px;
            display: none;
            z-index: 1000;
        }

        .admin-user:hover .admin-user-dropdown {
            display: block;
        }

        .dropdown-item {
            padding: 10px 15px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #343a40;
            transition: background-color 0.3s;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item i {
            margin-right: 10px;
            color: #6c757d;
        }

        /* View site button */
        .view-site-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-right: 15px;
            transition: background-color 0.3s;
        }

        .view-site-btn:hover {
            background-color: #218838;
            color: white;
            text-decoration: none;
        }

        .view-site-btn i {
            margin-right: 8px;
        }

        /* Mobile responsive adjustments */
        @media (max-width: 992px) {
            .admin-sidebar {
                left: -250px;
            }

            .content-wrapper {
                margin-left: 0;
            }

            .sidebar-open .admin-sidebar {
                left: 0;
            }

            .sidebar-open .content-wrapper {
                margin-left: 250px;
            }
        }

        @media (max-width: 768px) {
            .sidebar-open .content-wrapper {
                margin-left: 0;
                position: relative;
            }

            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 99;
                display: none;
            }

            .sidebar-open .sidebar-overlay {
                display: block;
            }

            .view-site-btn {
                padding: 6px 10px;
                font-size: 12px;
            }

            .view-site-btn span {
                display: none;
            }

            .view-site-btn i {
                margin-right: 0;
            }
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <div class="admin-sidebar">
            <div class="sidebar-brand">
                <img src="../assets/images/logo.png" alt="Logo">
                <h2>Admin Panel</h2>
            </div>

            <div class="sidebar-menu">
                <p class="menu-category">Main</p>
                <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>

                <p class="menu-category">Management</p>
                <a href="bookings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i> Bookings
                </a>
                <a href="users.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="flights.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'flights.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plane"></i> Flights
                </a>
                <a href="subscribers.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'subscribers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-newspaper"></i> Newsletter Subscribers
                </a>
                <a href="login-attempts.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'login-attempts.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i> Login Attempts
                </a>
                <a href="messages.php" class="menu-item">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <p class="menu-category">Configuration</p>
                <a href="settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="cron-jobs.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'cron-jobs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Cron Jobs
                </a>

                <p class="menu-category">Other</p>
                <a href="../index.php" class="menu-item">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a>
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="admin-topbar">
                <div class="topbar-left">
                    <button id="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">
                        <?php
                        $current_page = basename($_SERVER['PHP_SELF']);
                        switch ($current_page) {
                            case 'dashboard.php':
                                echo 'Dashboard';
                                break;
                            case 'bookings.php':
                                echo 'Manage Bookings';
                                break;
                            case 'users.php':
                                echo 'Manage Users';
                                break;
                            case 'flights.php':
                                echo 'Manage Flights';
                                break;
                            case 'settings.php':
                                echo 'Site Settings';
                                break;
                            default:
                                echo 'Admin Panel';
                        }
                        ?>
                    </h1>
                </div>

                <div class="topbar-right">
                    <a href="../index.php" class="view-site-btn">
                        <i class="fas fa-home"></i>
                        <span>View Site</span>
                    </a>
                    <div class="admin-user">
                        <div class="user-avatar">
                            <?php
                            // Get first letter of admin username
                            $firstLetter = isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'A';
                            echo $firstLetter;
                            ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                            <span class="user-role">Administrator</span>
                        </div>
                        <button class="dropdown">
                            <i class="fas fa-chevron-down"></i>
                        </button>

                        <div class="admin-user-dropdown">
                            <a href="../account.php" class="dropdown-item">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                            <a href="settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <a href="../logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <main class="admin-main"></main>