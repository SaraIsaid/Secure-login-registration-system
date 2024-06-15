<?php
// Include the Composer autoloader
require 'vendor/autoload.php'; // Adjust the path as per your project structure

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize session
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";

// Initialize variables
$success_message = $error_message = "";
$verification_code = "";

// Retrieve the user's email and role from the database based on their username
$sql = "SELECT email, admin FROM users WHERE username = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $_SESSION["username"]);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_bind_result($stmt, $email, $admin);
        if (mysqli_stmt_fetch($stmt)) {
            $_SESSION["email"] = $email; // Set the email address to the session variable
            $_SESSION["admin"] = $admin; // Set the admin status to the session variable
        } else {
            $error_message = "Error: User data not found.";
        }
    } else {
        $error_message = "Error executing SQL statement: " . mysqli_error($link);
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Error preparing SQL statement: " . mysqli_error($link);
}

// Check if the email address is set in the session
if (!isset($_SESSION["email"])) {
    $error_message = "Error: User email not found.";
}

// Check if the admin status is set in the session
if (!isset($_SESSION["admin"])) {
    $error_message = "Error: User role not found.";
}

// Generate a random verification code if it hasn't been sent yet
if (!isset($_SESSION["verification_code"])) {
    $verification_code = rand(100000, 999999);
    $_SESSION["verification_code"] = $verification_code;

    // Send verification code via email
    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        // Set SMTP configuration
        $mail->isSMTP();
        $mail->Host = "smtp-mail.outlook.com"; // Your SMTP server host
        $mail->Port = 587; // Your SMTP server port
        $mail->SMTPSecure = "tls"; // Enable TLS encryption
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = "sarani28@outlook.com"; // Your SMTP username
        $mail->Password = "1+x==x=false"; // Your SMTP password

        // Set email parameters
        $mail->setFrom("sarani28@outlook.com", "Your Name"); // Sender's email and name
        $mail->addAddress($_SESSION["email"]); // Recipient's email
        $mail->Subject = "Verification Code for Two-Factor Authentication";
        $mail->Body = "Your verification code is: $verification_code";

        // Send the email
        if ($mail->send()) {
            $success_message = "Verification code sent to your email.";
        } else {
            throw new Exception("Error sending verification code: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
} else {
    $verification_code = $_SESSION["verification_code"];
}

// Verify verification code
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["verification_code"])) {
    // Get the verification code entered by the user
    $entered_code = $_POST["verification_code"];

    // Check if the entered code matches the expected code
    if ($entered_code == $verification_code) {
        // Verification successful, redirect the user based on their role
        if ($_SESSION["admin"] == 1) {
            header("location: admin.php");
        } else {
            header("location: welcome.php");
        }
        exit;
    } else {
        // Verification failed, display an error message
        $error_message = "Invalid verification code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Two-Factor Authentication</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font: 14px sans-serif; text-align: center; }
    </style>
</head>
<body>
    <div class="mt-5">
        <h2>Two-Factor Authentication</h2>
        <?php if ($success_message) : ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php elseif ($error_message) : ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="verification_code">Enter Verification Code:</label>
                <input type="text" id="verification_code" name="verification_code"
                class="form-control" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Verify</button>
            </div>
        </form>
    </div>
</body>
</html>
