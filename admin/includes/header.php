
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Flight Booking</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-fix.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                <p class="menu-category">Security</p>
                <a href="login-attempts.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'login-attempts.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-in-alt"></i> Login Attempts
                </a>
                <a href="manage-lockouts.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'manage-lockouts.php' ? 'active' : ''; ?>">
                    <i class="fas fa-lock"></i> Manage Lockouts
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
            
            <!-- Main content area opens here -->
            <main class="admin-main">
            <!-- Page content will go here -->