<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Define variables and initialize with empty values
$new_email = $confirm_email = "";
$new_email_err = $confirm_email_err = "";

// Generate and store CSRF token in session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    // Validate new email
    if (empty(trim($_POST["new_email"]))) {
        $new_email_err = "Please enter a new email address.";
    } else {
        $new_email = trim($_POST["new_email"]);
    }

    // Validate confirm email
    if (empty(trim($_POST["confirm_email"]))) {
        $confirm_email_err = "Please confirm email address.";
    } else {
        $confirm_email = trim($_POST["confirm_email"]);
        if ($new_email != $confirm_email) {
            $confirm_email_err = "Email addresses do not match.";
        }
    }

    // Check input errors before updating email in database
    if (empty($new_email_err) && empty($confirm_email_err)) {

        // Prepare an update statement
        $sql = "UPDATE users SET email = ? WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "si", $param_new_email, $param_id);

            // Set parameters
            $param_new_email = $new_email;
            $param_id = $_SESSION["id"];

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Email updated successfully, redirect back to welcome page
                header("location: welcome.php");
                exit;
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Change Email</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font: 14px sans-serif;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="mt-5">
        <h2>Change Email Address</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <!-- CSRF token field -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group <?php echo (!empty($new_email_err)) ? 'has-error' : ''; ?>">
                <label>New Email Address</label>
                <input type="email" name="new_email" class="form-control" value="<?php echo $new_email; ?>">
                <span class="help-block"><?php echo $new_email_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($confirm_email_err)) ? 'has-error' : ''; ?>">
                <label>Confirm Email Address</label>
                <input type="email" name="confirm_email" class="form-control" value="<?php echo $confirm_email; ?>">
                <span class="help-block"><?php echo $confirm_email_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Change Email">
            </div>
        </form>
    </div>
</body>

</html>
