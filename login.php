<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
</head>
<body>
    <?php include "inc/nav.inc.php"; ?>
    
    <main class="container">
        <h1>Member Login</h1>
        <p>
            For new members, please go to the
            <a href="register.php">Registration page</a>.
        </p>
        <form action="process_login.php" method="post">
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" id="email" name="email" 
                       class="form-control" placeholder="Enter email" 
                       required maxlength="45">
            </div>
            <div class="mb-3">
                <label for="pwd" class="form-label">Password:</label>
                <input type="password" id="pwd" name="pwd" 
                       class="form-control" placeholder="Enter password" 
                       required>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </main>
    <?php include "inc/footer.inc.php"; ?>
</body>
</html>